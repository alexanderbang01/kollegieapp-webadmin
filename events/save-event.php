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

// Funktion til at oprette notifikation
function createEventNotification($conn, $event_id, $title, $date, $time, $created_by, $is_new = true) {
    try {
        $notification_title = $is_new ? "Ny begivenhed: $title" : "Begivenhed opdateret: $title";
        $notification_content = $is_new 
            ? "Der er oprettet en ny begivenhed d. $date kl. $time." 
            : "Begivenheden '$title' er blevet opdateret.";
        
        $notification_sql = "INSERT INTO notifications (type, title, content, related_id, created_by) VALUES (?, ?, ?, ?, ?)";
        $notification_stmt = $conn->prepare($notification_sql);
        
        $type = 'event';
        $notification_stmt->bind_param("sssii", 
            $type, 
            $notification_title, 
            $notification_content, 
            $event_id, 
            $created_by
        );
        
        return $notification_stmt->execute();
    } catch (Exception $e) {
        error_log("Fejl ved oprettelse af begivenhedsnotifikation: " . $e->getMessage());
        return false;
    }
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
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $location = isset($_POST['location']) ? trim($_POST['location']) : null;
    $date = isset($_POST['date']) ? $_POST['date'] : null;
    $time = isset($_POST['time']) ? $_POST['time'] : null;
    $description = isset($_POST['description']) ? trim($_POST['description']) : null;
    $max_participants = isset($_POST['max_participants']) && !empty($_POST['max_participants']) ? (int)$_POST['max_participants'] : null;
    $created_by = isset($_POST['created_by']) ? (int)$_POST['created_by'] : $_SESSION['user_id'];
    
    // Tjek at påkrævede felter eksisterer
    if (!$title || !$location || !$date || !$time || !$description) {
        throw new Exception("Manglende påkrævede felter");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Tjek om begivenheds-ID er angivet (for redigering)
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $is_new_event = !$event_id;
    
    if ($event_id) {
        // Opdater eksisterende begivenhed
        $sql = "UPDATE events SET 
                title = ?, description = ?, date = ?, time = ?, 
                location = ?, max_participants = ?
                WHERE id = ? AND created_by = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssiii", 
            $title, 
            $description, 
            $date, 
            $time, 
            $location, 
            $max_participants, 
            $event_id,
            $created_by
        );
        
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Opret notifikation for opdateret begivenhed
            createEventNotification($conn, $event_id, $title, $date, $time, $created_by, false);
            $_SESSION['success_message'] = "Begivenheden blev opdateret!";
        } else {
            // Tjek om begivenheden eksisterer, men tilhører en anden bruger
            $check_stmt = $conn->prepare("SELECT id FROM events WHERE id = ?");
            $check_stmt->bind_param("i", $event_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows === 0) {
                throw new Exception("Begivenheden kunne ikke findes");
            } else {
                throw new Exception("Du har ikke tilladelse til at redigere denne begivenhed");
            }
        }
    } else {
        // Indsæt ny begivenhed
        $sql = "INSERT INTO events (title, description, date, time, location, max_participants, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssii", 
            $title, 
            $description, 
            $date, 
            $time, 
            $location, 
            $max_participants, 
            $created_by
        );
        
        $stmt->execute();
        
        if ($stmt->affected_rows === 1) {
            // Hent det nye event ID
            $new_event_id = $conn->insert_id;
            
            // Opret notifikation for ny begivenhed
            createEventNotification($conn, $new_event_id, $title, $date, $time, $created_by, true);
            
            $_SESSION['success_message'] = "Begivenheden blev oprettet!";
        } else {
            throw new Exception("Begivenheden kunne ikke gemmes");
        }
    }
    
    // Commit transaktionen
    $conn->commit();
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i save-event.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til events-oversigten
header("Location: ./");
exit;
?>