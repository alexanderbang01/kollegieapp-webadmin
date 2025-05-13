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
    
    // Hent formdata
    $title = isset($_POST['title']) ? trim($_POST['title']) : null;
    $content = isset($_POST['content']) ? trim($_POST['content']) : null;
    // Fjern is_important da kolonnen ikke eksisterer i databasen
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
    
    if ($news_id) {
        // Opdater eksisterende nyhed - fjern is_important fra SQL
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
        
        // Indsæt ny nyhed - fjern is_important fra SQL
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