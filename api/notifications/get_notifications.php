<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'data' => null
];

require_once '../../database/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = $_GET['user_id'] ?? null;
    $limit = intval($_GET['limit'] ?? 10);
    $page = intval($_GET['page'] ?? 1);
    $unreadCountOnly = $_GET['unread_count'] ?? false;
    
    if (!$userId) {
        $response['message'] = 'Bruger ID påkrævet';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    try {
        if ($unreadCountOnly) {
            // Hent antal ulæste notifikationer
            $stmt = $conn->prepare("
                SELECT COUNT(*) as unread_count
                FROM notifications n
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.resident_id = ?
                WHERE nr.id IS NULL
                ORDER BY n.created_at DESC
            ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_assoc();
            
            $response['success'] = true;
            $response['data'] = ['unread_count' => intval($count['unread_count'])];
        } else {
            // Hent notifikationer med pagination
            $offset = ($page - 1) * $limit;
            
            $stmt = $conn->prepare("
                SELECT 
                    n.*,
                    u.name as created_by_name,
                    (nr.id IS NOT NULL) as is_read
                FROM notifications n
                LEFT JOIN users u ON n.created_by = u.id
                LEFT JOIN notification_reads nr ON n.id = nr.notification_id AND nr.resident_id = ?
                ORDER BY n.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("iii", $userId, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $row['is_read'] = (bool)$row['is_read'];
                $notifications[] = $row;
            }
            
            $response['success'] = true;
            $response['data'] = $notifications;
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Serverfejl: ' . $e->getMessage();
        http_response_code(500);
    }
}

echo json_encode($response);
?>