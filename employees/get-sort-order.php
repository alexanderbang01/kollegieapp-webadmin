<?php
// Start session
session_start();

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'sort_order' => []
];

// Tjek om bruger er logget ind og er administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    $response['message'] = 'Du har ikke tilladelse til at se sorteringsrækkefølge';
    echo json_encode($response);
    exit;
}

// Database forbindelse
require_once '../database/db_conn.php';

try {
    // Tjek om der findes en employee_sort_order tabel, hvis ikke opret den
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_sort_order (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sort_order JSON NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $conn->query($createTableQuery);

    // Hent den seneste sorteringsrækkefølge
    $stmt = $conn->prepare("SELECT sort_order FROM employee_sort_order ORDER BY updated_at DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sortOrder = json_decode($row['sort_order'], true);

        $response['success'] = true;
        $response['message'] = 'Sorteringsrækkefølge hentet';
        $response['sort_order'] = $sortOrder;
    } else {
        // Ingen sorteringsrækkefølge fundet, returner tom array
        $response['success'] = true;
        $response['message'] = 'Ingen sorteringsrækkefølge fundet';
        $response['sort_order'] = [];
    }
} catch (Exception $e) {
    error_log("Fejl i get-sort-order.php: " . $e->getMessage());
    $response['message'] = 'Der opstod en fejl ved hentning af sorteringsrækkefølge';
}

echo json_encode($response);
exit;
