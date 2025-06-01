<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tjek om bruger er logget ind
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

// Tjek om den nødvendige POST-data er angivet
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['employee_id']) || !is_numeric($_POST['employee_id'])) {
    $_SESSION['error_message'] = 'Ugyldig anmodning';
    header("Location: index.php");
    exit();
}

$employee_id = (int)$_POST['employee_id'];

// Hent database forbindelse
require_once '../database/db_conn.php';

if ($conn) {
    // Tjek først om medarbejderen eksisterer og ikke er den nuværende bruger
    if ($employee_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = 'Du kan ikke slette din egen bruger';
        header("Location: index.php");
        exit();
    }

    // Slet medarbejdere fra users tabellen
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role IN ('Administrator', 'Personale')");
    $stmt->bind_param("i", $employee_id);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $_SESSION['success_message'] = 'Medarbejder blev slettet';
    } else {
        $_SESSION['error_message'] = 'Kunne ikke slette medarbejderen eller medarbejderen findes ikke';
    }
} else {
    $_SESSION['error_message'] = 'Databaseforbindelse fejlede';
}

header("Location: index.php");
exit();
?>