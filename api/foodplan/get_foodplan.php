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
    'data' => null
];

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $response['message'] = 'Kun GET requests er tilladt';
    echo json_encode($response);
    exit;
}

// Hent parametre
$week = isset($_GET['week']) ? (int)$_GET['week'] : null;
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;

// Hvis ingen uge eller år er angivet, brug nuværende uge
if ($week === null || $year === null) {
    $week = (int)date('W');
    $year = (int)date('Y');
}

// Valider parametre
if ($week < 1 || $week > 53) {
    $response['message'] = 'Ugyldig uge. Skal være mellem 1 og 53.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

if ($year < 2020 || $year > 2030) {
    $response['message'] = 'Ugyldig år. Skal være mellem 2020 og 2030.';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Hent madplan for den angivne uge
    $stmt = $conn->prepare("
        SELECT 
            fp.*,
            GROUP_CONCAT(
                CASE WHEN fpa.day_of_week = 'monday' THEN a.name END
                SEPARATOR ', '
            ) as monday_allergens,
            GROUP_CONCAT(
                CASE WHEN fpa.day_of_week = 'tuesday' THEN a.name END
                SEPARATOR ', '
            ) as tuesday_allergens,
            GROUP_CONCAT(
                CASE WHEN fpa.day_of_week = 'wednesday' THEN a.name END
                SEPARATOR ', '
            ) as wednesday_allergens,
            GROUP_CONCAT(
                CASE WHEN fpa.day_of_week = 'thursday' THEN a.name END
                SEPARATOR ', '
            ) as thursday_allergens
        FROM foodplan fp
        LEFT JOIN foodplan_allergens fpa ON fp.id = fpa.foodplan_id
        LEFT JOIN allergens a ON fpa.allergen_id = a.id
        WHERE fp.week_number = ? AND fp.year = ?
        GROUP BY fp.id
    ");
    
    $stmt->bind_param("ii", $week, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response['success'] = true;
        $response['message'] = "Ingen madplan fundet for uge $week, $year";
        $response['data'] = [
            'week' => $week,
            'year' => $year,
            'meals' => []
        ];
    } else {
        $foodplan = $result->fetch_assoc();
        
        // Struktur madplan data
        $meals = [];
        
        // Mandag
        if (!empty($foodplan['monday_dish'])) {
            $meals[] = [
                'dag' => 'Mandag',
                'ret' => $foodplan['monday_dish'],
                'beskrivelse' => $foodplan['monday_description'] ?? '',
                'tid' => '18:00', // Standard tid - kan udvides til database senere
                'allergener' => $foodplan['monday_allergens'] ? explode(', ', $foodplan['monday_allergens']) : [],
                'vegetar' => (bool)$foodplan['monday_vegetarian']
            ];
        }
        
        // Tirsdag
        if (!empty($foodplan['tuesday_dish'])) {
            $meals[] = [
                'dag' => 'Tirsdag',
                'ret' => $foodplan['tuesday_dish'],
                'beskrivelse' => $foodplan['tuesday_description'] ?? '',
                'tid' => '18:00',
                'allergener' => $foodplan['tuesday_allergens'] ? explode(', ', $foodplan['tuesday_allergens']) : [],
                'vegetar' => (bool)$foodplan['tuesday_vegetarian']
            ];
        }
        
        // Onsdag
        if (!empty($foodplan['wednesday_dish'])) {
            $meals[] = [
                'dag' => 'Onsdag',
                'ret' => $foodplan['wednesday_dish'],
                'beskrivelse' => $foodplan['wednesday_description'] ?? '',
                'tid' => '18:00',
                'allergener' => $foodplan['wednesday_allergens'] ? explode(', ', $foodplan['wednesday_allergens']) : [],
                'vegetar' => (bool)$foodplan['wednesday_vegetarian']
            ];
        }
        
        // Torsdag
        if (!empty($foodplan['thursday_dish'])) {
            $meals[] = [
                'dag' => 'Torsdag',
                'ret' => $foodplan['thursday_dish'],
                'beskrivelse' => $foodplan['thursday_description'] ?? '',
                'tid' => '18:00',
                'allergener' => $foodplan['thursday_allergens'] ? explode(', ', $foodplan['thursday_allergens']) : [],
                'vegetar' => (bool)$foodplan['thursday_vegetarian']
            ];
        }
        
        $response['success'] = true;
        $response['message'] = "Madplan hentet for uge $week, $year";
        $response['data'] = [
            'week' => $week,
            'year' => $year,
            'meals' => $meals,
            'created_at' => $foodplan['created_at'],
            'updated_at' => $foodplan['updated_at']
        ];
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
?>