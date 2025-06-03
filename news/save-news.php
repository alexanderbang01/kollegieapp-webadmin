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
function createNewsNotification($conn, $news_id, $title, $created_by, $is_new = true, $is_featured = false) {
    try {
        $notification_title = $is_new ? "Ny nyhed: $title" : "Nyhed opdateret: $title";
        
        if ($is_new && $is_featured) {
            $notification_content = "Der er udgivet en vigtig nyhed: '$title'. Læs mere i nyheder.";
        } elseif ($is_new) {
            $notification_content = "Der er udgivet en ny nyhed. Læs mere i nyheder.";
        } else {
            $notification_content = "Nyheden '$title' er blevet opdateret.";
        }
        
        $notification_sql = "INSERT INTO notifications (type, title, content, related_id, created_by) VALUES (?, ?, ?, ?, ?)";
        $notification_stmt = $conn->prepare($notification_sql);
        
        $type = 'news';
        $notification_stmt->bind_param("sssii", 
            $type, 
            $notification_title, 
            $notification_content, 
            $news_id, 
            $created_by
        );
        
        return $notification_stmt->execute();
    } catch (Exception $e) {
        error_log("Fejl ved oprettelse af nyhedsnotifikation: " . $e->getMessage());
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
    $content = isset($_POST['content']) ? trim($_POST['content']) : null;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $created_by = isset($_POST['created_by']) ? (int)$_POST['created_by'] : $_SESSION['user_id'];
    
    // Tjek at påkrævede felter eksisterer
    if (!$title || !$content) {
        throw new Exception("Manglende påkrævede felter");
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Tjek om nyhed ID er angivet (for redigering)
    $news_id = isset($_POST['news_id']) ? (int)$_POST['news_id'] : null;
    $is_new_news = !$news_id;
    
    if ($news_id) {
        // Opdater eksisterende nyhed
        $sql = "UPDATE news SET 
                title = ?, content = ?, is_featured = ?
                WHERE id = ? AND created_by = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", 
            $title, 
            $content, 
            $is_featured, 
            $news_id,
            $created_by
        );
        
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Opret notifikation for opdateret nyhed
            createNewsNotification($conn, $news_id, $title, $created_by, false, $is_featured);
            $_SESSION['success_message'] = "Nyheden blev opdateret!";
        } else {
            // Tjek om nyheden eksisterer, men tilhører en anden bruger
            $check_stmt = $conn->prepare("SELECT id FROM news WHERE id = ?");
            $check_stmt->bind_param("i", $news_id);
            $check_stmt->execute();
            
            if ($check_stmt->get_result()->num_rows === 0) {
                throw new Exception("Nyheden kunne ikke findes");
            } else {
                throw new Exception("Du har ikke tilladelse til at redigere denne nyhed");
            }
        }
    } else {
        // Hvis en anden nyhed allerede er fremhævet og denne også skal fremhæves
        if ($is_featured) {
            // Fjern fremhævning fra alle andre nyheder
            $update_sql = "UPDATE news SET is_featured = 0 WHERE is_featured = 1";
            $conn->query($update_sql);
        }
        
        // Indsæt ny nyhed
        $sql = "INSERT INTO news (title, content, is_featured, created_by) 
                VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", 
            $title, 
            $content,
            $is_featured,  
            $created_by
        );
        
        $stmt->execute();
        
        if ($stmt->affected_rows === 1) {
            // Hent det nye news ID
            $new_news_id = $conn->insert_id;
            
            // Opret notifikation for ny nyhed
            createNewsNotification($conn, $new_news_id, $title, $created_by, true, $is_featured);
            
            $_SESSION['success_message'] = "Nyheden blev oprettet!";
        } else {
            throw new Exception("Nyheden kunne ikke gemmes");
        }
    }
    
    // Commit transaktionen
    $conn->commit();
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i save-news.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til news-oversigten
header("Location: ./");
exit;
?>