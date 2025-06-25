<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sæt header til JSON
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'resident' => null
];

// Tjek om bruger er logget ind
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind';
    echo json_encode($response);
    exit;
}

// Tjek om ID er angivet
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $response['message'] = 'Manglende eller ugyldigt ID';
    echo json_encode($response);
    exit;
}

$resident_id = (int)$_GET['id'];

// Hent database forbindelse
require_once '../database/db_conn.php';

if ($conn) {
    // Hent beboer data fra residents tabellen
    $stmt = $conn->prepare("SELECT * FROM residents WHERE id = ?");
    $stmt->bind_param("i", $resident_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $resident = $result->fetch_assoc();

        $response['success'] = true;
        $response['message'] = 'Beboer hentet';
        $response['resident'] = $resident;
    } else {
        $response['message'] = 'Beboer ikke fundet';
    }
} else {
    $response['message'] = 'Databaseforbindelse fejlede';
}

echo json_encode($response);
exit;
