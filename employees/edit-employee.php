<?php
// Start session
session_start();

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

// Tjek om bruger er logget ind og er administrator
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Administrator') {
    $response['message'] = 'Du har ikke tilladelse til at redigere medarbejdere';
    echo json_encode($response);
    exit;
}

// Tjek om det er en POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Kun POST requests er tilladt';
    echo json_encode($response);
    exit;
}

// Database forbindelse
require_once '../database/db_conn.php';

try {
    // Hent formdata
    $employee_id = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : null;
    $name = isset($_POST['name']) ? trim($_POST['name']) : null;
    $email = isset($_POST['email']) ? trim($_POST['email']) : null;
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $profession = isset($_POST['profession']) ? trim($_POST['profession']) : null;
    $role = isset($_POST['role']) ? trim($_POST['role']) : null;

    // Valider påkrævede felter
    if (!$employee_id || !$name || !$email || !$role) {
        throw new Exception("Medarbejder-ID, navn, email og rolle skal udfyldes");
    }

    // Valider email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Indtast en gyldig email-adresse");
    }

    // Valider rolle
    if (!in_array($role, ['Administrator', 'Personale'])) {
        throw new Exception("Ugyldig rolle");
    }

    // Tjek at medarbejderen eksisterer og ikke er den nuværende bruger selv
    if ($employee_id == $_SESSION['user_id']) {
        throw new Exception("Du kan ikke redigere din egen bruger her. Brug indstillinger i stedet.");
    }

    // Tjek at email ikke allerede er i brug af en anden bruger
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        throw new Exception("Email-adressen er allerede i brug af en anden bruger");
    }

    // Håndter fil upload
    $profile_image_filename = null;
    $current_profile_image = null;

    // Hent nuværende profilbillede
    $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $current_user = $result->fetch_assoc();
        $current_profile_image = $current_user['profile_image'];
    }

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
        $profile_image_filename = 'profile_' . $employee_id . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $profile_image_filename;

        // Flyt uploadet fil
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            throw new Exception("Kunne ikke uploade billedet");
        }

        // Generer fuld URL til billedet
        $base_url = "http://localhost/kollegieapp-webadmin/employees/images/";
        $profile_image_filename = $base_url . $profile_image_filename;

        // Slet det gamle billede hvis det eksisterer og er anderledes
        if (!empty($current_profile_image) && $current_profile_image !== $profile_image_filename) {
            // Hvis det er en fuld URL, ekstrahér kun filnavnet
            if (filter_var($current_profile_image, FILTER_VALIDATE_URL)) {
                $old_filename = basename($current_profile_image);
                $old_image_path = $upload_dir . $old_filename;
            } else {
                $old_image_path = $upload_dir . $current_profile_image;
            }

            if (file_exists($old_image_path)) {
                unlink($old_image_path);
            }
        }
    }

    // Start en transaktion
    $conn->begin_transaction();

    // Opdater medarbejder
    if ($profile_image_filename) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, profession = ?, role = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $email, $phone, $profession, $role, $profile_image_filename, $employee_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, profession = ?, role = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $profession, $role, $employee_id);
    }

    $stmt->execute();

    if ($stmt->affected_rows >= 0) { // >= 0 fordi affected_rows kan være 0 hvis ingen ændringer
        // Log aktivitet
        $admin_id = $_SESSION['user_id'];
        $activity_description = "Medarbejder '{$name}' blev redigeret af administrator.";

        $stmt = $conn->prepare("INSERT INTO activities (user_id, activity_type, description) VALUES (?, 'employee_updated', ?)");
        $stmt->bind_param("is", $admin_id, $activity_description);
        $stmt->execute();

        // Commit transaktionen
        $conn->commit();

        $response['success'] = true;
        $response['message'] = 'Medarbejder opdateret succesfuldt';
    } else {
        throw new Exception("Medarbejderen kunne ikke opdateres");
    }
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }

    // Slet uploadet fil hvis der var en fejl
    if (isset($upload_path) && file_exists($upload_path)) {
        unlink($upload_path);
    }

    error_log("Fejl i edit-employee.php: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
