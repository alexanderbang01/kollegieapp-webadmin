<?php
// change-password.php
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
    $current_password = isset($_POST['current_password']) ? trim($_POST['current_password']) : null;
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : null;
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : null;
    $user_id = $_SESSION['user_id'];
    
    // Tjek at alle felter er udfyldt
    if (!$current_password || !$new_password || !$confirm_password) {
        throw new Exception("Alle felter skal udfyldes");
    }
    
    // Tjek at de nye adgangskoder matcher
    if ($new_password !== $confirm_password) {
        throw new Exception("De nye adgangskoder matcher ikke");
    }
    
    // Tjek at adgangskoden er mindst 8 tegn
    if (strlen($new_password) < 8) {
        throw new Exception("Adgangskoden skal være mindst 8 tegn");
    }
    
    // Hent brugerens nuværende adgangskode
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        throw new Exception("Brugeren blev ikke fundet");
    }
    
    $user = $result->fetch_assoc();
    
    // Tjek at den nuværende adgangskode er korrekt
    if (!password_verify($current_password, $user['password'])) {
        throw new Exception("Den nuværende adgangskode er ikke korrekt");
    }
    
    // Hash den nye adgangskode
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Opdater adgangskoden
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows !== 1) {
        throw new Exception("Adgangskoden kunne ikke opdateres");
    }
    
    // Log aktivitet
    $activity_description = "Adgangskode blev ændret.";
    
    $stmt = $conn->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'password_changed', ?)");
    $stmt->bind_param("is", $user_id, $activity_description);
    $stmt->execute();
    
    // Commit transaktionen
    $conn->commit();
    
    // Success
    $_SESSION['success_message'] = "Din adgangskode er blevet opdateret!";
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i change-password.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til settings siden
header("Location: ./");
exit;
?>