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

// Tjek om bruger er logget ind
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind for at opdatere beskeder';
    echo json_encode($response);
    exit;
}

// Tjek om det er en POST request med påkrævede felter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['message_id']) || !isset($_POST['content'])) {
    $response['message'] = 'Ugyldig anmodning eller manglende felter';
    echo json_encode($response);
    exit;
}

require_once '../database/db_conn.php';

$message_id = (int)$_POST['message_id'];
$content = trim($_POST['content']);
$user_id = $_SESSION['user_id'];

// Validér input
if (empty($content)) {
    $response['message'] = 'Beskedindholdet kan ikke være tomt';
    echo json_encode($response);
    exit;
}

if ($message_id <= 0) {
    $response['message'] = 'Ugyldig besked-ID';
    echo json_encode($response);
    exit;
}

try {
    // Tjek om brugeren ejer beskeden
    $stmt = $conn->prepare("
        SELECT id FROM messages 
        WHERE id = ? AND sender_id = ? AND sender_type = 'staff'
    ");
    $stmt->bind_param("ii", $message_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'Du har ikke tilladelse til at redigere denne besked';
        echo json_encode($response);
        exit;
    }

    // Kryptér det nye indhold
    // Generer en Initialization Vector (IV)
    $ivLength = 16; // 16 bytes for AES
    $iv = openssl_random_pseudo_bytes($ivLength);
    $iv_hex = bin2hex($iv);

    // Krypteringsnøgle
    $key = "Mercantec2025KollegieHemmeligKrypteringsNogle";

    // Kryptér med AES-256-CBC
    $encryptedContent = openssl_encrypt(
        $content,              // Data der skal krypteres
        'AES-256-CBC',         // Krypteringsalgoritme
        $key,                  // Krypteringsnøgle
        0,                     // Optioner (0 = padding er påkrævet)
        $iv                    // Initialization Vector
    );

    // Opdater beskeden i databasen - UDEN updated_at kolonne
    $stmt = $conn->prepare("
        UPDATE messages 
        SET content = ?, encryption_iv = ? 
        WHERE id = ? AND sender_id = ? AND sender_type = 'staff'
    ");
    $stmt->bind_param("ssii", $encryptedContent, $iv_hex, $message_id, $user_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Besked opdateret';
    } else {
        $response['message'] = 'Kunne ikke opdatere besked: ' . $stmt->error;
    }
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
}

echo json_encode($response);
exit;
