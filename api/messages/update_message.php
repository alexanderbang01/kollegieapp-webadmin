<?php
// API/messages/update_message.php
// Opdaterer en eksisterende besked

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    $response['message'] = 'Kun POST/PUT requests er tilladt';
    echo json_encode($response);
    exit;
}

// Læs JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Tjek authorization
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    $response['message'] = 'Manglende authorization header';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$auth = str_replace('Bearer ', '', $headers['Authorization']);
$authParts = explode(':', $auth);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

// Valider input
if (!isset($input['message_id']) || !isset($input['content'])) {
    $response['message'] = 'Manglende påkrævede felter: message_id, content';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$message_id = (int)$input['message_id'];
$content = trim($input['content']);

if (empty($content)) {
    $response['message'] = 'Beskedindholdet kan ikke være tomt';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if ($message_id <= 0) {
    $response['message'] = 'Ugyldig besked-ID';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Tjek om brugeren ejer beskeden
    $stmt = $conn->prepare("
        SELECT id FROM messages 
        WHERE id = ? AND sender_id = ? AND sender_type = ?
    ");
    $stmt->bind_param("iis", $message_id, $user_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Du har ikke tilladelse til at redigere denne besked';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }
    
    // Kryptér det nye indhold
    $ivLength = 16;
    $iv = openssl_random_pseudo_bytes($ivLength);
    $iv_hex = bin2hex($iv);
    
    $key = "Mercantec2025KollegieHemmeligKrypteringsNogle";
    
    $encryptedContent = openssl_encrypt(
        $content,
        'AES-256-CBC',
        $key,
        0,
        $iv
    );
    
    // Opdater beskeden
    $stmt = $conn->prepare("
        UPDATE messages 
        SET content = ?, encryption_iv = ? 
        WHERE id = ? AND sender_id = ? AND sender_type = ?
    ");
    $stmt->bind_param("ssiis", $encryptedContent, $iv_hex, $message_id, $user_id, $user_type);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Besked opdateret';
    } else {
        $response['message'] = 'Kunne ikke opdatere besked: ' . $stmt->error;
        http_response_code(500);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>