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

// Funktion til at logge aktivitet
function logActivity($conn, $user_id, $activity_type, $description) {
   try {
       $activity_sql = "INSERT INTO activities (user_id, activity_type, description) VALUES (?, ?, ?)";
       $activity_stmt = $conn->prepare($activity_sql);
       $activity_stmt->bind_param("iss", $user_id, $activity_type, $description);
       
       return $activity_stmt->execute();
   } catch (Exception $e) {
       error_log("Fejl ved logging af aktivitet: " . $e->getMessage());
       return false;
   }
}

try {
   // Tjek om det er en POST request
   if ($_SERVER['REQUEST_METHOD'] != 'POST') {
       throw new Exception("Kun POST requests er tilladt");
   }
   
   // Tjek om databaseforbindelsen er tilgængelig
   if (!isset($conn)) {
       throw new Exception("Database forbindelse fejlede");
   }
   
   // Hent formdata
   $week_number = isset($_POST['week_number']) ? $_POST['week_number'] : null;
   $year = isset($_POST['year']) ? $_POST['year'] : null;
   $foodplan_id = isset($_POST['foodplan_id']) ? $_POST['foodplan_id'] : '';
   $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
   
   // Tjek at påkrævede felter eksisterer
   if (!$week_number || !$year || !$user_id) {
       throw new Exception("Manglende påkrævede felter");
   }
   
   // Start en transaktion
   $conn->begin_transaction();
   
   // Tjek om der allerede findes en madplan for denne uge
   $check_stmt = $conn->prepare("SELECT * FROM foodplan WHERE week_number = ? AND year = ?");
   $check_stmt->bind_param("ii", $week_number, $year);
   $check_stmt->execute();
   $check_result = $check_stmt->get_result();
   
   $is_new_foodplan = false;
   
   if ($check_result->num_rows == 0) {
       // Opret en ny tom madplan
       $stmt = $conn->prepare("INSERT INTO foodplan (week_number, year, monday_dish, tuesday_dish, wednesday_dish, thursday_dish, monday_vegetarian, tuesday_vegetarian, wednesday_vegetarian, thursday_vegetarian) VALUES (?, ?, '', '', '', '', 0, 0, 0, 0)");
       $stmt->bind_param("ii", $week_number, $year);
       $stmt->execute();
       $foodplan_id = $conn->insert_id;
       $is_new_foodplan = true;
   } else {
       $row = $check_result->fetch_assoc();
       $foodplan_id = $row['id'];
   }
   
   // Hent den aktuelle madplan igen
   $stmt = $conn->prepare("SELECT * FROM foodplan WHERE id = ?");
   $stmt->bind_param("i", $foodplan_id);
   $stmt->execute();
   $result = $stmt->get_result();
   $foodplan = $result->fetch_assoc();
   
   // Opdater hver dag individuelt, men kun hvis den er angivet i formularen
   $days = ['monday', 'tuesday', 'wednesday', 'thursday'];
   $has_updates = false;
   
   foreach ($days as $day) {
       $dish_key = $day . '_dish';
       
       if (isset($_POST[$dish_key]) && $_POST[$dish_key] !== '') {
           // Denne dag er blev sendt i formularen - opdater alle dens felter
           $dish = $_POST[$dish_key];
           $description = isset($_POST[$day . '_description']) ? $_POST[$day . '_description'] : '';
           $vegetarian = isset($_POST[$day . '_vegetarian']) ? 1 : 0;
           
           $sql = "UPDATE foodplan SET 
               {$day}_dish = ?,
               {$day}_description = ?,
               {$day}_vegetarian = ?
               WHERE id = ?";
               
           $stmt = $conn->prepare($sql);
           $stmt->bind_param("ssii", $dish, $description, $vegetarian, $foodplan_id);
           $stmt->execute();
           
           // Håndter allergener for denne dag
           $del_stmt = $conn->prepare("DELETE FROM foodplan_allergens WHERE foodplan_id = ? AND day_of_week = ?");
           $del_stmt->bind_param("is", $foodplan_id, $day);
           $del_stmt->execute();
           
           if (isset($_POST[$day . '_allergens']) && is_array($_POST[$day . '_allergens'])) {
               $insert_stmt = $conn->prepare("INSERT INTO foodplan_allergens (foodplan_id, allergen_id, day_of_week) VALUES (?, ?, ?)");
               foreach ($_POST[$day . '_allergens'] as $allergen_id) {
                   $insert_stmt->bind_param("iis", $foodplan_id, $allergen_id, $day);
                   $insert_stmt->execute();
               }
           }
           
           $has_updates = true;
       }
   }
   
   // Log aktivitet for madplan opdatering
   if ($has_updates) {
       $activity_description = "Opdaterede madplan for uge $week_number, $year.";
       logActivity($conn, $user_id, 'foodplan_updated', $activity_description);
   }
   
   // Commit transaktionen
   $conn->commit();
   
   // Success
   $_SESSION['success_message'] = "Madplanen blev gemt.";
   
} catch (Exception $e) {
   // Ved fejl: Rollback og log fejl
   if (isset($conn)) {
       $conn->rollback();
   }
   logError("Fejl i save_foodplan.php: " . $e->getMessage());
   $_SESSION['error_message'] = "Der opstod en fejl: " . $e->getMessage();
}

// Redirect tilbage til madplan siden
header("Location: ./");
exit;
?>