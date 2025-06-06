<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [
    'success' => false,
    'message' => 'Der opstod en fejl'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Kun POST requests er tilladt';
    echo json_encode($response);
    exit;
}

// Tjek authorization
$authorization = null;

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

$authParts = explode(':', $authorization);

if (count($authParts) !== 2) {
    $response['message'] = 'Ugyldig authorization format';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

$user_id = (int)$authParts[0];
$user_type = $authParts[1];

if ($user_type !== 'resident') {
    $response['message'] = 'Kun beboere kan uploade profilbilleder';
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// Læs JSON input
$input = json_decode(file_get_contents('php://input'), true);

if ($input === null || !isset($input['image_data'])) {
    $response['message'] = 'Manglende billede data';
    http_response_code(400);
    echo json_encode($response);
    exit;
}

try {
    // Decode base64 billede
    $image_data = $input['image_data'];
    $file_extension = isset($input['file_extension']) ? $input['file_extension'] : 'jpg';
    
    // Fjern data:image/...;base64, prefix hvis det findes
    if (strpos($image_data, ',') !== false) {
        $image_data = explode(',', $image_data)[1];
    }
    
    $decoded_image = base64_decode($image_data);
    
    if ($decoded_image === false) {
        throw new Exception('Kunne ikke dekode billede data');
    }
    
    // Valider filstørrelse (max 5MB)
    $max_file_size = 5 * 1024 * 1024; // 5MB
    if (strlen($decoded_image) > $max_file_size) {
        $response['message'] = 'Billedet må maksimalt være 5MB';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }
    
    // Opret upload directory - specifikt til XAMPP på macOS
    $document_root = $_SERVER['DOCUMENT_ROOT'];
    $upload_dir = $document_root . '/kollegieapp-webadmin/residents/images/';
    
    // Opret mappe hvis den ikke eksisterer
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Kunne ikke oprette upload directory: ' . $upload_dir . ' - Check rettigheder');
        }
    }
    
    // Sæt rettigheder på mappen
    chmod($upload_dir, 0755);
    
    // Check om directory er skrivbart
    if (!is_writable($upload_dir)) {
        // Prøv at ændre rettigheder
        chmod($upload_dir, 0777);
        if (!is_writable($upload_dir)) {
            throw new Exception('Upload directory er ikke skrivbart: ' . $upload_dir . ' - Kør: chmod 777 ' . $upload_dir);
        }
    }
    
    // Generer tilfældigt filnavn uden user ID
    $random_number = mt_rand(10000000, 99999999);
    $new_filename = 'resident_' . $random_number . '.' . $file_extension;
    
    // Tjek om filnavnet allerede eksisterer, generer nyt hvis det gør
    while (file_exists($upload_dir . $new_filename)) {
        $random_number = mt_rand(10000000, 99999999);
        $new_filename = 'resident_' . $random_number . '.' . $file_extension;
    }
    
    $file_path = $upload_dir . $new_filename;
    
    // Gem billedet
    $bytes_written = file_put_contents($file_path, $decoded_image);
    if ($bytes_written === false) {
        throw new Exception('file_put_contents() fejlede - kan ikke skrive til: ' . $file_path);
    }
    
    // Sæt rettigheder på filen
    chmod($file_path, 0644);
    
    // Verify file was actually created
    if (!file_exists($file_path)) {
        throw new Exception('Billedfil blev ikke oprettet: ' . $file_path);
    }
    
    require_once '../../database/db_conn.php';
    
    // Opdater database med korrekt billedsti (uden projekt-specifik del)
    $image_url = '/residents/images/' . $new_filename;
    
    // Hent og slet gammelt profilbillede før opdatering
    $old_image_stmt = $conn->prepare("SELECT profile_image FROM residents WHERE id = ?");
    $old_image_stmt->bind_param("i", $user_id);
    $old_image_stmt->execute();
    $old_result = $old_image_stmt->get_result();
    $old_image = null;
    
    if ($old_row = $old_result->fetch_assoc()) {
        $old_image = $old_row['profile_image'];
    }
    
    $stmt = $conn->prepare("
        UPDATE residents SET 
            profile_image = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    
    $stmt->bind_param("si", $image_url, $user_id);
    
    if ($stmt->execute()) {
        // Slet gammelt billede hvis det eksisterer og er forskelligt fra det nye
        if ($old_image && $old_image !== $image_url) {
            $old_file_path = $document_root . '/kollegieapp-webadmin' . $old_image;
            if (file_exists($old_file_path)) {
                unlink($old_file_path);
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Profilbillede uploaded succesfuldt';
        $response['image_url'] = $image_url;
    } else {
        // Slet uploaded fil hvis database opdatering fejler
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        throw new Exception('Kunne ikke opdatere database: ' . $conn->error);
    }
    
} catch (Exception $e) {
    // Slet uploaded fil hvis der opstår en fejl
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    $response['message'] = 'Serverfejl: ' . $e->getMessage();
    $response['debug'] = [
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'upload_dir' => isset($upload_dir) ? $upload_dir : 'not set',
        'upload_dir_exists' => isset($upload_dir) ? is_dir($upload_dir) : false,
        'upload_dir_writable' => isset($upload_dir) ? is_writable($upload_dir) : false,
        'current_user' => get_current_user(),
        'file_permissions' => isset($upload_dir) && is_dir($upload_dir) ? substr(sprintf('%o', fileperms($upload_dir)), -4) : 'unknown'
    ];
    http_response_code(500);
}

echo json_encode($response);
?>