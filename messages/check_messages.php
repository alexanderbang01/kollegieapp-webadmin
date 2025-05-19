<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'hasNewMessages' => false
];

// Tjek login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind';
    echo json_encode($response);
    exit;
}

// Tjek parametre
if (!isset($_GET['user_id']) || !isset($_GET['user_type'])) {
    $response['message'] = 'Manglende parametre';
    echo json_encode($response);
    exit;
}

$user_id = (int)$_GET['user_id'];
$user_type = $_GET['user_type'];

require_once '../database/db_conn.php';

try {
    // Tjek for nye beskeder fra den angivne bruger
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM messages 
        WHERE 
            recipient_id = ? 
            AND recipient_type = 'staff'
            AND sender_id = ?
            AND sender_type = ?
            AND read_at IS NULL
    ");
    
    $stmt->bind_param("iis", $_SESSION['user_id'], $user_id, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    $response['hasNewMessages'] = ($row['count'] > 0);
    $response['success'] = true;
    $response['message'] = 'Check udført';
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
}

echo json_encode($response);
exit;