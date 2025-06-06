<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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
if (!isset($input['contact_id']) || !isset($input['contact_type'])) {
    $response['message'] = 'Manglende påkrævede felter: contact_id, contact_type';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$contact_id = (int)$input['contact_id'];
$contact_type = $input['contact_type'];

if ($contact_id <= 0 || !in_array($contact_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldige kontakt-oplysninger';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Marker alle ulæste beskeder fra denne kontakt som læst
    $stmt = $conn->prepare("
        UPDATE messages
        SET read_at = NOW()
        WHERE 
            recipient_id = ? 
            AND recipient_type = ? 
            AND sender_id = ?
            AND sender_type = ?
            AND read_at IS NULL
    ");
    
    $stmt->bind_param("isis", $user_id, $user_type, $contact_id, $contact_type);
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        $response['success'] = true;
        $response['message'] = "Markerede $affected_rows beskeder som læst";
        $response['marked_count'] = $affected_rows;
    } else {
        $response['message'] = 'Kunne ikke markere beskeder som læst: ' . $stmt->error;
        http_response_code(500);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);