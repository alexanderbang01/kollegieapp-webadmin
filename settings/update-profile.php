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
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $profession = isset($_POST['profession']) ? trim($_POST['profession']) : null;
    $user_id = $_SESSION['user_id'];
    
    // Tjek at påkrævede felter er udfyldt
    if (!$name || !$username || !$email) {
        throw new Exception("Navn, brugernavn og email skal udfyldes");
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Indtast en gyldig email-adresse");
    }
    
    // Tjek at brugernavnet ikke allerede er i brug af en anden bruger
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Brugernavnet er allerede i brug");
    }
    
    // Tjek at email ikke allerede er i brug af en anden bruger
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Email-adressen er allerede i brug");
    }
    
    // Håndter fil upload
    $profile_image_filename = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../employees/images/';
        
        // Opret mappe hvis den ikke eksisterer
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0755, true)) {
                throw new Exception("Kunne ikke oprette upload mappe");
            }
        }
        
        // Valider filtype
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['profile_image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Kun JPEG, PNG, GIF og WebP billeder er tilladt");
        }
        
        // Valider filstørrelse (max 5MB)
        if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            throw new Exception("Billedet må maksimalt være 5MB");
        }
        
        // Generer unikt filnavn
        $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $profile_image_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $profile_image_filename;
        
        // Flyt uploadet fil
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            throw new Exception("Kunne ikke uploade billedet");
        }
        
        // Slet det gamle billede hvis det eksisterer
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_user = $result->fetch_assoc();
        
        if (!empty($current_user['profile_image'])) {
            $old_image_path = $upload_dir . $current_user['profile_image'];
            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
    }
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Opdater profil
    if ($profile_image_filename) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, phone = ?, profession = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $username, $email, $phone, $profession, $profile_image_filename, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, email = ?, phone = ?, profession = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $username, $email, $phone, $profession, $user_id);
    }
    
    $stmt->execute();
    
    if ($stmt->affected_rows < 0) {
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
    
    // Slet uploadet fil hvis der var en fejl
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    logError("Fejl i update-profile.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til settings siden
header("Location: ./");
exit;
?>