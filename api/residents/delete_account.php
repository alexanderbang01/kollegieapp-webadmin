<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    $response['message'] = 'Kun DELETE requests er tilladt';
    echo json_encode($response);
    exit;
}

// Tjek authorization
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    $response['message'] = 'Manglende authorization header';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$auth = str_replace('Bearer ', '', $headers['Authorization']);
$authParts = explode(':', $auth);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

// Kun residents kan slette deres egen konto
if ($user_type !== 'resident') {
    $response['message'] = 'Kun beboere kan slette deres egen konto';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

require_once '../../database/db_conn.php';

try {
    // Start transaktion
    $conn->autocommit(false);

    // Tjek om resident eksisterer
    $stmt = $conn->prepare("SELECT id, name FROM residents WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $response['message'] = 'Beboer ikke fundet';
        http_response_code(404);
        echo json_encode($response);
        exit;
    }

    $user_data = $result->fetch_assoc();
    $user_name = $user_data['name'];

    // Slet beboer-relaterede data

    // Slet event tilmeldinger (event_participants tabel)
    $stmt = $conn->prepare("DELETE FROM event_participants WHERE resident_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Slet læste nyheder
    $stmt = $conn->prepare("DELETE FROM news_reads WHERE resident_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Slet notification reads
    $stmt = $conn->prepare("DELETE FROM notification_reads WHERE resident_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Slet beskeder hvor beboeren er afsender
    $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = ? AND sender_type = 'resident'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Slet beskeder hvor beboeren er modtager
    $stmt = $conn->prepare("DELETE FROM messages WHERE recipient_id = ? AND recipient_type = 'resident'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Slet aktiviteter relateret til denne resident
    $stmt = $conn->prepare("DELETE FROM activities WHERE resident_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Log kontosletningen som aktivitet (før sletning)
    $activity_description = "Beboeren $user_name slettede sin egen konto";
    $stmt = $conn->prepare("INSERT INTO activities (resident_id, activity_type, description) VALUES (?, 'account_deleted', ?)");
    $stmt->bind_param("is", $user_id, $activity_description);
    $stmt->execute();

    // Slet selve beboeren
    $stmt = $conn->prepare("DELETE FROM residents WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception("Kunne ikke slette beboeren");
    }

    // Commit transaktionen
    $conn->commit();

    $response['success'] = true;
    $response['message'] = 'Din konto er blevet slettet';
} catch (Exception $e) {
    // Ved fejl: Rollback
    if (isset($conn)) {
        $conn->rollback();
    }

    error_log("Fejl i delete_account.php: " . $e->getMessage());
    $response['message'] = 'Der opstod en fejl ved sletning af kontoen: ' . $e->getMessage();
    http_response_code(500);
}

echo json_encode($response);
