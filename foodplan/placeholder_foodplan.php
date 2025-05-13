<?php
// Start session
session_start();

// Fejlhåndtering
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database forbindelse
include '../database/db_conn.php';

// Simpel fejl-logger
function logError($message) {
    error_log($message, 0);
    $_SESSION['error_message'] = "Der opstod en fejl. Tjek venligst server log for detaljer.";
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

try {
    // Tjek om databaseforbindelsen er tilgængelig
    if (!isset($conn)) {
        throw new Exception("Database forbindelse fejlede");
    }
    
    // Hent uge og år fra URL-parametre
    $week_number = isset($_GET['week']) ? (int)$_GET['week'] : null;
    $year = isset($_GET['year']) ? (int)$_GET['year'] : null;
    
    if (!$week_number || !$year) {
        throw new Exception("Manglende uge eller år");
    }
    
    // Standard madplan data
    $placeholderMeals = [
        'monday' => [
            'dish' => 'Pasta Bolognese',
            'description' => 'Hjemmelavet pasta med kødsauce og friskrevet parmesan.',
            'vegetarian' => 0,
            'allergens' => [1, 2] // Gluten, Laktose
        ],
        'tuesday' => [
            'dish' => 'Vegetarisk Wok',
            'description' => 'Friske grøntsager og tofu stegt i wok med teriyaki sauce og jasminris.',
            'vegetarian' => 1,
            'allergens' => [5] // Soja
        ],
        'wednesday' => [
            'dish' => 'Fiskefrikadeller',
            'description' => 'Hjemmelavede fiskefrikadeller med kartofler, remoulade og gulerodssalat.',
            'vegetarian' => 0,
            'allergens' => [4, 6] // Æg, Fisk
        ],
        'thursday' => [
            'dish' => 'Kylling i karry',
            'description' => 'Mørt kyllingebryst i cremet karrysauce med ris og mangochutney.',
            'vegetarian' => 0,
            'allergens' => [2] // Laktose
        ]
    ];
    
    // Start en transaktion
    $conn->begin_transaction();
    
    // Tjek om der allerede findes en madplan for denne uge
    $check_stmt = $conn->prepare("SELECT * FROM foodplan WHERE week_number = ? AND year = ?");
    $check_stmt->bind_param("ii", $week_number, $year);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows == 0) {
        // Opret en ny madplan med standarddata
        $stmt = $conn->prepare("INSERT INTO foodplan (
            week_number, year, 
            monday_dish, monday_description, monday_vegetarian,
            tuesday_dish, tuesday_description, tuesday_vegetarian,
            wednesday_dish, wednesday_description, wednesday_vegetarian,
            thursday_dish, thursday_description, thursday_vegetarian
        ) VALUES (
            ?, ?, 
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?
        )");
        
        $stmt->bind_param("iissississisi", 
            $week_number, $year, 
            $placeholderMeals['monday']['dish'], $placeholderMeals['monday']['description'], $placeholderMeals['monday']['vegetarian'],
            $placeholderMeals['tuesday']['dish'], $placeholderMeals['tuesday']['description'], $placeholderMeals['tuesday']['vegetarian'],
            $placeholderMeals['wednesday']['dish'], $placeholderMeals['wednesday']['description'], $placeholderMeals['wednesday']['vegetarian'],
            $placeholderMeals['thursday']['dish'], $placeholderMeals['thursday']['description'], $placeholderMeals['thursday']['vegetarian']
        );
        
        $stmt->execute();
        $foodplan_id = $conn->insert_id;
    } else {
        // Opdater eksisterende madplan med standarddata
        $row = $check_result->fetch_assoc();
        $foodplan_id = $row['id'];
        
        $stmt = $conn->prepare("UPDATE foodplan SET 
            monday_dish = ?, monday_description = ?, monday_vegetarian = ?,
            tuesday_dish = ?, tuesday_description = ?, tuesday_vegetarian = ?,
            wednesday_dish = ?, wednesday_description = ?, wednesday_vegetarian = ?,
            thursday_dish = ?, thursday_description = ?, thursday_vegetarian = ?
            WHERE id = ?");
            
        $stmt->bind_param("ssissississii", 
            $placeholderMeals['monday']['dish'], $placeholderMeals['monday']['description'], $placeholderMeals['monday']['vegetarian'],
            $placeholderMeals['tuesday']['dish'], $placeholderMeals['tuesday']['description'], $placeholderMeals['tuesday']['vegetarian'],
            $placeholderMeals['wednesday']['dish'], $placeholderMeals['wednesday']['description'], $placeholderMeals['wednesday']['vegetarian'],
            $placeholderMeals['thursday']['dish'], $placeholderMeals['thursday']['description'], $placeholderMeals['thursday']['vegetarian'],
            $foodplan_id
        );
        
        $stmt->execute();
        
        // Slet eksisterende allergener først
        $del_stmt = $conn->prepare("DELETE FROM foodplan_allergens WHERE foodplan_id = ?");
        $del_stmt->bind_param("i", $foodplan_id);
        $del_stmt->execute();
    }
    
    // Tilføj allergener for hver dag
    $insert_stmt = $conn->prepare("INSERT INTO foodplan_allergens (foodplan_id, allergen_id, day_of_week) VALUES (?, ?, ?)");
    
    foreach ($placeholderMeals as $day => $meal) {
        if (isset($meal['allergens']) && is_array($meal['allergens'])) {
            foreach ($meal['allergens'] as $allergen_id) {
                $insert_stmt->bind_param("iis", $foodplan_id, $allergen_id, $day);
                $insert_stmt->execute();
            }
        }
    }
    
    // Commit transaktionen
    $conn->commit();
    
    // Success
    $_SESSION['success_message'] = "Standardmadplan blev indsat for uge {$week_number}.";
    
} catch (Exception $e) {
    // Ved fejl: Rollback og log fejl
    if (isset($conn)) {
        $conn->rollback();
    }
    logError("Fejl i placeholder_foodplan.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til madplan siden
header("Location: ./index.php?week={$week_number}&year={$year}");
exit;
?>