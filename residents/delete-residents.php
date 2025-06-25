<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
function logError($message)
{
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

    // Slet først relaterede records før selve beboeren slettes

    // Slet event_participants registreringer (hvis tabellen findes)
    $tables_to_check = ['event_participants', 'news_reads', 'activities', 'messages', 'notification_reads'];

    foreach ($tables_to_check as $table) {
        $check_table = $conn->query("SHOW TABLES LIKE '$table'");
        if ($check_table->num_rows > 0) {
            // Tjek hvilke kolonner der findes i tabellen
            $columns_result = $conn->query("SHOW COLUMNS FROM $table LIKE 'resident_id'");
            if ($columns_result->num_rows > 0) {
                $del_stmt = $conn->prepare("DELETE FROM $table WHERE resident_id = ?");
                $del_stmt->bind_param("i", $resident_id);
                $del_stmt->execute();
            }
        }
    }

    // Slet også messages hvor beboeren er afsender eller modtager
    $messages_check = $conn->query("SHOW TABLES LIKE 'messages'");
    if ($messages_check->num_rows > 0) {
        $del_messages_stmt = $conn->prepare("DELETE FROM messages WHERE (sender_id = ? AND sender_type = 'resident') OR (recipient_id = ? AND recipient_type = 'resident')");
        $del_messages_stmt->bind_param("ii", $resident_id, $resident_id);
        $del_messages_stmt->execute();
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
