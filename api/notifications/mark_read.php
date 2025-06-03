<?php
// api/notifications/mark_read.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

require_once '../../database/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userId = $input['user_id'] ?? null;
    $notificationId = $input['notification_id'] ?? null;
    $markAll = $input['mark_all'] ?? false;
    
    if (!$userId) {
        $response['message'] = 'Bruger ID påkrævet';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    try {
        if ($markAll) {
            // Marker alle som læst
            $stmt = $conn->prepare("
                INSERT IGNORE INTO notification_reads (notification_id, resident_id)
                SELECT n.id, ?
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.resident_id = ?
                WHERE nr.id IS NULL
            ");
            $stmt->bind_param("ii", $userId, $userId);
        } else {
            if (!$notificationId) {
                $response['message'] = 'Notifikation ID påkrævet';
                http_response_code(400);
                echo json_encode($response);
                exit;
            }
            
            // Marker specifik notifikation som læst
            $stmt = $conn->prepare("
                INSERT IGNORE INTO notification_reads (notification_id, resident_id)
                VALUES (?, ?)
            ");
            $stmt->bind_param("ii", $notificationId, $userId);
        }
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Markeret som læst';
        } else {
            $response['message'] = 'Kunne ikke markere som læst';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Serverfejl: ' . $e->getMessage();
        http_response_code(500);
    }
}

echo json_encode($response);
?>