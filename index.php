<?php
$page = 'dashboard';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: login/");
    exit();
}

include 'components/header.php';
include 'database/db_conn.php';

// Hent dashboard data fra databasen
$dashboardData = [
    'totalResidents' => 0,
    'activeEvents' => 0,
    'lastFoodplanUpdate' => null,
    'unreadMessages' => 0
];

// Funktion til at formatere datoer på dansk uden årstal
function formatDanishDate($date, $includeYear = false)
{
    $months = [
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

    $format = $includeYear ? 'j. F Y' : 'j. F';
    $englishDate = date($format, strtotime($date));

    foreach ($months as $english => $danish) {
        $englishDate = str_replace($english, $danish, $englishDate);
    }

    return $englishDate;
}

// Funktion til at afkorte tekst
function truncateText($text, $maxLength = 90)
{
    if (strlen($text) <= $maxLength) {
        return $text;
    }

    return substr($text, 0, $maxLength) . '...';
}

// 1. Hent antal beboere
if ($conn) {
    $result = $conn->query("SELECT COUNT(*) as count FROM residents");
    if ($result && $row = $result->fetch_assoc()) {
        $dashboardData['totalResidents'] = $row['count'];
    }

    // 2. Hent antal kommende begivenheder
    $today = date('Y-m-d');
    $result = $conn->query("SELECT COUNT(*) as count FROM events WHERE date >= '$today'");
    if ($result && $row = $result->fetch_assoc()) {
        $dashboardData['activeEvents'] = $row['count'];
    }

    // 3. Hent dato for seneste madplans-opdatering
    $result = $conn->query("SELECT updated_at FROM foodplan ORDER BY updated_at DESC LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        $dashboardData['lastFoodplanUpdate'] = $row['updated_at'];
    }

    // 4. Hent antal ulæste beskeder
    $userId = $_SESSION['user_id'];
    $result = $conn->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = $userId AND recipient_type = 'staff' AND read_at IS NULL");
    if ($result && $row = $result->fetch_assoc()) {
        $dashboardData['unreadMessages'] = $row['count'];
    }

    // 5. Hent den aktuelle madplan (for indeværende uge)
    $weekNumber = date('W');
    $year = date('Y');
    $currentFoodplan = null;

    $stmt = $conn->prepare("SELECT * FROM foodplan WHERE week_number = ? AND year = ?");
    $stmt->bind_param("ii", $weekNumber, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $currentFoodplan = $result->fetch_assoc();

        // Hent allergener for hver dag
        $stmt = $conn->prepare("
            SELECT fa.day_of_week, a.name
            FROM foodplan_allergens fa
            JOIN allergens a ON fa.allergen_id = a.id
            WHERE fa.foodplan_id = ?
        ");
        $stmt->bind_param("i", $currentFoodplan['id']);
        $stmt->execute();
        $allergensResult = $stmt->get_result();

        $foodplanAllergens = [];
        while ($row = $allergensResult->fetch_assoc()) {
            $foodplanAllergens[$row['day_of_week']][] = $row['name'];
        }
    }

    // 6. Hent kommende begivenheder
    $upcomingEvents = [];
    $result = $conn->query("
        SELECT e.*, COUNT(ep.resident_id) as participant_count 
        FROM events e
        LEFT JOIN event_participants ep ON e.id = ep.event_id
        WHERE e.date >= '$today'
        GROUP BY e.id
        ORDER BY e.date ASC, e.time ASC
        LIMIT 3
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $upcomingEvents[] = $row;
        }
    }

    // 7. Hent seneste aktiviteter
    $recentActivities = [];
    $result = $conn->query("
        SELECT a.*, u.name AS user_name, r.first_name, r.last_name
        FROM activities a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN residents r ON a.resident_id = r.id
        ORDER BY a.created_at DESC
        LIMIT 3
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentActivities[] = $row;
        }
    }

    // 8. Hent alle aktiviteter til modal (begræns til 100 for performance)
    $allActivities = [];
    $result = $conn->query("
        SELECT a.*, u.name AS user_name, r.first_name, r.last_name
        FROM activities a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN residents r ON a.resident_id = r.id
        ORDER BY a.created_at DESC
        LIMIT 100
    ");

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $allActivities[] = $row;
        }
    }
}

