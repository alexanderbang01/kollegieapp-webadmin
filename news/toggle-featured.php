<?php
// Start session
session_start();

// Tjek om bruger er logget ind
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Du er ikke logget ind']);
    exit();
}

// Database forbindelse
include '../database/db_conn.php';

// Håndter toggle-featured
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['news_id']) && isset($_POST['is_featured'])) {
    $news_id = (int)$_POST['news_id'];
    $is_featured = (int)$_POST['is_featured']; // 0 eller 1
    
    try {
        // Start en transaktion
        $conn->begin_transaction();
        
        // Fjern tilladelseskontrol - Alle kan fremhæve/afpinne nyheder
        
        // Hvis denne nyhed skal fremhæves, fjern fremhævning fra alle andre nyheder
        if ($is_featured) {
            $update_stmt = $conn->prepare("UPDATE news SET is_featured = 0 WHERE is_featured = 1");
            $update_stmt->execute();
        }
        
        // Opdater denne nyhed
        $stmt = $conn->prepare("UPDATE news SET is_featured = ? WHERE id = ?");
        $stmt->bind_param("ii", $is_featured, $news_id);
        $stmt->execute();
        
        if ($stmt->affected_rows >= 0) {
            $conn->commit();
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit();
        } else {
            throw new Exception("Kunne ikke opdatere nyhedens status");
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Ugyldig anmodning']);
    exit();
}
?>