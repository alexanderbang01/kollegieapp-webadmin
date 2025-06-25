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
    'pagination' => null,
    'debug' => []
];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Kun GET requests er tilladt');
    }

    // Hent parametre
    $show_past = isset($_GET['show_past']) ? $_GET['show_past'] === 'true' : false;
    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 1;
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    $response['debug']['params'] = [
        'show_past' => $show_past,
        'user_id' => $user_id,
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset
    ];

    // Test database forbindelse
    $db_path = '../../database/db_conn.php';
    if (!file_exists($db_path)) {
        throw new Exception("Database forbindelsesfil findes ikke: $db_path");
    }

    require_once $db_path;

    if (!isset($conn)) {
        throw new Exception('Database forbindelse ikke etableret');
    }

    if ($conn->connect_error) {
        throw new Exception('Database forbindelsen fejlede: ' . $conn->connect_error);
    }

    $response['debug']['database'] = 'Forbindelse etableret';

    // Test om events tabel eksisterer
    $test_query = "SHOW TABLES LIKE 'events'";
    $test_result = $conn->query($test_query);
    if ($test_result->num_rows == 0) {
        throw new Exception('Events tabel eksisterer ikke');
    }

    $response['debug']['events_table'] = 'Eksisterer';

    // Simpel test query først
    $simple_test = $conn->query("SELECT COUNT(*) as count FROM events");
    if (!$simple_test) {
        throw new Exception('Kan ikke læse fra events tabel: ' . $conn->error);
    }
    $total_events = $simple_test->fetch_assoc()['count'];
    $response['debug']['total_events_in_db'] = $total_events;

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
    $count_sql = "SELECT COUNT(*) as total FROM events e WHERE $date_condition";
    $count_stmt = $conn->prepare($count_sql);

    if (!$count_stmt) {
        throw new Exception('Kunne ikke forberede count query: ' . $conn->error);
    }

    $count_stmt->execute();
    $total_result = $count_stmt->get_result();
    $total_count = $total_result->fetch_assoc()['total'];

    $response['debug']['filtered_count'] = $total_count;

    // Simpel events query uden komplekse JOINs først
    $events_sql = "
        SELECT 
            e.*
        FROM events e
        WHERE $date_condition
        GROUP BY e.id
        $order
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($events_sql);
    if (!$stmt) {
        throw new Exception('Kunne ikke forberede events query: ' . $conn->error);
    }

    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while ($row = $result->fetch_assoc()) {
        // Hent organizer info separat
        $organizer_name = 'Ukendt';
        if ($row['created_by']) {
            $org_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
            if ($org_stmt) {
                $org_stmt->bind_param("i", $row['created_by']);
                $org_stmt->execute();
                $org_result = $org_stmt->get_result();
                if ($org_data = $org_result->fetch_assoc()) {
                    $organizer_name = $org_data['name'];
                }
            }
        }

        // Hent deltagere separat
        $participants = [];
        $current_participants = 0;
        $is_user_registered = false;

        $part_stmt = $conn->prepare("
            SELECT r.first_name, r.last_name, ep.resident_id
            FROM event_participants ep
            LEFT JOIN residents r ON ep.resident_id = r.id
            WHERE ep.event_id = ?
        ");

        if ($part_stmt) {
            $part_stmt->bind_param("i", $row['id']);
            $part_stmt->execute();
            $part_result = $part_stmt->get_result();

            while ($part_data = $part_result->fetch_assoc()) {
                if ($part_data['first_name'] && $part_data['last_name']) {
                    $participants[] = $part_data['first_name'] . ' ' . $part_data['last_name'];
                }
                if ($part_data['resident_id'] == $user_id) {
                    $is_user_registered = true;
                }
                $current_participants++;
            }
        }

        $events[] = [
            'id' => (int)$row['id'],
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? '',
            'date' => $row['date'] ?? '',
            'time' => $row['time'] ?? '',
            'location' => $row['location'] ?? '',
            'organizer' => $organizer_name,
            'maxParticipants' => $row['max_participants'] ? (int)$row['max_participants'] : null,
            'currentParticipants' => $current_participants,
            'participants' => $participants,
            'isUserRegistered' => $is_user_registered,
            'isPast' => $show_past,
            'created_at' => $row['created_at'] ?? ''
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
    $response['debug']['error'] = $e->getMessage();
    $response['debug']['file'] = $e->getFile();
    $response['debug']['line'] = $e->getLine();
    http_response_code(500);
}

echo json_encode($response);
