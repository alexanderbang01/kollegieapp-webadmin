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
$authorization = null;
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authorization = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authorization = $headers['Authorization'];
    }
}

if (!$authorization) {
    $response['message'] = 'Manglende authorization header';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$auth = str_replace('Bearer ', '', $authorization);
$authParts = explode(':', $auth);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

// Kun residents kan markere nyheder som læst
if ($user_type !== 'resident') {
    $response['message'] = 'Kun beboere kan markere nyheder som læst';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Valider input
if (!isset($input['news_id'])) {
    $response['message'] = 'Manglende påkrævet felt: news_id';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

$news_id = (int)$input['news_id'];

if ($news_id <= 0) {
    $response['message'] = 'Ugyldig nyheds-ID';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Tjek om nyheden eksisterer
    $stmt = $conn->prepare("SELECT id FROM news WHERE id = ?");
    $stmt->bind_param("i", $news_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Nyhed ikke fundet';
        http_response_code(404);
        echo json_encode($response);
        exit;
    }
    
    // Indsæt eller opdater læsestatus (ON DUPLICATE KEY UPDATE for at undgå fejl hvis allerede læst)
    $stmt = $conn->prepare("
        INSERT INTO news_reads (news_id, resident_id, read_at)
        VALUES (?, ?, NOW())
        ON DUPLICATE KEY UPDATE read_at = NOW()
    ");
    $stmt->bind_param("ii", $news_id, $user_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Nyhed markeret som læst';
    } else {
        $response['message'] = 'Kunne ikke markere nyhed som læst: ' . $stmt->error;
        http_response_code(500);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>