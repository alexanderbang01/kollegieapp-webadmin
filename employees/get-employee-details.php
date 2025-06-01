<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sæt header til JSON
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'employee' => null
];

// Tjek om bruger er logget ind
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind';
    echo json_encode($response);
    exit;
}

// Tjek om ID er angivet
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Manglende eller ugyldigt ID';
    echo json_encode($response);
    exit;
}

$employee_id = (int)$_GET['id'];

// Hent database forbindelse
require_once '../database/db_conn.php';

if ($conn) {
    // Hent medarbejder data fra users tabellen
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role IN ('Administrator', 'Personale')");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        
        // Konverter til det format som frontend forventer
        $formatted_employee = [
            'id' => $employee['id'],
            'first_name' => explode(' ', $employee['name'])[0],
            'last_name' => isset(explode(' ', $employee['name'])[1]) ? implode(' ', array_slice(explode(' ', $employee['name']), 1)) : '',
            'name' => $employee['name'],
            'email' => $employee['email'],
            'phone' => $employee['phone'] ?? '',
            'profesion' => $employee['profession'] ?? $employee['role'],
            'profession' => $employee['profession'] ?? $employee['role'],
            'role' => $employee['role'],
            'profile_image' => $employee['profile_image'],
            'created_at' => $employee['created_at'],
            'updated_at' => $employee['updated_at']
        ];
        
        $response['success'] = true;
        $response['message'] = 'Medarbejder hentet';
        $response['employee'] = $formatted_employee;
    } else {
        $response['message'] = 'Medarbejder ikke fundet';
    }
} else {
    $response['message'] = 'Databaseforbindelse fejlede';
}

echo json_encode($response);
exit;
?>