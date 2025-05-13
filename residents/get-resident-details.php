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

// Tjek om resident_id er angivet
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ugyldigt beboer-ID']);
    exit();
}

$resident_id = (int)$_GET['id'];

// Hent beboer fra databasen
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $resident = $result->fetch_assoc();
        
        // Send beboerdata som JSON
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'resident' => $resident]);
        exit();
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Beboer blev ikke fundet']);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database forbindelse fejlede']);
    exit();
}
?>