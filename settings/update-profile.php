<?php
// update-profile.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tjek om bruger er logget ind
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
    
    // Hent formdata
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $user_id = $_SESSION['user_id'];
    
    // Tjek at alle felter er udfyldt
    if (!$name || !$username) {
        throw new Exception("Alle felter skal udfyldes");
    }
    
    // Tjek at brugernavnet ikke allerede er i brug af en anden bruger
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Brugernavnet er allerede i brug");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Opdater profil
    $stmt = $conn->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $username, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows !== 1) {
        throw new Exception("Profilen kunne ikke opdateres");
    }
    
    // Log aktivitet
    $activity_description = "Profil blev opdateret.";
    
    $stmt = $conn->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'profile_updated', ?)");
    $stmt->bind_param("is", $user_id, $activity_description);
    $stmt->execute();
    
    // Opdater session-variablerne
    $_SESSION['name'] = $name;
    $_SESSION['username'] = $username;
    
    // Commit transaktionen
    $conn->commit();
    
    // Success
    $_SESSION['success_message'] = "Din profil er blevet opdateret!";
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i update-profile.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til settings siden
header("Location: ./");
exit;
?>