<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

// Tjek om det er en POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../database/db_conn.php';

    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        $response['message'] = 'Brugernavn og adgangskode er påkrævet';
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Validate password with password_verify
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                // Gem profilbillede URL i session (enten fuld URL eller konverter gammel sti)
                if (!empty($user['profile_image'])) {
                    // Tjek om det allerede er en fuld URL
                    if (strpos($user['profile_image'], 'http') === 0) {
                        $_SESSION['profile_image'] = $user['profile_image'];
                    } else {
                        // Konverter gammelt filnavn til fuld URL
                        $_SESSION['profile_image'] = 'http://localhost/kollegieapp-webadmin/employees/images/' . $user['profile_image'];
                    }
                } else {
                    $_SESSION['profile_image'] = null;
                }

                $response['success'] = true;
                $response['message'] = 'Login succesfuld';
            } else {
                $response['message'] = 'Forkert brugernavn eller adgangskode';
            }
        } else {
            $response['message'] = 'Forkert brugernavn eller adgangskode';
        }

        $stmt->close();
    }
}

// Returner JSON response
echo json_encode($response);
exit;
