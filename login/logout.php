<?php
// Start session hvis den ikke allerede er startet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Unset alle session variabler
$_SESSION = array();

// Slet session cookie, hvis den findes
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Ødelæg sessionen
session_destroy();

// Redirect til login-siden
header("Location: ./");
exit();
?>