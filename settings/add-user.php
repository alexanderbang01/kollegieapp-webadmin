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
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'Personale';
    
    // Tjek at påkrævede felter eksisterer
    if (!$name || !$email || !$username || !$password) {
        throw new Exception("Manglende påkrævede felter");
    }
    
    // Tjek at adgangskoden er mindst 8 tegn
    if (strlen($password) < 8) {
        throw new Exception("Adgangskoden skal være mindst 8 tegn");
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
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Indsæt ny bruger
    $stmt = $conn->prepare("INSERT INTO users (name, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $email, $username, $hashed_password, $role);
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
    logError("Fejl i add-user.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til settings siden
header("Location: ./");
exit;
?>