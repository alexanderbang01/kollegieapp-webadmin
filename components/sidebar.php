<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

// Hent profilbillede fra session (sat ved login)
$userProfileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : null;

// Fallback: Hvis profilbillede ikke er i session, hent det fra database
if ($userProfileImage === null && isset($_SESSION['user_id'])) {
    // Find den korrekte sti til database ved at gå op i mappehierarkiet
    $current_dir = __DIR__;
    $db_found = false;

    // Prøv op til 5 niveauer op for at finde database mappen
    for ($i = 0; $i < 5; $i++) {
        $db_path = $current_dir . '/database/db_conn.php';
        if (file_exists($db_path)) {
            require_once $db_path;
            $db_found = true;
            break;
        }
        $current_dir = dirname($current_dir);
    }

    // Hvis database forbindelse findes, hent profilbillede
    if ($db_found && isset($conn)) {
        try {
            $stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
            $stmt->bind_param("i", $_SESSION['user_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
                $userProfileImage = $user_data['profile_image'];
                // Gem også i session for næste gang
                $_SESSION['profile_image'] = $userProfileImage;
            }
        } catch (Exception $e) {
            // Silent fejl
        }
    }
}

// Generer initialer fra brugerens navn (med støtte for danske bogstaver)
$userInitials = "";
if (isset($_SESSION['name'])) {
    $nameParts = explode(' ', $_SESSION['name']);

    if (count($nameParts) >= 2) {
        // Tag første bogstav af første navn og første bogstav af sidste navn
        $firstName = $nameParts[0];
        $lastName = $nameParts[count($nameParts) - 1];
        $userInitials = mb_strtoupper(mb_substr($firstName, 0, 1, 'UTF-8') . mb_substr($lastName, 0, 1, 'UTF-8'), 'UTF-8');
    } elseif (count($nameParts) == 1) {
        // Hvis kun ét navn, tag de to første bogstaver
        $userInitials = mb_strtoupper(mb_substr($nameParts[0], 0, 2, 'UTF-8'), 'UTF-8');
    }
}

// Formatter navnet til at kun vise fornavn og efternavn
$displayName = "";
if (isset($_SESSION['name'])) {
    $nameParts = explode(' ', $_SESSION['name']);

    if (count($nameParts) >= 2) {
        // Vis kun fornavn og efternavn
        $firstName = $nameParts[0];
        $lastName = $nameParts[count($nameParts) - 1];
        $displayName = $firstName . " " . $lastName;
    } else {
        $displayName = $_SESSION['name'];
    }
}
?>
<!-- Sidebar for desktop -->
<aside class="w-64 bg-white shadow-md hidden md:block h-screen sticky top-0" style="width: 16rem; min-width: 16rem; max-width: 16rem;">
    <div class="flex items-center gap-3 text-primary font-bold text-xl p-6 border-b">
        <i class="fas fa-building text-2xl"></i>
        <span>KollegieAdmin</span>
    </div>
    <div class="py-4">
        <div class="px-6 py-3 mb-4">
            <div class="flex items-center gap-3 mb-1">
                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center flex-shrink-0 overflow-hidden">
                    <?php if (!empty($userProfileImage)): ?>
                        <img src="<?php echo htmlspecialchars($userProfileImage); ?>"
                            alt="Profilbillede"
                            class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="font-medium"><?php echo $userInitials; ?></span>
                    <?php endif; ?>
                </div>
                <div class="min-w-0">
                    <p class="font-medium truncate whitespace-nowrap"><?php echo $displayName; ?></p>
                    <p class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['role']); ?></p>
                </div>
            </div>
        </div>
        <ul class="space-y-1">
            <li>
                <a href="<?= $base ?>" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'dashboard' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>foodplan/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'foodplan' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-utensils"></i>
                    <span>Madplan</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>events/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'events' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Begivenheder</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>news/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'news' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>Nyheder</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>residents" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'residents' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-users"></i>
                    <span>Beboere</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>employees" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'employees' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-user-tie"></i>
                    <span>Ansatte</span>
                </a>
            </li>
            <li>
                <a href="<?= $base ?>settings/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'settings' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Indstillinger</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="absolute bottom-0 w-full p-6 border-t">
        <a href="<?= $base ?>login/logout.php" class="flex items-center gap-3 text-gray-700 hover:text-danger transition-colors">
            <i class="fas fa-sign-out-alt"></i>
            <span>Log ud</span>
        </a>
    </div>
</aside>

<!-- Mobile sidebar menu (hidden by default) -->
<div id="mobile-sidebar" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="bg-white w-64 h-full overflow-y-auto">
        <div class="flex items-center justify-between p-4 border-b">
            <div class="flex items-center gap-3 text-primary font-bold text-xl">
                <i class="fas fa-building text-2xl"></i>
                <span>KollegieAdmin</span>
            </div>
            <button id="close-mobile-menu" class="text-gray-700">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="py-4">
            <div class="px-4 py-3 mb-4">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center flex-shrink-0 overflow-hidden">
                        <?php if (!empty($userProfileImage)): ?>
                            <img src="<?php echo htmlspecialchars($userProfileImage); ?>"
                                alt="Profilbillede"
                                class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="font-medium"><?php echo $userInitials; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0"> <!-- Sikrer at indholdet ikke breder sig ud over containeren -->
                        <p class="font-medium truncate whitespace-nowrap"><?php echo $displayName; ?></p>
                        <p class="text-xs text-gray-500"><?php echo ucfirst($_SESSION['role']); ?></p>
                    </div>
                </div>
            </div>
            <ul class="space-y-1">
                <li>
                    <a href="<?= $base ?>" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'dashboard' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>foodplan/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'foodplan' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-utensils"></i>
                        <span>Madplan</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>events/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'events' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Begivenheder</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>news/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'news' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-newspaper"></i>
                        <span>Nyheder</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>residents" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'residents' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-users"></i>
                        <span>Beboere</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>employees" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'employees' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-user-tie"></i>
                        <span>Ansatte</span>
                    </a>
                </li>
                <li>
                    <a href="<?= $base ?>settings/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'settings' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Indstillinger</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="absolute bottom-0 w-full p-6 border-t">
            <a href="<?= $base ?>login/logout.php" class="flex items-center gap-3 text-gray-700 hover:text-danger transition-colors">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log ud</span>
            </a>
        </div>
    </div>
</div>

<script>
    // Mobile menu toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileSidebar = document.getElementById('mobile-sidebar');
    const closeMobileMenu = document.getElementById('close-mobile-menu');

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileSidebar.classList.remove('hidden');
        });
    }

    if (closeMobileMenu) {
        closeMobileMenu.addEventListener('click', () => {
            mobileSidebar.classList.add('hidden');
        });
    }

    // Close mobile menu when clicking outside
    mobileSidebar.addEventListener('click', (e) => {
        if (e.target === mobileSidebar) {
            mobileSidebar.classList.add('hidden');
        }
    });

    // User dropdown toggle
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', () => {
            userDropdown.classList.toggle('hidden');
        });

        // Close user dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });
    }
</script>