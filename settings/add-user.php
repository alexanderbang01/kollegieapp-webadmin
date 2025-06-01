<?php
// Start session
session_start();

// Fejlhåndtering
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Tjek om bruger er logget ind og er administrator, ellers redirect til login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
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
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $username = isset($_POST['username']) ? trim($_POST['username']) : null;
    $password = isset($_POST['password']) ? trim($_POST['password']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $profession = isset($_POST['profession']) ? trim($_POST['profession']) : null;
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'Personale';
    
    // Tjek at påkrævede felter eksisterer
    if (!$name || !$email || !$username || !$password) {
        throw new Exception("Navn, email, brugernavn og adgangskode skal udfyldes");
    }
    
    // Tjek at adgangskoden er mindst 8 tegn
    if (strlen($password) < 8) {
        throw new Exception("Adgangskoden skal være mindst 8 tegn");
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Indtast en gyldig email-adresse");
    }
    
    // Tjek at rollen er valid
    if ($role !== 'Administrator' && $role !== 'Personale') {
        throw new Exception("Ugyldig rolle");
    }
    
    // Tjek at brugernavn er unikt
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Brugernavnet er allerede i brug");
    }
    
    // Tjek at email er unik
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
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
        $profile_image_filename = 'profile_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $profile_image_filename;
        
        // Flyt uploadet fil
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            throw new Exception("Kunne ikke uploade billedet");
        }
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Indsæt ny bruger
    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, phone, profession, role, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $name, $email, $username, $hashed_password, $phone, $profession, $role, $profile_image_filename);
    $stmt->execute();
    
    if ($stmt->affected_rows !== 1) {
        throw new Exception("Brugeren kunne ikke oprettes");
    }
    
    // Log aktivitet
    $user_id = $conn->insert_id;
    $admin_id = $_SESSION['user_id'];
    $activity_description = "Bruger '{$username}' ({$role}) er blevet oprettet af administrator.";
    
    $stmt = $conn->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'user_created', ?)");
    $stmt->bind_param("is", $admin_id, $activity_description);
    $stmt->execute();
    
    // Commit transaktionen
    $conn->commit();
    
    // Success
    $_SESSION['success_message'] = "Brugeren '{$name}' er blevet oprettet!";
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    
    // Slet uploadet fil hvis der var en fejl
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }
    
    logError("Fejl i add-user.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til settings siden
header("Location: ./");
exit;
?>