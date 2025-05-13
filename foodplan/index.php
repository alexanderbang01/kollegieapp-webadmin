<?php
$page = "foodplan";
include '../components/header.php';
include '../database/db_conn.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

// Hjælpefunktion til at vise rettens navn eller "Ingen ret planlagt"
function displayDish($dish)
{
    if (isset($dish) && !empty(trim($dish)) && $dish !== 'faw' && $dish !== '0') {
        return $dish;
    }
    return 'Ingen ret planlagt';
}

// Get current day of week (1 = Monday, 7 = Sunday)
$current_day_num = date('N');
$current_day = '';

// Only set current_day if it's Monday through Thursday (1-4)
if ($current_day_num >= 1 && $current_day_num <= 4) {
    // Convert number to day name
    switch ($current_day_num) {
        case 1:
            $current_day = 'monday';
            break;
        case 2:
            $current_day = 'tuesday';
            break;
        case 3:
            $current_day = 'wednesday';
            break;
        case 4:
            $current_day = 'thursday';
            break;
    }
}

// Hent nuværende uge og år
$current_week = date('W');
$current_year = date('Y');

// Override uge/år hvis angivet i URL
if (isset($_GET['week']) && isset($_GET['year'])) {
    $current_week = $_GET['week'];
    $current_year = $_GET['year'];
}

// Beregn datoer for hver dag i den valgte uge
function getWeekDates($week, $year)
{
    $dates = [];

    // Beregn mandag for den valgte uge
    $mondayDate = new DateTime();
    $mondayDate->setISODate($year, $week, 1); // 1 = Mandag

    // Indstil til dansk format
    $format = 'j. F'; // F.eks. "2. maj"

    // Gem datomåned teksterne på dansk
    $monthNames = [
        'January' => 'januar',
        'February' => 'februar',
        'March' => 'marts',
        'April' => 'april',
        'May' => 'maj',
        'June' => 'juni',
        'July' => 'juli',
        'August' => 'august',
        'September' => 'september',
        'October' => 'oktober',
        'November' => 'november',
        'December' => 'december'
    ];

    // Beregn alle datoer
    $dates['monday'] = str_replace(
        array_keys($monthNames),
        array_values($monthNames),
        $mondayDate->format($format)
    );

    $tuesdayDate = clone $mondayDate;
    $tuesdayDate->modify('+1 day');
    $dates['tuesday'] = str_replace(
        array_keys($monthNames),
        array_values($monthNames),
        $tuesdayDate->format($format)
    );

    $wednesdayDate = clone $mondayDate;
    $wednesdayDate->modify('+2 days');
    $dates['wednesday'] = str_replace(
        array_keys($monthNames),
        array_values($monthNames),
        $wednesdayDate->format($format)
    );

    $thursdayDate = clone $mondayDate;
    $thursdayDate->modify('+3 days');
    $dates['thursday'] = str_replace(
        array_keys($monthNames),
        array_values($monthNames),
        $thursdayDate->format($format)
    );

    return $dates;
}

$dates = getWeekDates($current_week, $current_year);

// Hent madplanen fra databasen hvis den eksisterer
$foodplan = null;
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM foodplan WHERE week_number = ? AND year = ?");
    $stmt->bind_param("ii", $current_week, $current_year);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $foodplan = $result->fetch_assoc();
    }
    $stmt->close();
}

// Hent alle allergener til dropdown
$allergens = [];
if (isset($conn)) {
    $result = $conn->query("SELECT * FROM allergens ORDER BY name");
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $allergens[] = $row;
        }
    }
}

// Hent aktive allergener for hver dag
$day_allergens = [
    'monday' => [],
    'tuesday' => [],
    'wednesday' => [],
    'thursday' => []
];

// Tjek om en dag er vegetarisk
$is_vegetarian = [
    'monday' => false,
    'tuesday' => false,
    'wednesday' => false,
    'thursday' => false
];

