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

// Tjek login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind';
    echo json_encode($response);
    exit;
}

// Tjek request metode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Ugyldig request metode';
    echo json_encode($response);
    exit;
}

// Tjek påkrævede felter
if (!isset($_POST['message']) || !isset($_POST['recipient_id']) || !isset($_POST['recipient_type'])) {
    $response['message'] = 'Manglende påkrævede felter';
    echo json_encode($response);
    exit;
}

$message = trim($_POST['message']);
$recipient_id = (int)$_POST['recipient_id'];
$recipient_type = $_POST['recipient_type'];

// Valider input
if (empty($message)) {
    $response['message'] = 'Besked kan ikke være tom';
    echo json_encode($response);
    exit;
}

if ($recipient_id <= 0) {
    $response['message'] = 'Ugyldig modtager ID';
    echo json_encode($response);
    exit;
}

if (!in_array($recipient_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldig modtagertype';
    echo json_encode($response);
    exit;
}

require_once '../database/db_conn.php';

try {
    // 1. Krypter beskeden
    $encryptionResult = encryptMessage($message);
    
    // 2. Gem besked i databasen med den nye struktur
    $stmt = $conn->prepare("
        INSERT INTO messages (
            sender_id, 
            sender_type, 
            recipient_id, 
            recipient_type, 
            content, 
            encryption_iv, 
            is_encrypted, 
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $sender_id = $_SESSION['user_id'];
    $sender_type = 'staff'; // Antager at alle der bruger admin-panel er personale
    
    $stmt->bind_param("isisss", 
        $sender_id, 
        $sender_type, 
        $recipient_id, 
        $recipient_type, 
        $encryptionResult['encrypted'], 
        $encryptionResult['iv']
    );
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Besked sendt';
        $response['message_id'] = $conn->insert_id;
    } else {
        $response['message'] = 'Kunne ikke gemme besked: ' . $stmt->error;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Fejl: ' . $e->getMessage();
}

echo json_encode($response);
exit;

// Sikker krypteringsmetode der returnerer både krypteret indhold og IV
function encryptMessage($message) {
    // Brug en stærk krypteringsnøgle - i produktion burde den gemmes sikkert, f.eks. i en miljøvariabel
    $key = "Mercantec2025KollegieHemmeligKrypteringsNogle"; 
    
    // Generer en tilfældig IV (Initialization Vector)
    $ivLength = 16; // 16 bytes for AES
    $iv = openssl_random_pseudo_bytes($ivLength);
    
    // Krypter med AES-256-CBC
    $encrypted = openssl_encrypt(
        $message,               // Data der skal krypteres
        'AES-256-CBC',          // Krypteringsalgoritme
        $key,                   // Krypteringsnøgle
        0,                      // Optioner (0 = padding er påkrævet)
        $iv                     // Initialization Vector
    );
    
    // Returner både krypteret indhold og IV (begge som base64)
    return [
        'encrypted' => $encrypted,
        'iv' => bin2hex($iv)    // Konverter binær IV til hex for sikker lagring
    ];
}