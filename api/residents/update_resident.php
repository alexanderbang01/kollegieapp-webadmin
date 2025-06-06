<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'])) {
    $response['message'] = 'Kun PUT/POST requests er tilladt';
    echo json_encode($response);
    exit;
}

// Tjek authorization - prøv flere metoder
$authorization = null;

// Metode 1: Standard HTTP_AUTHORIZATION
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authorization = $_SERVER['HTTP_AUTHORIZATION'];
}
// Metode 2: Redirect variant
elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
// Metode 3: Apache function
elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authorization = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authorization = $headers['authorization'];
    }
}
// Metode 4: getallheaders function
elseif (function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $authorization = $headers['Authorization'];
    } elseif (isset($headers['authorization'])) {
        $authorization = $headers['authorization'];
    }
}

if (!$authorization) {
    $response['message'] = 'Manglende authorization header';
    $response['debug'] = 'Headers: ' . json_encode(function_exists('getallheaders') ? getallheaders() : $_SERVER);
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$authParts = explode(':', $authorization);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format';
    $response['debug'] = 'Auth: ' . $authorization;
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

if ($user_type !== 'resident') {
    $response['message'] = 'Kun beboere kan opdatere deres profil';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Læs JSON input
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null) {
    $response['message'] = 'Ugyldig JSON data';
    $response['debug'] = 'Raw input: ' . file_get_contents('php://input');
    http_response_code(400);
    echo json_encode($response);
    exit;
}

// Valider input
$required_fields = ['first_name', 'last_name', 'email', 'phone', 'room_number'];
foreach ($required_fields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        $response['message'] = "Manglende påkrævet felt: $field";
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
}

$first_name = trim($input['first_name']);
$last_name = trim($input['last_name']);
$email = trim(strtolower($input['email']));
$phone = trim($input['phone']);
$room_number = trim(strtoupper($input['room_number']));
$contact_name = isset($input['contact_name']) ? trim($input['contact_name']) : '';
$contact_phone = isset($input['contact_phone']) ? trim($input['contact_phone']) : '';

// Valideringer
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Ugyldig email adresse';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if (!preg_match('/^[a-zA-ZæøåÆØÅ\s]+$/u', $first_name) || 
    !preg_match('/^[a-zA-ZæøåÆØÅ\s]+$/u', $last_name)) {
    $response['message'] = 'Navne må kun indeholde bogstaver';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if (!preg_match('/^[A-Z]-\d{3}$/', $room_number)) {
    $response['message'] = 'Værelsenummer skal have format A-204';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Tjek at email og værelse ikke er i brug af andre beboere
    $check_stmt = $conn->prepare("
        SELECT id FROM residents 
        WHERE (email = ? OR room_number = ?) AND id != ?
    ");
    $check_stmt->bind_param("ssi", $email, $room_number, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $response['message'] = 'Email eller værelse er allerede i brug af en anden beboer';
        http_response_code(409);
        echo json_encode($response);
        exit;
    }
    
    // Opdater beboer
    $stmt = $conn->prepare("
        UPDATE residents SET 
            first_name = ?, 
            last_name = ?, 
            email = ?, 
            phone = ?, 
            room_number = ?, 
            contact_name = ?, 
            contact_phone = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->bind_param("sssssssi", 
        $first_name, 
        $last_name, 
        $email, 
        $phone, 
        $room_number, 
        $contact_name, 
        $contact_phone, 
        $user_id
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profil opdateret succesfuldt';
        $response['data'] = [
            'id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'room_number' => $room_number,
            'contact_name' => $contact_name,
            'contact_phone' => $contact_phone
        ];
    } else {
        $response['message'] = 'Kunne ikke opdatere profil';
        http_response_code(500);
    }
    
} catch (Exception $e) {
    $response['message'] = 'Serverfejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>