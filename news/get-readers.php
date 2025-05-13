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

// Håndter get-readers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['news_id'])) {
    $news_id = (int)$_GET['news_id'];
    
    try {
        // Hent læsere af den valgte nyhed
        $stmt = $conn->prepare("
            SELECT r.first_name, r.last_name, r.room_number, nr.read_at
            FROM news_reads nr
            JOIN residents r ON nr.resident_id = r.id
            WHERE nr.news_id = ?
            ORDER BY nr.read_at DESC
        ");
        $stmt->bind_param("i", $news_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $readers = [];
        while ($row = $result->fetch_assoc()) {
            // Formater tidspunkt til dansk format
            $date = new DateTime($row['read_at']);
            $readers[] = [
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'room' => $row['room_number'],
                'time' => $date->format('j. F, H:i')
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'readers' => $readers
        ]);
        exit();
        
    } catch (Exception $e) {
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