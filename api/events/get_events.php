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
$show_past = isset($_GET['show_past']) ? $_GET['show_past'] === 'true' : false;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
$offset = ($page - 1) * $limit;

require_once '../../database/db_conn.php';

try {
    // Beregn 4 timer cutoff tid
    $four_hours_ago = "DATE_SUB(NOW(), INTERVAL 4 HOUR)";
    
    // Byg SQL query baseret på om vi vil se tidligere begivenheder
    if ($show_past) {
        // Begivenheder der er afsluttet (starttid + 4 timer er passeret)
        $date_condition = "CONCAT(e.date, ' ', e.time) < $four_hours_ago";
        $order = "ORDER BY e.date DESC, e.time DESC";
    } else {
        // Kommende begivenheder (starttid + 4 timer er ikke passeret endnu)
        $date_condition = "CONCAT(e.date, ' ', e.time) >= $four_hours_ago";
        $order = "ORDER BY e.date ASC, e.time ASC";
    }
    
    // Først tæl total antal events
    $count_stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM events e
        WHERE $date_condition
    ");
    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_count = $total_result->fetch_assoc()['total'];
    
    // Så hent events med pagination
    $stmt = $conn->prepare("
        SELECT 
            e.*,
            u.name as organizer_name,
            COUNT(ep.resident_id) as current_participants,
            GROUP_CONCAT(
                CONCAT(r.first_name, ' ', r.last_name) 
                SEPARATOR '|||'
            ) as participant_names,
            CASE WHEN ep_user.resident_id IS NOT NULL THEN 1 ELSE 0 END as is_user_registered
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN event_participants ep ON e.id = ep.event_id
        LEFT JOIN residents r ON ep.resident_id = r.id
        LEFT JOIN event_participants ep_user ON e.id = ep_user.event_id AND ep_user.resident_id = ?
        WHERE $date_condition
        GROUP BY e.id
        $order
        LIMIT ? OFFSET ?
    ");
    
    $stmt->bind_param("iii", $user_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Parse deltagere
        $participants = [];
        if (!empty($row['participant_names'])) {
            $participants = explode('|||', $row['participant_names']);
            $participants = array_filter($participants);
        }
        
        $events[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'description' => $row['description'] ?? '',
            'date' => $row['date'],
            'time' => $row['time'],
            'location' => $row['location'],
            'organizer' => $row['organizer_name'] ?? 'Ukendt',
            'maxParticipants' => $row['max_participants'],
            'currentParticipants' => (int)$row['current_participants'],
            'participants' => $participants,
            'isUserRegistered' => (bool)$row['is_user_registered'],
            'isPast' => $show_past,
            'created_at' => $row['created_at']
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'Begivenheder hentet';
    $response['data'] = $events;
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