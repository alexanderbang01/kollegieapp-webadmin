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
    
    // Hent news_id
    $news_id = isset($_POST['news_id']) ? (int)$_POST['news_id'] : null;
    
    // Tjek at news_id er angivet
    if (!$news_id) {
        throw new Exception("Manglende news_id");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Check om brugeren har ret til at slette nyheden
    // Administratorer kan slette alle nyheder, personale kun deres egne
    $user_role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    $can_delete = false;
    
    if ($user_role == 'Administrator') {
        $can_delete = true;
    } else {
        // Tjek om nyheden tilhører brugeren
        $check_stmt = $conn->prepare("SELECT id FROM news WHERE id = ? AND created_by = ?");
        $check_stmt->bind_param("ii", $news_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $can_delete = true;
        }
    }
    
    if (!$can_delete) {
        throw new Exception("Du har ikke tilladelse til at slette denne nyhed");
    }
    
    // Slet først alle læseregistreringer for nyheden (fremmed nøgle)
    $del_reads_stmt = $conn->prepare("DELETE FROM news_reads WHERE news_id = ?");
    $del_reads_stmt->bind_param("i", $news_id);
    $del_reads_stmt->execute();
    
    // Slet derefter nyheden
    $del_news_stmt = $conn->prepare("DELETE FROM news WHERE id = ?");
    $del_news_stmt->bind_param("i", $news_id);
    $del_news_stmt->execute();
    
    if ($del_news_stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Nyheden blev slettet!";
    } else {
        throw new Exception("Nyheden kunne ikke slettes");
    }
    
    // Commit transaktionen
    $conn->commit();
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i delete-news.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til news-oversigten
header("Location: ./");
exit;
?>