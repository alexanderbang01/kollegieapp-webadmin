<?php
// Start session
session_start();

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

// Tjek om bruger er logget ind og er administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    $response['message'] = 'Du har ikke tilladelse til at gemme sorteringsrækkefølge';
    echo json_encode($response);
    exit;
}

// Tjek om det er en POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Kun POST requests er tilladt';
    echo json_encode($response);
    exit;
}

// Database forbindelse
require_once '../database/db_conn.php';

try {
    // Læs JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['sort_order']) || !is_array($input['sort_order'])) {
        throw new Exception("Ugyldig sorteringsrækkefølge data");
    }

    $sortOrder = $input['sort_order'];
    $userId = $_SESSION['user_id'];

    // Valider at alle ID'er er heltal
    foreach ($sortOrder as $id) {
        if (!is_numeric($id)) {
            throw new Exception("Ugyldigt medarbejder-ID i sorteringsrækkefølge");
        }
    }

    // Tjek om employee_sort_order tabel eksisterer, hvis ikke opret den
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS employee_sort_order (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sort_order JSON NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )
    ";
    $conn->query($createTableQuery);

    // Start en transaktion
    $conn->begin_transaction();

    // Slet eksisterende sorteringsrækkefølge
    $deleteStmt = $conn->prepare("DELETE FROM employee_sort_order");
    $deleteStmt->execute();

    // Gem ny sorteringsrækkefølge
    $sortOrderJson = json_encode($sortOrder);
    $insertStmt = $conn->prepare("INSERT INTO employee_sort_order (sort_order, created_by) VALUES (?, ?)");
    $insertStmt->bind_param("si", $sortOrderJson, $userId);
    $insertStmt->execute();

    if ($insertStmt->affected_rows > 0) {
        // Log aktivitet
        $activity_description = "Sorteringsrækkefølge for medarbejdere blev opdateret.";
        $logStmt = $conn->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'sort_order_updated', ?)");
        $logStmt->bind_param("is", $userId, $activity_description);
        $logStmt->execute();

        // Commit transaktionen
        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Sorteringsrækkefølge gemt succesfuldt';
    } else {
        throw new Exception("Sorteringsrækkefølgen kunne ikke gemmes");
    }
} catch (Exception $e) {
    // Ved fejl: Rollback
    if (isset($conn)) {
        $conn->rollback();
    }

    error_log("Fejl i save-sort-order.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
