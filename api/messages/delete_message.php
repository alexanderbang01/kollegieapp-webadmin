<?php
// API/messages/delete_message.php
// Sletter en besked

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    $response['message'] = 'Kun DELETE/POST requests er tilladt';
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
if (!isset($input['message_id'])) {
    $response['message'] = 'Manglende påkrævet felt: message_id';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$message_id = (int)$input['message_id'];

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
        $response['message'] = 'Du har ikke tilladelse til at slette denne besked';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }
    
    // Slet beskeden
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Besked slettet';
    } else {
        $response['message'] = 'Kunne ikke slette besked: ' . $stmt->error;
        http_response_code(500);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>