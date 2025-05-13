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
    
    // Hent event_id
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    
    // Tjek at event_id er angivet
    if (!$event_id) {
        throw new Exception("Manglende event_id");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Check om brugeren har ret til at slette begivenheden
    // Administratorer kan slette alle begivenheder, personale kun deres egne
    $user_role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    $can_delete = false;
    
    if ($user_role == 'Administrator') {
        $can_delete = true;
    } else {
        // Tjek om begivenheden tilhører brugeren
        $check_stmt = $conn->prepare("SELECT id FROM events WHERE id = ? AND created_by = ?");
        $check_stmt->bind_param("ii", $event_id, $user_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $can_delete = true;
        }
    }
    
    if (!$can_delete) {
        throw new Exception("Du har ikke tilladelse til at slette denne begivenhed");
    }
    
    // Slet først alle deltagere fra begivenheden (fremmed nøgle)
    $del_participants_stmt = $conn->prepare("DELETE FROM event_participants WHERE event_id = ?");
    $del_participants_stmt->bind_param("i", $event_id);
    $del_participants_stmt->execute();
    
    // Slet derefter begivenheden
    $del_event_stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
    $del_event_stmt->bind_param("i", $event_id);
    $del_event_stmt->execute();
    
    if ($del_event_stmt->affected_rows > 0) {
        $_SESSION['success_message'] = "Begivenheden blev slettet!";
    } else {
        throw new Exception("Begivenheden kunne ikke slettes");
    }
    
    // Commit transaktionen
    $conn->commit();
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i delete-event.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til events-oversigten
header("Location: ./");
exit;
?>