// Definer aktivitetskategorier
$activityCategories = [
    'user_registered' => [
        'title' => 'Nye Beboere',
        'icon' => 'fas fa-user-plus',
        'color' => 'blue-500'
    ],
    'event_created' => [
        'title' => 'Begivenheder',
        'icon' => 'fas fa-calendar-plus',
        'color' => 'green-500'
    ],
    'event_updated' => [
        'title' => 'Begivenheder',
        'icon' => 'fas fa-calendar-alt',
        'color' => 'green-500'
    ],
    'event_registration' => [
        'title' => 'Begivenheder',
        'icon' => 'fas fa-user-check',
        'color' => 'green-500'
    ],
    'event_unregistration' => [
        'title' => 'Begivenheder',
        'icon' => 'fas fa-user-times',
        'color' => 'yellow-500'
    ],
    'news_created' => [
        'title' => 'Nyheder',
        'icon' => 'fas fa-newspaper',
        'color' => 'purple-500'
    ],
    'news_updated' => [
        'title' => 'Nyheder',
        'icon' => 'fas fa-edit',
        'color' => 'purple-500'
    ],
    'foodplan_updated' => [
        'title' => 'Madplan',
        'icon' => 'fas fa-utensils',
        'color' => 'orange-500'
    ]
];
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include 'components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Dashboard content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-sm sm:text-base text-gray-600">Velkommen tilbage, <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Administrator'; ?></p>
                </div>

                <!-- Overview Cards - Optimeret til mobile enheder -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6 mb-4 sm:mb-8">
                    <!-- Total Beboere Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-100 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-primary/10 text-primary mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-users text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Totale Beboere</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo $dashboardData['totalResidents']; ?></p>
                        </div>
                    </div>

                    <!-- Aktive Begivenheder Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-200 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-secondary/10 text-secondary mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Aktive Begivenheder</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo $dashboardData['activeEvents']; ?></p>
                        </div>
                    </div>

                    <!-- Madplan Status Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-300 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-accent/10 text-accent mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-utensils text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Madplan Status</p>
                            <p class="text-sm sm:text-base font-bold">
                                <?php
                                if ($dashboardData['lastFoodplanUpdate']) {
                                    echo 'Opdateret ' . formatDanishDate($dashboardData['lastFoodplanUpdate']);
                                } else {
                                    echo 'Ikke opdateret';
                                }
                                ?>
                            </p>
                        </div>
                    </div>

                    <!-- Ulæste Beskeder Card (erstattet Aktive Anmodninger) -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-400 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-danger/10 text-danger mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-envelope text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Ulæste Beskeder</p>
                            <p class="text-xl sm:text-2xl font-bold"><?php echo $dashboardData['unreadMessages']; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Madplan og Hurtige Handlinger -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-3 sm:gap-6">
                    <!-- Ugens Madplan (i række-format) -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in delay-100">
                            <div class="flex justify-between items-center mb-4 sm:mb-6">
                                <h2 class="text-lg sm:text-xl font-bold">Ugens Madplan</h2>
                                <a href="<?= $base ?>foodplan/" class="text-primary hover:underline text-xs sm:text-sm">Redigér</a>
                            </div>

                            <?php if ($currentFoodplan): ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-full">
                                        <thead>
                                            <tr class="text-left">
                                                <th class="pb-2 text-sm font-medium text-gray-500">Dag</th>
                                                <th class="pb-2 text-sm font-medium text-gray-500">Ret</th>
                                                <th class="pb-2 text-sm font-medium text-gray-500">Allergener</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Bestem den aktuelle ugedag (1 = mandag, 7 = søndag)
                                            $currentWeekday = date('N');
                                            $weekdays = [
                                                1 => ['name' => 'Mandag', 'key' => 'monday'],
                                                2 => ['name' => 'Tirsdag', 'key' => 'tuesday'],
                                                3 => ['name' => 'Onsdag', 'key' => 'wednesday'],
                                                4 => ['name' => 'Torsdag', 'key' => 'thursday']
                                            ];

                                            foreach ($weekdays as $dayNum => $day) {
                                                $isCurrentDay = ($dayNum == $currentWeekday);
                                                $rowClass = $isCurrentDay ? 'bg-primary/5' : '';
                                                $tdClass = $isCurrentDay ? 'border-l-4 border-primary pl-2' : '';
                                            ?>
                                                <!-- <?php echo $day['name']; ?> -->
                                                <tr class="border-t border-gray-100 <?php echo $rowClass; ?>">
                                                    <td class="py-3 pr-2 <?php echo $tdClass; ?>">
                                                        <p class="font-medium text-gray-800"><?php echo $day['name']; ?></p>
                                                        <p class="text-xs text-gray-500"><?php echo date('j. M', strtotime($day['key'] . " this week")); ?></p>
                                                    </td>
                                                    <td class="py-3 pr-2">
                                                        <?php if (!empty($currentFoodplan[$day['key'] . '_dish'])): ?>
                                                            <p class="font-medium"><?php echo htmlspecialchars($currentFoodplan[$day['key'] . '_dish']); ?></p>
                                                            <p class="text-xs text-gray-500">
                                                                <?php
                                                                $description = $currentFoodplan[$day['key'] . '_description'];
                                                                echo htmlspecialchars(truncateText($description, 90));
                                                                ?>
                                                            </p>
                                                        <?php else: ?>
                                                            <p class="text-gray-500 italic">Ingen ret planlagt</p>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="py-3">
                                                        <div class="flex flex-wrap gap-1">
                                                            <?php
                                                            if (isset($foodplanAllergens[$day['key']]) && !empty($foodplanAllergens[$day['key']])) {
                                                                foreach ($foodplanAllergens[$day['key']] as $allergen) {
                                                                    echo '<span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">' . htmlspecialchars($allergen) . '</span>';
                                                                }
                                                            } else {
                                                                echo '<span class="text-gray-500 text-xs">Ingen allergener angivet</span>';
                                                            }
                                                            ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="bg-gray-50 rounded-lg p-4 text-center">
                                    <p class="text-gray-500">Ingen madplan fundet for denne uge</p>
                                    <a href="<?= $base ?>foodplan/" class="inline-block mt-2 text-primary hover:underline">Opret ugens madplan</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in delay-200">
                            <h2 class="text-lg sm:text-xl font-bold mb-4 sm:mb-6">Hurtige Handlinger</h2>
                            <div class="space-y-2 sm:space-y-3">
                                <a href="<?= $base ?>foodplan/" class="flex items-center gap-2 sm:gap-4 p-2 sm:p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                        <i class="fas fa-utensils text-sm sm:text-base"></i>
                                    </div>
                                    <span class="font-medium text-sm sm:text-base">Opdater madplan</span>
                                </a>
                                <a href="<?= $base ?>events/" class="flex items-center gap-2 sm:gap-4 p-2 sm:p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                        <i class="fas fa-calendar-plus text-sm sm:text-base"></i>
                                    </div>
                                    <span class="font-medium text-sm sm:text-base">Opret begivenhed</span>
                                </a>
                                <a href="<?= $base ?>news/" class="flex items-center gap-2 sm:gap-4 p-2 sm:p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                        <i class="fas fa-newspaper text-sm sm:text-base"></i>
                                    </div>
                                    <span class="font-medium text-sm sm:text-base">Tilføj nyhed</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kommende Begivenheder og Seneste Aktiviteter -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-6 mt-4 sm:mt-8">
                    <!-- Kommende Begivenheder -->
                    <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in delay-100">
                        <div class="flex justify-between items-center mb-4 sm:mb-6">
                            <h2 class="text-lg sm:text-xl font-bold">Kommende Begivenheder</h2>
                            <a href="<?= $base ?>events/" class="text-primary hover:underline text-xs sm:text-sm">Se alle</a>
                        </div>

                        <?php if (empty($upcomingEvents)): ?>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-gray-500">Ingen kommende begivenheder</p>
                                <a href="<?= $base ?>events/" class="inline-block mt-2 text-primary hover:underline">Opret en begivenhed</a>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3 sm:space-y-4">
                                <?php
                                $colors = ['primary', 'secondary', 'accent'];
                                foreach ($upcomingEvents as $index => $event):
                                    $color = $colors[$index % count($colors)];
                                    $formattedDate = formatDanishDate($event['date'], true);
                                    $formattedTime = date('H:i', strtotime($event['time']));
                                ?>
                                    <div class="border-l-4 border-<?php echo $color; ?> pl-2 sm:pl-4">
                                        <div class="flex justify-between">
                                            <span class="text-<?php echo $color; ?> font-medium text-xs sm:text-sm"><?php echo $formattedDate; ?></span>
                                            <span class="text-gray-500 text-xs"><?php echo $formattedTime; ?></span>
                                        </div>
                                        <h3 class="font-bold text-sm sm:text-base"><?php echo htmlspecialchars($event['title']); ?></h3>
                                        <p class="text-gray-600 text-xs"><?php echo htmlspecialchars($event['location']); ?> • <?php echo $event['participant_count']; ?> tilmeldte</p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Seneste Aktiviteter -->
                    <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in delay-200">
                        <div class="flex justify-between items-center mb-4 sm:mb-6">
                            <h2 class="text-lg sm:text-xl font-bold">Seneste Aktiviteter</h2>
                            <button id="show-activities-modal" class="text-primary hover:underline text-xs sm:text-sm cursor-pointer">Se alle</button>
                        </div>

                        <?php if (empty($recentActivities)): ?>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-gray-500">Ingen nylige aktiviteter registreret</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3 sm:space-y-4">
                                <?php
                                $icons = [
                                    'event_created' => ['fas fa-calendar-plus', 'primary'],
                                    'event_updated' => ['fas fa-calendar-alt', 'secondary'],
                                    'event_registration' => ['fas fa-user-check', 'success'],
                                    'event_unregistration' => ['fas fa-user-times', 'warning'],
                                    'foodplan_updated' => ['fas fa-utensils', 'accent'],
                                    'user_registered' => ['fas fa-user-plus', 'info'],
                                    'news_created' => ['fas fa-newspaper', 'primary'],
                                    'news_updated' => ['fas fa-edit', 'secondary'],
                                    'default' => ['fas fa-history', 'gray-500']
                                ];

                                foreach ($recentActivities as $index => $activity):
                                    // Bestem ikon og farve baseret på aktivitetstype
                                    $iconInfo = isset($icons[$activity['activity_type']]) ? $icons[$activity['activity_type']] : $icons['default'];
                                    [$icon, $color] = $iconInfo;

                                    // Beregn tidsforskel
                                    $activityTime = new DateTime($activity['created_at']);
                                    $now = new DateTime();
                                    $interval = $now->diff($activityTime);

                                    if ($interval->d > 0) {
                                        $timeAgo = $interval->d . ' ' . ($interval->d == 1 ? 'dag' : 'dage') . ' siden';
                                    } elseif ($interval->h > 0) {
                                        $timeAgo = $interval->h . ' ' . ($interval->h == 1 ? 'time' : 'timer') . ' siden';
                                    } else {
                                        $timeAgo = $interval->i . ' ' . ($interval->i == 1 ? 'minut' : 'minutter') . ' siden';
                                    }
                                ?>
                                    <?php if ($index > 0): ?>
                                        <div class="border-t border-gray-200 my-2"></div>
                                    <?php endif; ?>

                                    <div class="flex items-start gap-2 sm:gap-4">
                                        <div class="w-8 h-8 rounded-full bg-<?php echo $color; ?>/10 text-<?php echo $color; ?> flex items-center justify-center flex-shrink-0">
                                            <i class="<?php echo $icon; ?> text-sm"></i>
                                        </div>
                                        <div class="flex-grow min-w-0">
                                            <p class="font-medium text-sm break-words"><?php echo htmlspecialchars(truncateText($activity['description'], 100)); ?></p>
                                            <p class="text-gray-400 text-xs mt-1"><?php echo $timeAgo; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Aktiviteter Modal -->
    <div id="activities-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden z-50">
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-full p-4 text-center">
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-4xl transform scale-95 opacity-0 transition-all duration-300" id="modal-content">
                    <!-- Header -->
                    <div class="flex justify-between items-center p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-800">Alle Aktiviteter</h2>
                        <button id="close-activities-modal" class="text-gray-500 hover:text-gray-700 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="border-b border-gray-200">
                        <nav class="flex space-x-8 px-6 pt-4" role="tablist">
                            <button class="filter-tab active whitespace-nowrap py-2 px-1 border-b-2 border-primary text-primary font-medium text-sm" data-filter="all">
                                Alle Aktiviteter
                            </button>
                            <button class="filter-tab whitespace-nowrap py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-filter="user_registered">
                                <i class="fas fa-user-plus mr-1"></i>
                                Nye Beboere
                            </button>
                            <button class="filter-tab whitespace-nowrap py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-filter="events">
                                <i class="fas fa-calendar mr-1"></i>
                                Begivenheder
                            </button>
                            <button class="filter-tab whitespace-nowrap py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-filter="news">
                                <i class="fas fa-newspaper mr-1"></i>
                                Nyheder
                            </button>
                            <button class="filter-tab whitespace-nowrap py-2 px-1 border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 font-medium text-sm" data-filter="foodplan_updated">
                                <i class="fas fa-utensils mr-1"></i>
                                Madplan
                            </button>
                        </nav>
                    </div>

                    <!-- Content -->
                    <div class="p-6 max-h-96 overflow-y-auto">
                        <?php if (empty($allActivities)): ?>
                            <div class="bg-gray-50 rounded-lg p-8 text-center">
                                <i class="fas fa-history text-gray-400 text-4xl mb-4"></i>
                                <p class="text-gray-500 text-lg">Ingen aktiviteter registreret endnu</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3" id="activities-container">
                                <?php
                                $icons = [
                                    'event_created' => ['fas fa-calendar-plus', 'green-500'],
                                    'event_updated' => ['fas fa-calendar-alt', 'green-500'],
                                    'event_registration' => ['fas fa-user-check', 'green-500'],
                                    'event_unregistration' => ['fas fa-user-times', 'yellow-500'],
                                    'foodplan_updated' => ['fas fa-utensils', 'orange-500'],
                                    'user_registered' => ['fas fa-user-plus', 'blue-500'],
                                    'news_created' => ['fas fa-newspaper', 'purple-500'],
                                    'news_updated' => ['fas fa-edit', 'purple-500'],
                                    'default' => ['fas fa-history', 'gray-500']
                                ];

                                foreach ($allActivities as $index => $activity):
                                    // Bestem ikon og farve baseret på aktivitetstype
                                    $iconInfo = isset($icons[$activity['activity_type']]) ? $icons[$activity['activity_type']] : $icons['default'];
                                    [$icon, $color] = $iconInfo;

                                    // Bestem kategori for filtrering
                                    $category = 'other';
                                    if (in_array($activity['activity_type'], ['event_created', 'event_updated', 'event_registration', 'event_unregistration'])) {
                                        $category = 'events';
                                    } elseif (in_array($activity['activity_type'], ['news_created', 'news_updated'])) {
                                        $category = 'news';
                                    } elseif ($activity['activity_type'] === 'user_registered') {
                                        $category = 'user_registered';
                                    } elseif ($activity['activity_type'] === 'foodplan_updated') {
                                        $category = 'foodplan_updated';
                                    }

                                    // Beregn tidsforskel
                                    $activityTime = new DateTime($activity['created_at']);
                                    $now = new DateTime();
                                    $interval = $now->diff($activityTime);

                                    if ($interval->d > 0) {
                                        $timeAgo = $interval->d . ' ' . ($interval->d == 1 ? 'dag' : 'dage') . ' siden';
                                    } elseif ($interval->h > 0) {
                                        $timeAgo = $interval->h . ' ' . ($interval->h == 1 ? 'time' : 'timer') . ' siden';
                                    } else {
                                        $timeAgo = $interval->i . ' ' . ($interval->i == 1 ? 'minut' : 'minutter') . ' siden';
                                    }
                                ?>
                                    <div class="activity-item bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition-colors"
                                        data-category="<?php echo $category; ?>"
                                        data-type="<?php echo $activity['activity_type']; ?>">
                                        <div class="flex items-start gap-4">
                                            <div class="w-10 h-10 rounded-full bg-<?php echo $color; ?>/10 text-<?php echo $color; ?> flex items-center justify-center flex-shrink-0">
                                                <i class="<?php echo $icon; ?> text-base"></i>
                                            </div>
                                            <div class="flex-grow min-w-0 text-left">
                                                <p class="font-medium text-sm text-gray-800 break-words mb-1 text-left"><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <div class="flex items-center justify-between text-left">
                                                    <p class="text-gray-500 text-xs"><?php echo $timeAgo; ?></p>
                                                    <p class="text-gray-400 text-xs"><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div id="no-activities-message" class="hidden bg-gray-50 rounded-lg p-8 text-center">
                                <i class="fas fa-filter text-gray-400 text-4xl mb-4"></i>
                                <p class="text-gray-500 text-lg">Ingen aktiviteter fundet i denne kategori</p>
                            </div>

                            <?php if (count($allActivities) >= 100): ?>
                                <div class="mt-6 text-center">
                                    <p class="text-gray-500 text-sm">Viser de 100 seneste aktiviteter</p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Activities modal functionality
        document.addEventListener('DOMContentLoaded', function() {
            const showActivitiesModalBtn = document.getElementById('show-activities-modal');
            const activitiesModal = document.getElementById('activities-modal');
            const closeActivitiesModalBtn = document.getElementById('close-activities-modal');
            const modalContent = document.getElementById('modal-content');

            if (showActivitiesModalBtn && activitiesModal && closeActivitiesModalBtn) {
                showActivitiesModalBtn.addEventListener('click', function() {
                    activitiesModal.classList.remove('hidden');
                    document.body.style.overflow = 'hidden';

                    // Trigger animation
                    setTimeout(() => {
                        modalContent.classList.remove('scale-95', 'opacity-0');
                        modalContent.classList.add('scale-100', 'opacity-100');
                    }, 10);
                });

                closeActivitiesModalBtn.addEventListener('click', function() {
                    closeModal();
                });

                // Close modal when clicking outside - improved version
                activitiesModal.addEventListener('click', function(e) {
                    // Check if the click is on the backdrop (not on the modal content)
                    if (e.target === activitiesModal || (!modalContent.contains(e.target) && e.target.closest('#modal-content') === null)) {
                        closeModal();
                    }
                });

                // Prevent modal from closing when clicking inside the modal content
                modalContent.addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                // Close modal with Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && !activitiesModal.classList.contains('hidden')) {
                        closeModal();
                    }
                });

                function closeModal() {
                    modalContent.classList.remove('scale-100', 'opacity-100');
                    modalContent.classList.add('scale-95', 'opacity-0');

                    setTimeout(() => {
                        activitiesModal.classList.add('hidden');
                        document.body.style.overflow = 'auto';
                    }, 300);
                }
            }

            // Filter functionality
            const filterTabs = document.querySelectorAll('.filter-tab');
            const activityItems = document.querySelectorAll('.activity-item');
            const noActivitiesMessage = document.getElementById('no-activities-message');

            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');

                    // Update active tab
                    filterTabs.forEach(t => {
                        t.classList.remove('active', 'border-primary', 'text-primary');
                        t.classList.add('border-transparent', 'text-gray-500');
                    });
                    this.classList.add('active', 'border-primary', 'text-primary');
                    this.classList.remove('border-transparent', 'text-gray-500');

                    // Filter activities
                    let visibleCount = 0;
                    activityItems.forEach(item => {
                        const category = item.getAttribute('data-category');
                        const type = item.getAttribute('data-type');

                        if (filter === 'all' ||
                            category === filter ||
                            type === filter ||
                            (filter === 'events' && ['event_created', 'event_updated', 'event_registration', 'event_unregistration'].includes(type)) ||
                            (filter === 'news' && ['news_created', 'news_updated'].includes(type))) {
                            item.style.display = 'block';
                            visibleCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Show/hide no activities message
                    if (visibleCount === 0) {
                        noActivitiesMessage.classList.remove('hidden');
                    } else {
                        noActivitiesMessage.classList.add('hidden');
                    }
                });
            });
        });
    </script>
</body>

</html>