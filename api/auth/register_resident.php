<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'available' => false,
    'user_id' => null
];

require_once '../../database/db_conn.php';

// Funktion til at logge aktivitet
function logActivity($conn, $resident_id, $activity_type, $description)
{
    try {
        $activity_sql = "INSERT INTO activities (resident_id, activity_type, description) VALUES (?, ?, ?)";
        $activity_stmt = $conn->prepare($activity_sql);
        $activity_stmt->bind_param("iss", $resident_id, $activity_type, $description);

        return $activity_stmt->execute();
    } catch (Exception $e) {
        error_log("Fejl ved logging af aktivitet: " . $e->getMessage());
        return false;
    }
}

// GET request - tjek email tilgængelighed (værelse tjek fjernet)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['check_email'])) {
        $email = trim(strtolower($_GET['check_email']));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $response['message'] = 'Ugyldig email format';
            echo json_encode($response);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM residents WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $response['success'] = true;
        $response['available'] = ($result->num_rows === 0);
        $response['message'] = $response['available'] ? 'Email tilgængelig' : 'Email optaget';
    } else {
        $response['message'] = 'Ugyldig GET parameter';
    }

    echo json_encode($response);
    exit;
}

// POST request - registrer ny beboer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    // Opdaterede required fields - nødkontakt er ikke længere required
    $requiredFields = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'room_number'
    ];

    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            $response['message'] = "Manglende felt: $field";
            http_response_code(400);
            echo json_encode($response);
            exit;
        }
    }

    $firstName = trim($input['first_name']);
    $lastName = trim($input['last_name']);
    $email = trim(strtolower($input['email']));
    $phone = trim($input['phone']);
    $roomNumber = trim($input['room_number']);

    // Nødkontakt er nu optional
    $contactName = isset($input['contact_name']) ? trim($input['contact_name']) : '';
    $contactPhone = isset($input['contact_phone']) ? trim($input['contact_phone']) : '';

    // Valideringer
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Ugyldig email adresse';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    if (
        !preg_match('/^[a-zA-ZæøåÆØÅ\s]+$/u', $firstName) ||
        !preg_match('/^[a-zA-ZæøåÆØÅ\s]+$/u', $lastName)
    ) {
        $response['message'] = 'Navne må kun indeholde bogstaver';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Opdateret validering for værelsenummer - tillad 0-999
    if (!preg_match('/^\d{1,3}$/', $roomNumber) || (int)$roomNumber < 0 || (int)$roomNumber > 999) {
        $response['message'] = 'Værelsenummer skal være mellem 0 og 999';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    try {
        // Start en transaktion
        $conn->begin_transaction();

        // Tjek kun email duplikater (værelse tjek fjernet)
        $stmt = $conn->prepare("SELECT id FROM residents WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $response['message'] = 'Email er allerede i brug';
            http_response_code(409);
            echo json_encode($response);
            exit;
        }

        // Indsæt ny beboer
        $stmt = $conn->prepare("
           INSERT INTO residents (
               first_name, last_name, email, phone, room_number, 
               contact_name, contact_phone, created_at
           ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
       ");

        $stmt->bind_param(
            "sssssss",
            $firstName,
            $lastName,
            $email,
            $phone,
            $roomNumber,
            $contactName,
            $contactPhone
        );

        if ($stmt->execute()) {
            $newUserId = $conn->insert_id;

            // Log aktivitet for ny registrering
            logActivity($conn, $newUserId, 'user_registered', "Ny beboer registreret: $firstName $lastName");

            // Commit transaktionen
            $conn->commit();

            $response['success'] = true;
            $response['message'] = 'Beboer registreret succesfuldt';
            $response['user_id'] = $newUserId;
            $response['user_data'] = [
                'id' => $newUserId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'room_number' => $roomNumber,
                'user_type' => 'resident'
            ];

            http_response_code(201);
        } else {
            $conn->rollback();
            $response['message'] = 'Kunne ikke registrere beboer';
            http_response_code(500);
        }
    } catch (Exception $e) {
        $conn->rollback();
        $response['message'] = 'Serverfejl: ' . $e->getMessage();
        http_response_code(500);
    }

    echo json_encode($response);
    exit;
}

$response['message'] = 'Ugyldig request metode';
http_response_code(405);
echo json_encode($response);
