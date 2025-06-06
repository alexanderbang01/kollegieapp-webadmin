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
                <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                    <span class="font-medium">AJ</span>
                </div>
                <div>
                    <p class="font-medium">Admin Jensen</p>
                    <p class="text-xs text-gray-500">Administrator</p>
                </div>
            </div>
        </div>
        <ul class="space-y-1">
            <li>
                <a href="<?=$base?>" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'dashboard' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>foodplan/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'foodplan' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-utensils"></i>
                    <span>Madplan</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>events/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'events' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Begivenheder</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>news/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'news' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-newspaper"></i>
                    <span>Nyheder</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>residents" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'residents' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-users"></i>
                    <span>Beboere</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>employee" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'employee' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fa-solid fa-address-card"></i>
                    <span>Ansatte</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>messages" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'messages' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fa-solid fa-comments"></i>
                    <span>Beskeder</span>
                </a>
            </li>
            <li>
                <a href="<?=$base?>settings/" class="flex items-center gap-3 px-6 py-3 <?php echo $page === 'settings' ? 'bg-primary/10 text-primary font-medium border-r-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Indstillinger</span>
                </a>
            </li>
        </ul>
    </div>
    <div class="absolute bottom-0 w-full p-6 border-t">
        <a href="<?=$base?>login/logout.php" class="flex items-center gap-3 text-gray-700 hover:text-danger transition-colors">
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
                    <div class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center">
                        <span class="font-medium">AJ</span>
                    </div>
                    <div>
                        <p class="font-medium">Admin Jensen</p>
                        <p class="text-xs text-gray-500">Administrator</p>
                    </div>
                </div>
            </div>
            <ul class="space-y-1">
                <li>
                    <a href="<?=$base?>" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'dashboard' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="<?=$base?>foodplan/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'foodplan' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-utensils"></i>
                        <span>Madplan</span>
                    </a>
                </li>
                <li>
                    <a href="<?=$base?>events/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'events' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Begivenheder</span>
                    </a>
                </li>
                <li>
                    <a href="<?=$base?>news/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'news' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-newspaper"></i>
                        <span>Nyheder</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'residents' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-users"></i>
                        <span>Beboere</span>
                    </a>
                </li>
                <li>
                    <a href="<?=$base?>settings/" class="flex items-center gap-3 px-4 py-3 <?php echo $page === 'settings' ? 'bg-primary/10 text-primary font-medium border-l-4 border-primary' : 'text-gray-700 hover:bg-gray-100 transition-colors'; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Indstillinger</span>
                    </a>
                </li>
            </ul>
        </div>
        <div class="absolute bottom-0 w-full p-6 border-t">
            <a href="<?=$base?>login/logout.php" class="flex items-center gap-3 text-gray-700 hover:text-danger transition-colors">
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

    // User dropdown toggle
    const userMenuBtn = document.getElementById('user-menu-btn');
    const userDropdown = document.getElementById('user-dropdown');

    userMenuBtn.addEventListener('click', () => {
        userDropdown.classList.toggle('hidden');
    });

    // Close user dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
            userDropdown.classList.add('hidden');
        }
    });
</script>