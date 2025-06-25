<?php
// Start session
session_start();

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'data' => []
];

// Tjek om bruger er logget ind og er administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    $response['message'] = 'Du har ikke tilladelse til at se medarbejdere';
    echo json_encode($response);
    exit;
}

// Database forbindelse
require_once '../database/db_conn.php';

try {
    // Hent ALLE medarbejdere (både Administrator og Personale)
    $stmt = $conn->prepare("
        SELECT id, name, email, phone, profession, role, profile_image
        FROM users 
        WHERE role IN ('Administrator', 'Personale')
        ORDER BY name ASC
    ");

    $stmt->execute();
    $result = $stmt->get_result();

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        // Generer initialer
        $name_parts = explode(' ', $row['name']);
        $first_initial = isset($name_parts[0]) ? mb_substr($name_parts[0], 0, 1, 'UTF-8') : '';
        $last_initial = isset($name_parts[1]) ? mb_substr($name_parts[count($name_parts) - 1], 0, 1, 'UTF-8') : '';
        $initials = mb_strtoupper($first_initial . $last_initial, 'UTF-8');
        if (empty($initials)) $initials = 'U';

        $employees[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'profession' => $row['profession'] ?: $row['role'],
            'role' => $row['role'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'profile_image' => $row['profile_image'],
            'initials' => $initials
        ];
    }

    $response['success'] = true;
    $response['message'] = 'Alle medarbejdere hentet';
    $response['data'] = $employees;
} catch (Exception $e) {
    error_log("Fejl i get-all-employees.php: " . $e->getMessage());
    $response['message'] = 'Der opstod en fejl ved hentning af medarbejdere';
}

echo json_encode($response);
exit;
