<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
   http_response_code(200);
   exit();
}

$response = [
   'success' => false,
   'message' => 'Der opstod en fejl'
];

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'])) {
   $response['message'] = 'Kun POST og DELETE requests er tilladt';
   echo json_encode($response);
   exit;
}

// Læs JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Tjek authorization - med fallback for forskellige server setups
$authorization = null;

// Prøv flere måder at få authorization header
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
   $authorization = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
   $authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
   $headers = apache_request_headers();
   if (isset($headers['Authorization'])) {
       $authorization = $headers['Authorization'];
   } elseif (isset($headers['authorization'])) {
       $authorization = $headers['authorization'];
   }
} elseif (function_exists('getallheaders')) {
   $headers = getallheaders();
   if (isset($headers['Authorization'])) {
       $authorization = $headers['Authorization'];
   } elseif (isset($headers['authorization'])) {
       $authorization = $headers['authorization'];
   }
}

if (!$authorization) {
   $response['message'] = 'Manglende authorization header';
   http_response_code(401);
   echo json_encode($response);
   exit;
}

$auth = str_replace('Bearer ', '', $authorization);
$authParts = explode(':', $auth);

if (count($authParts) !== 2) {
   $response['message'] = 'Ugyldig authorization format';
   http_response_code(401);
   echo json_encode($response);
   exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

// Kun residents kan tilmelde sig begivenheder
if ($user_type !== 'resident') {
   $response['message'] = 'Kun beboere kan tilmelde sig begivenheder';
   http_response_code(403);
   echo json_encode($response);
   exit;
}

// Valider input
if (!isset($input['event_id'])) {
   $response['message'] = 'Manglende påkrævet felt: event_id';
   http_response_code(400);
   echo json_encode($response);
   exit;
}

$event_id = (int)$input['event_id'];

if ($event_id <= 0) {
   $response['message'] = 'Ugyldig begivenhed-ID';
   http_response_code(400);
   echo json_encode($response);
   exit;
}

require_once '../../database/db_conn.php';

// Funktion til at logge aktivitet med spam-beskyttelse
function logEventActivity($conn, $resident_id, $event_id, $activity_type, $description) {
   try {
       // Tjek om der allerede er en lignende aktivitet inden for de sidste 5 minutter
       $check_sql = "SELECT id FROM activities 
                     WHERE resident_id = ? 
                     AND activity_type = ? 
                     AND description LIKE ? 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)";
       
       $check_stmt = $conn->prepare($check_sql);
       $description_pattern = "%event ID: $event_id%";
       $check_stmt->bind_param("iss", $resident_id, $activity_type, $description_pattern);
       $check_stmt->execute();
       $check_result = $check_stmt->get_result();
       
       // Hvis der allerede er en lignende aktivitet inden for 5 minutter, log ikke igen
       if ($check_result->num_rows > 0) {
           return true; // Returner success men log ikke
       }
       
       // Log aktiviteten
       $activity_sql = "INSERT INTO activities (resident_id, activity_type, description) VALUES (?, ?, ?)";
       $activity_stmt = $conn->prepare($activity_sql);
       $activity_stmt->bind_param("iss", $resident_id, $activity_type, $description);
       
       return $activity_stmt->execute();
   } catch (Exception $e) {
       error_log("Fejl ved logging af event aktivitet: " . $e->getMessage());
       return false;
   }
}

