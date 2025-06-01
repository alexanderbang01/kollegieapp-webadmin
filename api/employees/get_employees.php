<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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

// Hent parametre
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

require_once '../../database/db_conn.php';

try {
    // Byg SQL query baseret på søgning
    if ($search_query) {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, email, phone, profesion, profile_image
            FROM employees 
            WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR profesion LIKE ?
            ORDER BY last_name ASC, first_name ASC
        ");
        $search_param = "%{$search_query}%";
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    } else {
        $stmt = $conn->prepare("
            SELECT id, first_name, last_name, email, phone, profesion, profile_image
            FROM employees 
            ORDER BY last_name ASC, first_name ASC
        ");
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = [
            'id' => $row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'role' => $row['profesion'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'profile_image' => $row['profile_image'],
            'initials' => strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1))
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'Medarbejdere hentet';
    $response['data'] = $employees;
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>