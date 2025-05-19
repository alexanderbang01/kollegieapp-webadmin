<?php
// delete-user.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tjek om bruger er logget ind og er administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
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
    
    // Hent bruger-ID
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
    
    if (!$user_id) {
        throw new Exception("Intet bruger-ID angivet");
    }
    
    // Tjek at brugeren ikke sletter sig selv
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception("Du kan ikke slette din egen bruger");
    }
    
    // Hent brugeroplysninger til logning
    $stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception("Brugeren blev ikke fundet");
    }
    
    $user = $result->fetch_assoc();
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Slet først alle aktiviteter for brugeren
    $del_activities_stmt = $conn->prepare("DELETE FROM activities WHERE user_id = ?");
    $del_activities_stmt->bind_param("i", $user_id);
    $del_activities_stmt->execute();
    
    // Slet brugeren
    $del_user_stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $del_user_stmt->bind_param("i", $user_id);
    $del_user_stmt->execute();
    
    if ($del_user_stmt->affected_rows !== 1) {
        throw new Exception("Brugeren kunne ikke slettes");
    }
    
    // Log aktivitet
    $admin_id = $_SESSION['user_id'];
    $activity_description = "Bruger '{$user['username']}' ({$user['role']}) blev slettet.";
    
    $stmt = $conn->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'user_deleted', ?)");
    $stmt->bind_param("is", $admin_id, $activity_description);
    $stmt->execute();
    
    // Commit transaktionen
    $conn->commit();
    
    // Success
    $_SESSION['success_message'] = "Brugeren er blevet slettet!";
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i delete-user.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til settings siden
header("Location: ./");
exit;
?>