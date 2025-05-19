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
function formatDanishDate($date, $includeYear = false) {
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
function truncateText($text, $maxLength = 90) {
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
}
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
                            <a href="#" class="text-primary hover:underline text-xs sm:text-sm">Se alle</a>
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
                                'foodplan_updated' => ['fas fa-utensils', 'secondary'],
                                'resident_added' => ['fas fa-user-plus', 'accent'],
                                'news_created' => ['fas fa-newspaper', 'danger'],
                                'default' => ['fas fa-history', 'primary']
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
                                <div class="w-8 h-8 rounded-full bg-<?php echo $color; ?>/10 text-<?php echo $color; ?> flex items-center justify-center">
                                    <i class="<?php echo $icon; ?> text-sm sm:text-base"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-sm sm:text-base"><?php echo htmlspecialchars(truncateText($activity['description'], 100)); ?></p>
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

    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const closeMobileMenu = document.getElementById('close-mobile-menu');

        if (mobileMenuBtn && mobileSidebar && closeMobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileSidebar.classList.remove('hidden');
            });

            closeMobileMenu.addEventListener('click', () => {
                mobileSidebar.classList.add('hidden');
            });

            // Close mobile menu when clicking outside
            mobileSidebar.addEventListener('click', (e) => {
                if (e.target === mobileSidebar) {
                    mobileSidebar.classList.add('hidden');
                }
            });
        }

        // User dropdown toggle
        const userMenuBtn = document.getElementById('user-menu-btn');
        const userDropdown = document.getElementById('user-dropdown');

        if (userMenuBtn && userDropdown) {
            userMenuBtn.addEventListener('click', () => {
                userDropdown.classList.toggle('hidden');
            });

            // Close user dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (userMenuBtn && userDropdown && !userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.add('hidden');
                }
            });
        }
    </script>
</body>

</html>