if (isset($conn) && $foodplan) {
    $stmt = $conn->prepare("SELECT * FROM foodplan_allergens WHERE foodplan_id = ?");
    $stmt->bind_param("i", $foodplan['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $day_allergens[$row['day_of_week']][] = $row['allergen_id'];
    }
    $stmt->close();

    // Tjek om dage er markeret som vegetariske
    if (isset($foodplan['monday_vegetarian']) && $foodplan['monday_vegetarian'] == 1) {
        $is_vegetarian['monday'] = true;
    }
    if (isset($foodplan['tuesday_vegetarian']) && $foodplan['tuesday_vegetarian'] == 1) {
        $is_vegetarian['tuesday'] = true;
    }
    if (isset($foodplan['wednesday_vegetarian']) && $foodplan['wednesday_vegetarian'] == 1) {
        $is_vegetarian['wednesday'] = true;
    }
    if (isset($foodplan['thursday_vegetarian']) && $foodplan['thursday_vegetarian'] == 1) {
        $is_vegetarian['thursday'] = true;
    }
}

// Get month name from date
function getMonthName($week, $year)
{
    $dto = new DateTime();
    $dto->setISODate($year, $week);

    $monthNames = [
        'January' => 'januar',
        'February' => 'februar',
        'March' => 'marts',
        'April' => 'april',
        'May' => 'maj',
        'June' => 'juni',
        'July' => 'juli',
        'August' => 'august',
        'September' => 'september',
        'October' => 'oktober',
        'November' => 'november',
        'December' => 'december'
    ];

    return str_replace(
        array_keys($monthNames),
        array_values($monthNames),
        $dto->format('F Y')
    );
}

$month_name = getMonthName($current_week, $current_year);

// Success og fejlbeskeder
$message = '';
$message_type = '';

if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    $message_type = 'success';
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $message = $_SESSION['error_message'];
    $message_type = 'error';
    unset($_SESSION['error_message']);
}
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Foodplan content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Madplan</h1>
                        <p class="text-sm sm:text-base text-gray-600">Administrer kollegiets madplan</p>
                    </div>
                    <div class="flex gap-2">
                        <button id="edit-btn" class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                            <i class="fas fa-edit"></i>
                            <span>Rediger madplan</span>
                        </button>
                        <button id="save-btn" class="hidden bg-secondary hover:bg-secondary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                            <i class="fas fa-save"></i>
                            <span>Gem ændringer</span>
                        </button>
                        <button id="placeholder-btn" class="hidden bg-accent hover:bg-accent/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                            <i class="fas fa-magic"></i>
                            <span>Indsæt standardmadplan</span>
                        </button>
                    </div>
                </div>

                <!-- Status message -->
                <?php if ($message): ?>
                    <div id="status-message" class="<?php echo $message_type == 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'; ?> px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold"><?php echo $message_type == 'success' ? 'Succes!' : 'Fejl!'; ?></strong>
                        <span class="block sm:inline"><?php echo $message; ?></span>
                    </div>
                    <script>
                        // Skjul besked efter 3 sekunder
                        setTimeout(function() {
                            const statusMessage = document.getElementById('status-message');
                            if (statusMessage) {
                                statusMessage.style.opacity = '0';
                                statusMessage.style.transition = 'opacity 0.5s';
                                setTimeout(function() {
                                    statusMessage.style.display = 'none';
                                }, 500);
                            }
                        }, 3000);
                    </script>
                <?php endif; ?>

                <!-- Week selector -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex justify-center items-center gap-4">
                        <button id="prev-week" class="text-gray-600 hover:text-primary transition-colors text-xl">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h2 class="font-bold text-lg">Uge <?php echo $current_week; ?> • <?php echo $month_name; ?></h2>
                        <button id="next-week" class="text-gray-600 hover:text-primary transition-colors text-xl">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Current week meals -->
                <div id="view-mode" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                    <!-- Monday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-100">
                        <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'monday') ? 'bg-primary/10' : ''; ?>">
                            <h3 class="font-bold text-primary">Mandag <span class="text-gray-500"><?php echo $dates['monday']; ?></span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <h4 class="font-bold mb-2"><?php echo displayDish($foodplan['monday_dish'] ?? ''); ?></h4>
                            <p class="text-gray-600 text-sm mb-3"><?php echo $foodplan && isset($foodplan['monday_description']) ? $foodplan['monday_description'] : ''; ?></p>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($is_vegetarian['monday']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Vegetar</span>
                                <?php endif; ?>
                                <?php
                                if (isset($day_allergens['monday']) && count($day_allergens['monday']) > 0) {
                                    foreach ($day_allergens['monday'] as $allergen_id) {
                                        foreach ($allergens as $allergen) {
                                            if ($allergen['id'] == $allergen_id) {
                                                echo '<span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">' . $allergen['name'] . '</span>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Tuesday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-200">
                        <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'tuesday') ? 'bg-primary/10' : ''; ?>">
                            <h3 class="font-bold text-primary">Tirsdag <span class="text-gray-500"><?php echo $dates['tuesday']; ?></span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <h4 class="font-bold mb-2"><?php echo displayDish($foodplan['tuesday_dish'] ?? ''); ?></h4>
                            <p class="text-gray-600 text-sm mb-3"><?php echo $foodplan && isset($foodplan['tuesday_description']) ? $foodplan['tuesday_description'] : ''; ?></p>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($is_vegetarian['tuesday']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Vegetar</span>
                                <?php endif; ?>
                                <?php
                                if (isset($day_allergens['tuesday']) && count($day_allergens['tuesday']) > 0) {
                                    foreach ($day_allergens['tuesday'] as $allergen_id) {
                                        foreach ($allergens as $allergen) {
                                            if ($allergen['id'] == $allergen_id) {
                                                echo '<span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">' . $allergen['name'] . '</span>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Wednesday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-300">
                        <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'wednesday') ? 'bg-primary/10' : ''; ?>">
                            <h3 class="font-bold text-primary">Onsdag <span class="text-gray-500"><?php echo $dates['wednesday']; ?></span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <h4 class="font-bold mb-2"><?php echo displayDish($foodplan['wednesday_dish'] ?? ''); ?></h4>
                            <p class="text-gray-600 text-sm mb-3"><?php echo $foodplan && isset($foodplan['wednesday_description']) ? $foodplan['wednesday_description'] : ''; ?></p>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($is_vegetarian['wednesday']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Vegetar</span>
                                <?php endif; ?>
                                <?php
                                if (isset($day_allergens['wednesday']) && count($day_allergens['wednesday']) > 0) {
                                    foreach ($day_allergens['wednesday'] as $allergen_id) {
                                        foreach ($allergens as $allergen) {
                                            if ($allergen['id'] == $allergen_id) {
                                                echo '<span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">' . $allergen['name'] . '</span>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Thursday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-400">
                        <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'thursday') ? 'bg-primary/10' : ''; ?>">
                            <h3 class="font-bold text-primary">Torsdag <span class="text-gray-500"><?php echo $dates['thursday']; ?></span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <h4 class="font-bold mb-2"><?php echo displayDish($foodplan['thursday_dish'] ?? ''); ?></h4>
                            <p class="text-gray-600 text-sm mb-3"><?php echo $foodplan && isset($foodplan['thursday_description']) ? $foodplan['thursday_description'] : ''; ?></p>
                            <div class="flex flex-wrap gap-1">
                                <?php if ($is_vegetarian['thursday']): ?>
                                    <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Vegetar</span>
                                <?php endif; ?>
                                <?php
                                if (isset($day_allergens['thursday']) && count($day_allergens['thursday']) > 0) {
                                    foreach ($day_allergens['thursday'] as $allergen_id) {
                                        foreach ($allergens as $allergen) {
                                            if ($allergen['id'] == $allergen_id) {
                                                echo '<span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">' . $allergen['name'] . '</span>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit form (hidden by default) -->
                <form id="edit-form" class="hidden" method="post" action="save_foodplan.php">
                    <input type="hidden" name="week_number" value="<?php echo $current_week; ?>">
                    <input type="hidden" name="year" value="<?php echo $current_year; ?>">
                    <input type="hidden" name="foodplan_id" value="<?php echo $foodplan ? $foodplan['id'] : ''; ?>">

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                        <!-- Monday Edit -->
                        <div class="bg-white rounded-xl shadow animate-fade-in">
                            <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'monday') ? 'bg-primary/10' : ''; ?>">
                                <h3 class="font-bold text-primary">Mandag <span class="text-gray-500"><?php echo $dates['monday']; ?></span></h3>
                            </div>
                            <div class="p-3 sm:p-4">
                                <div class="mb-3">
                                    <label for="monday_dish" class="block text-xs text-gray-500 mb-1">Ret</label>
                                    <input type="text" id="monday_dish" name="monday_dish" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50" value="<?php echo ($foodplan && isset($foodplan['monday_dish']) && $foodplan['monday_dish'] !== 'Ingen ret planlagt' && $foodplan['monday_dish'] !== 'faw' && $foodplan['monday_dish'] !== '0') ? $foodplan['monday_dish'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="monday_description" class="block text-xs text-gray-500 mb-1">Beskrivelse</label>
                                    <textarea id="monday_description" name="monday_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"><?php echo $foodplan && isset($foodplan['monday_description']) ? $foodplan['monday_description'] : ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="monday_vegetarian" value="1" <?php echo ($is_vegetarian['monday']) ? 'checked' : ''; ?>>
                                        <span class="text-green-600 font-medium">Vegetarret</span>
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs text-gray-500 mb-1">Allergener</label>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($allergens as $allergen): ?>
                                            <label class="flex items-center gap-1 text-sm">
                                                <input type="checkbox" name="monday_allergens[]" value="<?php echo $allergen['id']; ?>"
                                                    <?php echo (isset($day_allergens['monday']) && in_array($allergen['id'], $day_allergens['monday'])) ? 'checked' : ''; ?>>
                                                <?php echo $allergen['name']; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tuesday Edit -->
                        <div class="bg-white rounded-xl shadow animate-fade-in">
                            <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'tuesday') ? 'bg-primary/10' : ''; ?>">
                                <h3 class="font-bold text-primary">Tirsdag <span class="text-gray-500"><?php echo $dates['tuesday']; ?></span></h3>
                            </div>
                            <div class="p-3 sm:p-4">
                                <div class="mb-3">
                                    <label for="tuesday_dish" class="block text-xs text-gray-500 mb-1">Ret</label>
                                    <input type="text" id="tuesday_dish" name="tuesday_dish" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50" value="<?php echo ($foodplan && isset($foodplan['tuesday_dish']) && $foodplan['tuesday_dish'] !== 'Ingen ret planlagt' && $foodplan['tuesday_dish'] !== 'faw' && $foodplan['tuesday_dish'] !== '0') ? $foodplan['tuesday_dish'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="tuesday_description" class="block text-xs text-gray-500 mb-1">Beskrivelse</label>
                                    <textarea id="tuesday_description" name="tuesday_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"><?php echo $foodplan && isset($foodplan['tuesday_description']) ? $foodplan['tuesday_description'] : ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="tuesday_vegetarian" value="1" <?php echo ($is_vegetarian['tuesday']) ? 'checked' : ''; ?>>
                                        <span class="text-green-600 font-medium">Vegetarret</span>
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs text-gray-500 mb-1">Allergener</label>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($allergens as $allergen): ?>
                                            <label class="flex items-center gap-1 text-sm">
                                                <input type="checkbox" name="tuesday_allergens[]" value="<?php echo $allergen['id']; ?>"
                                                    <?php echo (isset($day_allergens['tuesday']) && in_array($allergen['id'], $day_allergens['tuesday'])) ? 'checked' : ''; ?>>
                                                <?php echo $allergen['name']; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Wednesday Edit -->
                        <div class="bg-white rounded-xl shadow animate-fade-in">
                            <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'wednesday') ? 'bg-primary/10' : ''; ?>">
                                <h3 class="font-bold text-primary">Onsdag <span class="text-gray-500"><?php echo $dates['wednesday']; ?></span></h3>
                            </div>
                            <div class="p-3 sm:p-4">
                                <div class="mb-3">
                                    <label for="wednesday_dish" class="block text-xs text-gray-500 mb-1">Ret</label>
                                    <input type="text" id="wednesday_dish" name="wednesday_dish" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50" value="<?php echo ($foodplan && isset($foodplan['wednesday_dish']) && $foodplan['wednesday_dish'] !== 'Ingen ret planlagt' && $foodplan['wednesday_dish'] !== 'faw' && $foodplan['wednesday_dish'] !== '0') ? $foodplan['wednesday_dish'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="wednesday_description" class="block text-xs text-gray-500 mb-1">Beskrivelse</label>
                                    <textarea id="wednesday_description" name="wednesday_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"><?php echo $foodplan && isset($foodplan['wednesday_description']) ? $foodplan['wednesday_description'] : ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="wednesday_vegetarian" value="1" <?php echo ($is_vegetarian['wednesday']) ? 'checked' : ''; ?>>
                                        <span class="text-green-600 font-medium">Vegetarret</span>
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs text-gray-500 mb-1">Allergener</label>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($allergens as $allergen): ?>
                                            <label class="flex items-center gap-1 text-sm">
                                                <input type="checkbox" name="wednesday_allergens[]" value="<?php echo $allergen['id']; ?>"
                                                    <?php echo (isset($day_allergens['wednesday']) && in_array($allergen['id'], $day_allergens['wednesday'])) ? 'checked' : ''; ?>>
                                                <?php echo $allergen['name']; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Thursday Edit -->
                        <div class="bg-white rounded-xl shadow animate-fade-in">
                            <div class="p-3 sm:p-4 border-b border-gray-100 <?php echo ($current_day == 'thursday') ? 'bg-primary/10' : ''; ?>">
                                <h3 class="font-bold text-primary">Torsdag <span class="text-gray-500"><?php echo $dates['thursday']; ?></span></h3>
                            </div>
                            <div class="p-3 sm:p-4">
                                <div class="mb-3">
                                    <label for="thursday_dish" class="block text-xs text-gray-500 mb-1">Ret</label>
                                    <input type="text" id="thursday_dish" name="thursday_dish" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50" value="<?php echo ($foodplan && isset($foodplan['thursday_dish']) && $foodplan['thursday_dish'] !== 'Ingen ret planlagt' && $foodplan['thursday_dish'] !== 'faw' && $foodplan['thursday_dish'] !== '0') ? $foodplan['thursday_dish'] : ''; ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="thursday_description" class="block text-xs text-gray-500 mb-1">Beskrivelse</label>
                                    <textarea id="thursday_description" name="thursday_description" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"><?php echo $foodplan && isset($foodplan['thursday_description']) ? $foodplan['thursday_description'] : ''; ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="thursday_vegetarian" value="1" <?php echo ($is_vegetarian['thursday']) ? 'checked' : ''; ?>>
                                        <span class="text-green-600 font-medium">Vegetarret</span>
                                    </label>
                                </div>
                                <div class="mb-3">
                                    <label class="block text-xs text-gray-500 mb-1">Allergener</label>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($allergens as $allergen): ?>
                                            <label class="flex items-center gap-1 text-sm">
                                                <input type="checkbox" name="thursday_allergens[]" value="<?php echo $allergen['id']; ?>"
                                                    <?php echo (isset($day_allergens['thursday']) && in_array($allergen['id'], $day_allergens['thursday'])) ? 'checked' : ''; ?>>
                                                <?php echo $allergen['name']; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="button" id="cancel-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg mr-2">
                            Annuller
                        </button>
                        <button type="submit" class="bg-secondary hover:bg-secondary/90 text-white px-4 py-2 rounded-lg">
                            Gem madplan
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Vis/skjul redigeringsformular
        const editBtn = document.getElementById('edit-btn');
        const saveBtn = document.getElementById('save-btn');
        const placeholderBtn = document.getElementById('placeholder-btn');
        const cancelBtn = document.getElementById('cancel-btn');
        const viewMode = document.getElementById('view-mode');
        const editForm = document.getElementById('edit-form');

        editBtn.addEventListener('click', () => {
            viewMode.classList.add('hidden');
            editForm.classList.remove('hidden');
            editBtn.classList.add('hidden');
            saveBtn.classList.remove('hidden');
            placeholderBtn.classList.remove('hidden');
        });

        cancelBtn.addEventListener('click', () => {
            viewMode.classList.remove('hidden');
            editForm.classList.add('hidden');
            editBtn.classList.remove('hidden');
            saveBtn.classList.add('hidden');
            placeholderBtn.classList.add('hidden');
        });

        // Håndter placeholder madplan knap
        placeholderBtn.addEventListener('click', (e) => {
            e.preventDefault();

            // Standard madplan data
            const placeholderMeals = {
                'monday': {
                    'dish': 'Pasta Bolognese',
                    'description': 'Hjemmelavet pasta med kødsauce og friskrevet parmesan.',
                    'vegetarian': false,
                    'allergens': [1, 2] // Gluten, Laktose
                },
                'tuesday': {
                    'dish': 'Vegetarisk Wok',
                    'description': 'Friske grøntsager og tofu stegt i wok med teriyaki sauce og jasminris.',
                    'vegetarian': true,
                    'allergens': [5] // Soja
                },
                'wednesday': {
                    'dish': 'Fiskefrikadeller',
                    'description': 'Hjemmelavede fiskefrikadeller med kartofler, remoulade og gulerodssalat.',
                    'vegetarian': false,
                    'allergens': [4, 6] // Æg, Fisk
                },
                'thursday': {
                    'dish': 'Kylling i karry',
                    'description': 'Mørt kyllingebryst i cremet karrysauce med ris og mangochutney.',
                    'vegetarian': false,
                    'allergens': [2] // Laktose
                }
            };

            // Udfyld felterne med standard madplan
            document.getElementById('monday_dish').value = placeholderMeals.monday.dish;
            document.getElementById('monday_description').value = placeholderMeals.monday.description;
            document.querySelector('input[name="monday_vegetarian"]').checked = placeholderMeals.monday.vegetarian;

            document.getElementById('tuesday_dish').value = placeholderMeals.tuesday.dish;
            document.getElementById('tuesday_description').value = placeholderMeals.tuesday.description;
            document.querySelector('input[name="tuesday_vegetarian"]').checked = placeholderMeals.tuesday.vegetarian;

            document.getElementById('wednesday_dish').value = placeholderMeals.wednesday.dish;
            document.getElementById('wednesday_description').value = placeholderMeals.wednesday.description;
            document.querySelector('input[name="wednesday_vegetarian"]').checked = placeholderMeals.wednesday.vegetarian;

            document.getElementById('thursday_dish').value = placeholderMeals.thursday.dish;
            document.getElementById('thursday_description').value = placeholderMeals.thursday.description;
            document.querySelector('input[name="thursday_vegetarian"]').checked = placeholderMeals.thursday.vegetarian;

            // Nulstil alle allergen checkbokse først
            document.querySelectorAll('input[type="checkbox"][name^="monday_allergens"], input[type="checkbox"][name^="tuesday_allergens"], input[type="checkbox"][name^="wednesday_allergens"], input[type="checkbox"][name^="thursday_allergens"]').forEach(checkbox => {
                checkbox.checked = false;
            });

            // Markér de relevante allergener for hver dag
            placeholderMeals.monday.allergens.forEach(allergenId => {
                const checkbox = document.querySelector(`input[name="monday_allergens[]"][value="${allergenId}"]`);
                if (checkbox) checkbox.checked = true;
            });

            placeholderMeals.tuesday.allergens.forEach(allergenId => {
                const checkbox = document.querySelector(`input[name="tuesday_allergens[]"][value="${allergenId}"]`);
                if (checkbox) checkbox.checked = true;
            });

            placeholderMeals.wednesday.allergens.forEach(allergenId => {
                const checkbox = document.querySelector(`input[name="wednesday_allergens[]"][value="${allergenId}"]`);
                if (checkbox) checkbox.checked = true;
            });

            placeholderMeals.thursday.allergens.forEach(allergenId => {
                const checkbox = document.querySelector(`input[name="thursday_allergens[]"][value="${allergenId}"]`);
                if (checkbox) checkbox.checked = true;
            });
        });

        // Week navigation
        const prevWeekBtn = document.getElementById('prev-week');
        const nextWeekBtn = document.getElementById('next-week');

        let currentWeek = <?php echo $current_week; ?>;
        let currentYear = <?php echo $current_year; ?>;
        const weekTitle = document.querySelector('h2');

        prevWeekBtn.addEventListener('click', () => {
            currentWeek--;
            if (currentWeek < 1) {
                currentWeek = 52;
                currentYear--;
            }
            updateWeekDisplay();
            window.location.href = `?week=${currentWeek}&year=${currentYear}`;
        });

        nextWeekBtn.addEventListener('click', () => {
            currentWeek++;
            if (currentWeek > 52) {
                currentWeek = 1;
                currentYear++;
            }
            updateWeekDisplay();
            window.location.href = `?week=${currentWeek}&year=${currentYear}`;
        });

        function updateWeekDisplay() {
            // Get month name for first day of the week
            const firstDay = new Date(currentYear, 0, 1 + (currentWeek - 1) * 7);
            const monthName = firstDay.toLocaleString('default', {
                month: 'long'
            });
            weekTitle.textContent = `Uge ${currentWeek} • ${monthName} ${currentYear}`;
        }
    </script>
</body>

</html>