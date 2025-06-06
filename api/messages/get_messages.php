<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'data' => []
];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Kun GET requests er tilladt';
    echo json_encode($response);
    exit;
}

// Tjek authorization - med fallback for forskellige server setups
$authorization = null;

if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authorization = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authorization = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authorization = $headers['authorization'];
    }
} elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authorization = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authorization = $headers['authorization'];
    }
}

if (!$authorization) {
    $response['message'] = 'Manglende authorization header';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$authParts = explode(':', $authorization);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format. Brug user_id:user_type';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

if ($user_id <= 0 || !in_array($user_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldig bruger-ID eller type';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// Tjek parametre
if (!isset($_GET['contact_id']) || !isset($_GET['contact_type'])) {
    $response['message'] = 'Manglende contact_id eller contact_type parameter';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$contact_id = (int)$_GET['contact_id'];
$contact_type = $_GET['contact_type'];

if ($contact_id <= 0 || !in_array($contact_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldige parametre';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    $messages = [];
    
    // 1. Hent beskeder: Contact til Mig
    $incomingSQL = "
        SELECT * FROM messages
        WHERE 
            sender_id = ? AND 
            sender_type = ? AND 
            recipient_id = ? AND 
            recipient_type = ?
    ";
    
    $stmt = $conn->prepare($incomingSQL);
    $stmt->bind_param("isis", $contact_id, $contact_type, $user_id, $user_type);
    $stmt->execute();
    $incomingResult = $stmt->get_result();
    
    while ($row = $incomingResult->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // 2. Hent beskeder: Mig til Contact
    $outgoingSQL = "
        SELECT * FROM messages
        WHERE 
            sender_id = ? AND 
            sender_type = ? AND 
            recipient_id = ? AND 
            recipient_type = ?
    ";
    
    $stmt = $conn->prepare($outgoingSQL);
    $stmt->bind_param("isis", $user_id, $user_type, $contact_id, $contact_type);
    $stmt->execute();
    $outgoingResult = $stmt->get_result();
    
    while ($row = $outgoingResult->fetch_assoc()) {
        $messages[] = $row;
    }
    
    // 3. Sortér beskeder efter tidspunkt
    usort($messages, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    // 4. Behandl og dekrypter beskeder
    $processedMessages = [];
    foreach ($messages as $message) {
        // Dekrypter besked
        $content = $message['content'];
        if ($message['is_encrypted'] == 1) {
            $content = decryptMessage($message['content'], $message['encryption_iv']);
        }
        
        // Få sender navn
        $senderName = 'Ukendt';
        if ($message['sender_type'] == 'staff') {
            $name_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            $name_stmt->bind_param("i", $message['sender_id']);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            if ($name_row = $name_result->fetch_assoc()) {
                $senderName = $name_row['name'];
            }
        } else {
            $name_stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM residents WHERE id = ?");
            $name_stmt->bind_param("i", $message['sender_id']);
            $name_stmt->execute();
            $name_result = $name_stmt->get_result();
            if ($name_row = $name_result->fetch_assoc()) {
                $senderName = $name_row['name'];
            }
        }
        
        $processedMessages[] = [
            'id' => $message['id'],
            'text' => $content,
            'sender' => $senderName,
            'isCurrentUser' => ($message['sender_id'] == $user_id && $message['sender_type'] == $user_type),
            'timestamp' => $message['created_at'],
            'isRead' => !is_null($message['read_at'])
        ];
    }
    
    // 5. Marker beskeder som læst
    $mark_read_sql = "
        UPDATE messages
        SET read_at = NOW()
        WHERE 
            recipient_id = ? 
            AND recipient_type = ? 
            AND sender_id = ?
            AND sender_type = ?
            AND read_at IS NULL
    ";
    
    $stmt2 = $conn->prepare($mark_read_sql);
    $stmt2->bind_param("isis", $user_id, $user_type, $contact_id, $contact_type);
    $stmt2->execute();
    
    $response['success'] = true;
    $response['message'] = 'Beskeder hentet';
    $response['data'] = $processedMessages;
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

function decryptMessage($encryptedContent, $encryptionIv) {
    if (empty($encryptionIv) || empty($encryptedContent)) {
        return "[Krypteret besked]";
    }
    
    try {
        $key = "Mercantec2025KollegieHemmeligKrypteringsNogle";
        $iv = hex2bin($encryptionIv);
        
        $decrypted = openssl_decrypt(
            $encryptedContent,
            'AES-256-CBC',
            $key,
            0,
            $iv
        );
        
        return $decrypted === false ? "[Besked kunne ikke dekrypteres]" : $decrypted;
    } catch (Exception $e) {
        return "[Fejl ved dekryptering]";
    }
}
?>