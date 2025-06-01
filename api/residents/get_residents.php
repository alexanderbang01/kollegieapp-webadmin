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
    'data' => []
];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Kun GET requests er tilladt';
    echo json_encode($response);
    exit;
}

// Hent query parametre
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
$offset = ($page - 1) * $limit;

require_once '../../database/db_conn.php';

try {
    // Build SQL query
    $sql = "SELECT id, first_name, last_name, email, phone, room_number, profile_image FROM residents";
    $countSql = "SELECT COUNT(*) as total FROM residents";
    $params = [];
    $types = "";
    
    // Add search filter if provided
    if (!empty($search)) {
        $searchCondition = " WHERE CONCAT(first_name, ' ', last_name) LIKE ? OR room_number LIKE ? OR email LIKE ?";
        $sql .= $searchCondition;
        $countSql .= $searchCondition;
        
        $searchParam = "%$search%";
        $params = [$searchParam, $searchParam, $searchParam];
        $types = "sss";
    }
    
    // Get total count
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $totalResult = $countStmt->get_result();
    $total = $totalResult->fetch_assoc()['total'];
    
    // Add pagination
    $sql .= " ORDER BY first_name, last_name LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    // Execute main query
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $residents = [];
    while ($row = $result->fetch_assoc()) {
        // Generer initialer
        $initials = generateInitials($row['first_name'] . ' ' . $row['last_name']);
        
        $residents[] = [
            'id' => (int)$row['id'],
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'room_number' => $row['room_number'],
            'profile_image' => $row['profile_image'],
            'initials' => $initials,
            'role' => 'Værelse ' . $row['room_number'],
            'type' => 'resident'
        ];
    }
    
    $response['success'] = true;
    $response['message'] = 'Beboere hentet';
    $response['data'] = $residents;
    $response['pagination'] = [
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total,
        'total_pages' => ceil($total / $limit),
        'has_next' => $page < ceil($total / $limit),
        'has_prev' => $page > 1
    ];
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);

function generateInitials($name) {
    $nameParts = explode(' ', trim($name));
    
    if (count($nameParts) >= 2) {
        return mb_strtoupper(
            mb_substr($nameParts[0], 0, 1, 'UTF-8') . 
            mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8'), 
            'UTF-8'
        );
    } else {
        return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
    }
}
?>