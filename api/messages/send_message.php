<?php
// API/messages/send_message.php
// Sender en ny besked

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
    'message' => 'Der opstod en fejl'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Kun POST requests er tilladt';
    echo json_encode($response);
    exit;
}

// Læs JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Tjek authorization - med fallback for forskellige server setups
$authorization = null;

// Prøv flere måder at få authorization header
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

// Valider input
if (!isset($input['message']) || !isset($input['recipient_id']) || !isset($input['recipient_type'])) {
    $response['message'] = 'Manglende påkrævede felter: message, recipient_id, recipient_type';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$message = trim($input['message']);
$recipient_id = (int)$input['recipient_id'];
$recipient_type = $input['recipient_type'];

if (empty($message)) {
    $response['message'] = 'Besked kan ikke være tom';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if ($recipient_id <= 0 || !in_array($recipient_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldige modtager-oplysninger';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Krypter beskeden
    $encryptionResult = encryptMessage($message);
    
    // Gem besked i databasen
    $stmt = $conn->prepare("
        INSERT INTO messages (
            sender_id, 
            sender_type, 
            recipient_id, 
            recipient_type, 
            content, 
            encryption_iv, 
            is_encrypted, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->bind_param("isisss", 
        $user_id, 
        $user_type, 
        $recipient_id, 
        $recipient_type, 
        $encryptionResult['encrypted'], 
        $encryptionResult['iv']
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Besked sendt';
        $response['message_id'] = $conn->insert_id;
    } else {
        $response['message'] = 'Kunne ikke gemme besked: ' . $stmt->error;
        http_response_code(500);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

function encryptMessage($message) {
    $key = "Mercantec2025KollegieHemmeligKrypteringsNogle"; 
    $ivLength = 16;
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    $encrypted = openssl_encrypt(
        $message,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );
    
    return [
        'encrypted' => $encrypted,
        'iv' => bin2hex($iv)
    ];
}
?>