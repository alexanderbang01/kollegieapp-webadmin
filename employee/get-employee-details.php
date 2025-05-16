<?php
// Start session
session_start();

// Tjek om bruger er logget ind, ellers send fejl
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Du er ikke logget ind']);
    exit();
}

// Database forbindelse
include '../database/db_conn.php';

// Tjek om employee_id er angivet
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ugyldigt ansat-ID']);
    exit();
}

$employee_id = (int)$_GET['id'];

// Hent ansatte fra databasen
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM employee WHERE id = ?");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $employee = $result->fetch_assoc();
        
        // Send ansattedata som JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'employee' => $employee]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Ansat blev ikke fundet']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database forbindelse fejlede']);
    exit();
}
?>