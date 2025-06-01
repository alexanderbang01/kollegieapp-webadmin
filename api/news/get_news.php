<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'data' => [],
    'pagination' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Kun GET requests er tilladt';
    echo json_encode($response);
    exit;
}

// Hent parametre
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$featured_only = isset($_GET['featured_only']) ? $_GET['featured_only'] === 'true' : false;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
$offset = ($page - 1) * $limit;

require_once '../../database/db_conn.php';

try {
    // Byg WHERE clause
    $where_conditions = [];
    $where_conditions[] = "n.published_at <= NOW()"; // Kun publiserede nyheder
    
    if ($featured_only) {
        $where_conditions[] = "n.is_featured = 1";
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Først tæl total antal nyheder
    $count_stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM news n
        $where_clause
    ");
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_count = $total_result->fetch_assoc()['total'];
    
    // Så hent nyheder med pagination
    $stmt = $conn->prepare("
        SELECT 
            n.*,
            u.name as author_name,
            CASE WHEN nr.news_id IS NOT NULL THEN 1 ELSE 0 END as is_read_by_user
        FROM news n
        LEFT JOIN users u ON n.created_by = u.id
        LEFT JOIN news_reads nr ON n.id = nr.news_id AND nr.resident_id = ?
        $where_clause
        ORDER BY n.published_at DESC, n.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $news = [];
    while ($row = $result->fetch_assoc()) {
        // Beregn om nyheden er ny (under 7 dage gammel)
        $published_date = new DateTime($row['published_at']);
        $now = new DateTime();
        $days_old = $now->diff($published_date)->days;
        $is_recent = $days_old <= 7;
        
        $news[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'content' => $row['content'],
            'is_featured' => (bool)$row['is_featured'],
            'is_recent' => $is_recent,
            'is_read' => (bool)$row['is_read_by_user'],
            'published_at' => $row['published_at'],
            'author' => $row['author_name'] ?? 'Ukendt',
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'Nyheder hentet';
    $response['data'] = $news;
    $response['pagination'] = [
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total_count,
        'total_pages' => ceil($total_count / $limit),
        'has_next' => $page < ceil($total_count / $limit),
        'has_prev' => $page > 1
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>