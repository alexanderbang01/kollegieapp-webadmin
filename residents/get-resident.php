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
    
    // Hent resident_id
    $resident_id = isset($_POST['resident_id']) ? (int)$_POST['resident_id'] : null;
    
    // Tjek at resident_id er angivet
    if (!$resident_id) {
        throw new Exception("Manglende resident_id");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Check om brugeren har ret til at slette beboeren
    $user_role = $_SESSION['role'];
    
    $can_delete = ($user_role == 'Administrator');
    
    if (!$can_delete) {
        throw new Exception("Du har ikke tilladelse til at slette denne beboer");
    }
    
    // Slet først alle event_participants registreringer for beboeren (hvis tabellen stadig findes)
    if ($conn->query("SHOW TABLES LIKE 'event_participants'")->num_rows > 0) {
        $del_participants_stmt = $conn->prepare("DELETE FROM event_participants WHERE resident_id = ?");
        $del_participants_stmt->bind_param("i", $resident_id);
        $del_participants_stmt->execute();
    }
    
    // Slet news_reads registreringer (hvis tabellen stadig findes)
    if ($conn->query("SHOW TABLES LIKE 'news_reads'")->num_rows > 0) {
        $del_news_reads_stmt = $conn->prepare("DELETE FROM news_reads WHERE resident_id = ?");
        $del_news_reads_stmt->bind_param("i", $resident_id);
        $del_news_reads_stmt->execute();
    }
    
    // Slet activities registreringer (hvis tabellen stadig findes)
    if ($conn->query("SHOW TABLES LIKE 'activities'")->num_rows > 0) {
        $del_activities_stmt = $conn->prepare("DELETE FROM activities WHERE resident_id = ?");
        $del_activities_stmt->bind_param("i", $resident_id);
        $del_activities_stmt->execute();
    }
    
    // Slet beboeren
    $del_resident_stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
    $del_resident_stmt->bind_param("i", $resident_id);
    $del_resident_stmt->execute();
    
    if ($del_resident_stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Beboeren blev slettet!";
    } else {
        throw new Exception("Beboeren kunne ikke slettes");
    }
    
    // Commit transaktionen
    $conn->commit();
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i delete-resident.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til residents-oversigten
header("Location: ./");
exit;
?>