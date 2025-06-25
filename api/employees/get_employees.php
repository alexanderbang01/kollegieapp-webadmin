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

// Hent parametre
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$contact_format = isset($_GET['contact_format']) ? $_GET['contact_format'] === 'true' : false;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

require_once '../../database/db_conn.php';

try {
    // Hent sorteringsrækkefølge fra employee_sort_order tabel
    $sortOrder = [];
    $sortStmt = $conn->prepare("SELECT sort_order FROM employee_sort_order ORDER BY updated_at DESC LIMIT 1");
    $sortStmt->execute();
    $sortResult = $sortStmt->get_result();
    if ($sortResult->num_rows > 0) {
        $sortRow = $sortResult->fetch_assoc();
        $sortOrder = json_decode($sortRow['sort_order'], true) ?: [];
    }

    // Byg SQL query baseret på søgning - hent fra users tabel i stedet for employees
    if ($search_query) {
        $stmt = $conn->prepare("
            SELECT id, name, email, phone, profession, role, profile_image
            FROM users 
            WHERE role IN ('Administrator', 'Personale') 
            AND (name LIKE ? OR email LIKE ? OR profession LIKE ? OR role LIKE ?)
            ORDER BY name ASC
        ");
        $search_param = "%{$search_query}%";
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    } else {
        // Hvis contact_format er angivet, hent alle uden pagination
        if ($contact_format) {
            $stmt = $conn->prepare("
                SELECT id, name, email, phone, profession, role, profile_image
                FROM users 
                WHERE role IN ('Administrator', 'Personale')
                ORDER BY name ASC
            ");
        } else {
            // Normal pagination for app
            $stmt = $conn->prepare("
                SELECT id, name, email, phone, profession, role, profile_image
                FROM users 
                WHERE role IN ('Administrator', 'Personale')
                ORDER BY name ASC
                LIMIT ? OFFSET ?
            ");
            $stmt->bind_param("ii", $limit, $offset);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $employees = [];
    while ($row = $result->fetch_assoc()) {
        // Generer initialer
        $name_parts = explode(' ', $row['name']);
        $first_initial = isset($name_parts[0]) ? mb_substr($name_parts[0], 0, 1, 'UTF-8') : '';
        $last_initial = isset($name_parts[1]) ? mb_substr($name_parts[count($name_parts) - 1], 0, 1, 'UTF-8') : '';
        $initials = mb_strtoupper($first_initial . $last_initial, 'UTF-8');
        if (empty($initials)) $initials = 'U';

        $employees[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'role' => $row['profession'] ?: $row['role'],
            'profession' => $row['profession'] ?: $row['role'],
            'phone' => $row['phone'],
            'email' => $row['email'],
            'profile_image' => $row['profile_image'],
            'initials' => $initials
        ];
    }

    // Sorter medarbejdere baseret på gemt rækkefølge (kun hvis ingen søgning)
    if (!$search_query && !empty($sortOrder)) {
        usort($employees, function ($a, $b) use ($sortOrder) {
            $aIndex = array_search($a['id'], $sortOrder);
            $bIndex = array_search($b['id'], $sortOrder);

            // Hvis begge er i sort_order, sorter efter position
            if ($aIndex !== false && $bIndex !== false) {
                return $aIndex - $bIndex;
            }
            // Hvis kun a er i sort_order, a kommer først
            if ($aIndex !== false) return -1;
            // Hvis kun b er i sort_order, b kommer først
            if ($bIndex !== false) return 1;
            // Hvis ingen er i sort_order, sorter alfabetisk
            return strcmp($a['name'], $b['name']);
        });
    }

    $response['success'] = true;
    $response['message'] = 'Medarbejdere hentet';
    $response['data'] = $employees;
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