try {
   // Hent beboer information
   $stmt = $conn->prepare("
       SELECT first_name, last_name 
       FROM residents 
       WHERE id = ?
   ");
   $stmt->bind_param("i", $user_id);
   $stmt->execute();
   $resident_result = $stmt->get_result();
   
   if ($resident_result->num_rows === 0) {
       $response['message'] = 'Beboer ikke fundet';
       http_response_code(404);
       echo json_encode($response);
       exit;
   }
   
   $resident = $resident_result->fetch_assoc();
   $resident_name = $resident['first_name'] . ' ' . $resident['last_name'];
   
   // Tjek om begivenheden eksisterer og ikke er passeret
   $stmt = $conn->prepare("
       SELECT id, title, max_participants, 
              CONCAT(date, ' ', time) >= NOW() as is_future
       FROM events 
       WHERE id = ?
   ");
   $stmt->bind_param("i", $event_id);
   $stmt->execute();
   $result = $stmt->get_result();
   
   if ($result->num_rows === 0) {
       $response['message'] = 'Begivenhed ikke fundet';
       http_response_code(404);
       echo json_encode($response);
       exit;
   }
   
   $event = $result->fetch_assoc();
   
   if (!$event['is_future']) {
       $response['message'] = 'Kan ikke tilmelde sig tidligere begivenheder';
       http_response_code(400);
       echo json_encode($response);
       exit;
   }
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       // TILMELDING
       
       // Start transaktion
       $conn->begin_transaction();
       
       // Tjek om brugeren allerede er tilmeldt
       $stmt = $conn->prepare("
           SELECT event_id FROM event_participants 
           WHERE event_id = ? AND resident_id = ?
       ");
       $stmt->bind_param("ii", $event_id, $user_id);
       $stmt->execute();
       $existing = $stmt->get_result();
       
       if ($existing->num_rows > 0) {
           $response['message'] = 'Du er allerede tilmeldt denne begivenhed';
           http_response_code(409);
           echo json_encode($response);
           exit;
       }
       
       // Tjek om der er plads (hvis max_participants er sat)
       if ($event['max_participants'] !== null) {
           $stmt = $conn->prepare("
               SELECT COUNT(*) as participant_count 
               FROM event_participants 
               WHERE event_id = ?
           ");
           $stmt->bind_param("i", $event_id);
           $stmt->execute();
           $count_result = $stmt->get_result();
           $count_data = $count_result->fetch_assoc();
           
           if ($count_data['participant_count'] >= $event['max_participants']) {
               $response['message'] = 'Begivenheden er fyldt op';
               http_response_code(400);
               echo json_encode($response);
               exit;
           }
       }
       
       // Tilmeld brugeren
       $stmt = $conn->prepare("
           INSERT INTO event_participants (event_id, resident_id, signup_date)
           VALUES (?, ?, NOW())
       ");
       $stmt->bind_param("ii", $event_id, $user_id);
       
       if ($stmt->execute()) {
           // Log aktivitet med spam-beskyttelse
           logEventActivity($conn, $user_id, $event_id, 'event_registration', "$resident_name tilmeldte sig {$event['title']}");
           
           // Commit transaktion
           $conn->commit();
           
           $response['success'] = true;
           $response['message'] = "Du er nu tilmeldt '{$event['title']}'";
           $response['action'] = 'registered';
       } else {
           $conn->rollback();
           $response['message'] = 'Kunne ikke tilmelde dig: ' . $stmt->error;
           http_response_code(500);
       }
       
   } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
       // AFMELDING
       
       // Start transaktion
       $conn->begin_transaction();
       
       // Tjek om brugeren er tilmeldt
       $stmt = $conn->prepare("
           SELECT event_id FROM event_participants 
           WHERE event_id = ? AND resident_id = ?
       ");
       $stmt->bind_param("ii", $event_id, $user_id);
       $stmt->execute();
       $existing = $stmt->get_result();
       
       if ($existing->num_rows === 0) {
           $response['message'] = 'Du er ikke tilmeldt denne begivenhed';
           http_response_code(404);
           echo json_encode($response);
           exit;
       }
       
       // Afmeld brugeren
       $stmt = $conn->prepare("
           DELETE FROM event_participants 
           WHERE event_id = ? AND resident_id = ?
       ");
       $stmt->bind_param("ii", $event_id, $user_id);
       
       if ($stmt->execute()) {
           // Log aktivitet med spam-beskyttelse
           logEventActivity($conn, $user_id, $event_id, 'event_unregistration', "$resident_name afmeldte sig {$event['title']}");
           
           // Commit transaktion
           $conn->commit();
           
           $response['success'] = true;
           $response['message'] = "Du er nu afmeldt '{$event['title']}'";
           $response['action'] = 'unregistered';
       } else {
           $conn->rollback();
           $response['message'] = 'Kunne ikke afmelde dig: ' . $stmt->error;
           http_response_code(500);
       }
   }
   
} catch (Exception $e) {
   if (isset($conn)) {
       $conn->rollback();
   }
   $response['message'] = 'Fejl: ' . $e->getMessage();
   http_response_code(500);
}

echo json_encode($response);
?>