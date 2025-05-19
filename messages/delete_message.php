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
    'message' => 'Der opstod en fejl'
];

// Tjek om bruger er logget ind
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind for at slette beskeder';
    echo json_encode($response);
    exit;
}

// Tjek om det er en POST request med påkrævede felter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['message_id'])) {
    $response['message'] = 'Ugyldig anmodning eller manglende felter';
    echo json_encode($response);
    exit;
}

require_once '../database/db_conn.php';

$message_id = (int)$_POST['message_id'];
$user_id = $_SESSION['user_id'];

if ($message_id <= 0) {
    $response['message'] = 'Ugyldig besked-ID';
    echo json_encode($response);
    exit;
}

try {
    // Tjek om brugeren ejer beskeden
    $stmt = $conn->prepare("
        SELECT id FROM messages 
        WHERE id = ? AND sender_id = ? AND sender_type = 'staff'
    ");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['message'] = 'Du har ikke tilladelse til at slette denne besked';
        echo json_encode($response);
        exit;
    }
    
    // Slet beskeden
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Besked slettet';
    } else {
        $response['message'] = 'Kunne ikke slette besked: ' . $stmt->error;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
}

echo json_encode($response);
exit;