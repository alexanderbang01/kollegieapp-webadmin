<?php
// Start session
session_start();

// Fejlhåndtering
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

// Database forbindelse
include '../database/db_conn.php';

// Simpel fejl-logger
function logError($message) {
    error_log($message, 0);
    $_SESSION['error_message'] = "Der opstod en fejl. Tjek venligst server log for detaljer.";
}

try {
    // Tjek om det er en POST request
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        throw new Exception("Kun POST requests er tilladt");
    }
    
    // Tjek om databaseforbindelsen er tilgængelig
    if (!isset($conn)) {
        throw new Exception("Database forbindelse fejlede");
    }
    
    // Hent employee_id
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    
    // Tjek at resident_id er angivet
    if (!$employee_id) {
        throw new Exception("Manglende employee_id");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Check om brugeren har ret til at slette beboeren
    $user_role = $_SESSION['role'];
    
    $can_delete = ($user_role == 'Administrator');
    
    if (!$can_delete) {
        throw new Exception("Du har ikke tilladelse til at slette denne Ansat");
    }
    
    // Slet Ansat
    $del_employee_stmt = $conn->prepare("DELETE FROM employee WHERE id = ?");
    $del_employee_stmt->bind_param("i", $employee_id);
    $del_employee_stmt->execute();
    
    if ($del_resident_stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Den Ansatte blev slettet!";
    } else {
        throw new Exception("Den Ansatte kunne ikke slettes");
    }
    
    // Commit transaktionen
    $conn->commit();
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i delete-employee.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til residents-oversigten
header("Location: ./");
exit;
?>