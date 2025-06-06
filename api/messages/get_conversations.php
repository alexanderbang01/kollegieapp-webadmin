<?php
// API/messages/get_conversations.php
// Henter alle samtaler for den aktuelle bruger (staff/resident)

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Håndter preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'data' => []
];

// Tjek request metode
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Kun GET requests er tilladt';
    echo json_encode($response);
    exit;
}

// Tjek authorization header
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    $response['message'] = 'Manglende authorization header';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

// Parse authorization header (user_id:user_type)
$auth = str_replace('Bearer ', '', $headers['Authorization']);
$authParts = explode(':', $auth);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format. Brug user_id:user_type';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1]; // 'staff' eller 'resident'

if ($user_id <= 0 || !in_array($user_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldig bruger-ID eller type';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Hent alle unikke samtaler (personer vi har udvekslet beskeder med)
    $conversations = [];
    
    // Hent alle kontakter der har beskeder med denne bruger
    $sql = "
        SELECT DISTINCT
            CASE 
                WHEN m.sender_id = ? AND m.sender_type = ? THEN m.recipient_id
                ELSE m.sender_id
            END as contact_id,
            CASE 
                WHEN m.sender_id = ? AND m.sender_type = ? THEN m.recipient_type
                ELSE m.sender_type
            END as contact_type
        FROM messages m
        WHERE 
            (m.sender_id = ? AND m.sender_type = ?) OR 
            (m.recipient_id = ? AND m.recipient_type = ?)
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisisis", 
        $user_id, $user_type, $user_id, $user_type,
        $user_id, $user_type, $user_id, $user_type
    );
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Hent detaljer for hver kontakt
    while ($row = $result->fetch_assoc()) {
        $contact_id = $row['contact_id'];
        $contact_type = $row['contact_type'];
        
        // Skip hvis contact_id er lig med brugerens eget ID
        if ($contact_id == $user_id && $contact_type == $user_type) continue;
        
        // Hent kontakt info
        $contact_info = null;
        if ($contact_type === 'staff') {
            $stmt2 = $conn->prepare("SELECT id, name, role, profile_image FROM users WHERE id = ?");
            $stmt2->bind_param("i", $contact_id);
            $stmt2->execute();
            $contact_result = $stmt2->get_result();
            $contact_info = $contact_result->fetch_assoc();
            
            if ($contact_info) {
                $contact_info['type'] = 'staff';
                $contact_info['additional'] = $contact_info['role'];
            }
        } else {
            $stmt2 = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, room_number, profile_image FROM residents WHERE id = ?");
            $stmt2->bind_param("i", $contact_id);
            $stmt2->execute();
            $contact_result = $stmt2->get_result();
            $contact_info = $contact_result->fetch_assoc();
            
            if ($contact_info) {
                $contact_info['type'] = 'resident';
                $contact_info['additional'] = 'Værelse ' . $contact_info['room_number'];
            }
        }
        
        if (!$contact_info) continue;
        
        // Hent seneste besked
        $latest_message_sql = "
            SELECT content, created_at, encryption_iv, is_encrypted, sender_id, sender_type, read_at
            FROM messages 
            WHERE 
                ((sender_id = ? AND sender_type = ? AND recipient_id = ? AND recipient_type = ?) OR
                 (sender_id = ? AND sender_type = ? AND recipient_id = ? AND recipient_type = ?))
            ORDER BY created_at DESC 
            LIMIT 1
        ";
        
        $stmt3 = $conn->prepare($latest_message_sql);
        $stmt3->bind_param("sssisssi", 
            $user_id, $user_type, $contact_id, $contact_type,
            $contact_id, $contact_type, $user_id, $user_type
        );
        $stmt3->execute();
        $latest_result = $stmt3->get_result();
        $latest_message = $latest_result->fetch_assoc();
        
        // Dekrypter seneste besked
        $decrypted_content = '';
        if ($latest_message && $latest_message['content']) {
            if ($latest_message['is_encrypted'] == 1) {
                $decrypted_content = decryptMessage($latest_message['content'], $latest_message['encryption_iv']);
            } else {
                $decrypted_content = $latest_message['content'];
            }
        }
        
        // Hent antal ulæste beskeder
        $unread_sql = "
            SELECT COUNT(*) as unread_count
            FROM messages 
            WHERE 
                sender_id = ? AND sender_type = ? AND 
                recipient_id = ? AND recipient_type = ? AND 
                read_at IS NULL
        ";
        
        $stmt4 = $conn->prepare($unread_sql);
        $stmt4->bind_param("issi", $contact_id, $contact_type, $user_id, $user_type);
        $stmt4->execute();
        $unread_result = $stmt4->get_result();
        $unread_data = $unread_result->fetch_assoc();
        
        // Generer initialer
        $initials = generateInitials($contact_info['name']);
        
        // Tilføj til samtaler
        $conversations[] = [
            'id' => $contact_info['id'],
            'name' => $contact_info['name'],
            'type' => $contact_info['type'],
            'role' => $contact_info['additional'],
            'avatar' => $initials,
            'lastMessage' => $decrypted_content,
            'lastMessageTime' => $latest_message ? $latest_message['created_at'] : null,
            'unreadCount' => (int)$unread_data['unread_count'],
            'isOnline' => rand(0, 1) == 1, // Mock online status
            'profileImage' => $contact_info['profile_image']
        ];
    }
    
    // Sorter efter seneste besked
    usort($conversations, function($a, $b) {
        if (!$a['lastMessageTime']) return 1;
        if (!$b['lastMessageTime']) return -1;
        return strtotime($b['lastMessageTime']) - strtotime($a['lastMessageTime']);
    });
    
    $response['success'] = true;
    $response['message'] = 'Samtaler hentet';
    $response['data'] = $conversations;
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

// Hjælpefunktioner
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

function generateInitials($name) {
    $nameParts = explode(' ', $name);
    
    if (count($nameParts) >= 2) {
        return mb_strtoupper(mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8'), 'UTF-8');
    } else {
        return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
    }
}
?>