<?php
$page = 'dashboard';
include 'components/header.php'; ?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include 'components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Dashboard content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-sm sm:text-base text-gray-600">Velkommen tilbage, Admin Jensen</p>
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
                            <p class="text-xl sm:text-2xl font-bold">112</p>
                        </div>
                    </div>

                    <!-- Aktive Begivenheder Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-200 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-secondary/10 text-secondary mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Aktive Begivenheder</p>
                            <p class="text-xl sm:text-2xl font-bold">24</p>
                        </div>
                    </div>

                    <!-- Madplan Status Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-300 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-accent/10 text-accent mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-utensils text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Madplan Status</p>
                            <p class="text-xl sm:text-2xl font-bold">
                                <span class="text-secondary">Ajourført</span>
                            </p>
                        </div>
                    </div>

                    <!-- Aktive Anmodninger Card -->
                    <div class="bg-white rounded-xl p-4 sm:p-6 shadow animate-fade-in delay-400 flex items-center">
                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-danger/10 text-danger mr-3 sm:mr-4 flex items-center justify-center">
                            <i class="fas fa-clipboard-list text-lg sm:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs sm:text-sm">Aktive Anmodninger</p>
                            <p class="text-xl sm:text-2xl font-bold">5</p>
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
                            <div class="overflow-x-auto">
                                <table class="w-full min-w-full">
                                    <thead>
                                        <tr class="text-left">
                                            <th class="pb-2 text-sm font-medium text-gray-500">Dag</th>
                                            <th class="pb-2 text-sm font-medium text-gray-500">Ret</th>
                                            <th class="pb-2 text-sm font-medium text-gray-500">Tidspunkt</th>
                                            <th class="pb-2 text-sm font-medium text-gray-500">Allergener</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Mandag -->
                                        <tr class="border-t border-gray-100">
                                            <td class="py-3 pr-2">
                                                <p class="font-medium text-gray-800">Mandag</p>
                                                <p class="text-xs text-gray-500">1. maj</p>
                                            </td>
                                            <td class="py-3 pr-2">
                                                <p class="font-medium">Lasagne med salat</p>
                                                <p class="text-xs text-gray-500">Hjemmelavet lasagne med oksekød og grøn salat</p>
                                            </td>
                                            <td class="py-3 pr-2 text-primary">18:00</td>
                                            <td class="py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Tirsdag -->
                                        <tr class="border-t border-gray-100">
                                            <td class="py-3 pr-2">
                                                <p class="font-medium text-gray-800">Tirsdag</p>
                                                <p class="text-xs text-gray-500">2. maj</p>
                                            </td>
                                            <td class="py-3 pr-2">
                                                <p class="font-medium">Kylling i karry</p>
                                                <p class="text-xs text-gray-500">Kylling i karrysauce med ris og nanbrød</p>
                                            </td>
                                            <td class="py-3 pr-2 text-primary">18:00</td>
                                            <td class="py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Onsdag (aktiv dag) -->
                                        <tr class="border-t border-gray-100 bg-primary/5">
                                            <td class="py-3 pr-2 border-l-4 border-primary pl-2">
                                                <p class="font-medium text-gray-800">Onsdag</p>
                                                <p class="text-xs text-gray-500">3. maj</p>
                                            </td>
                                            <td class="py-3 pr-2">
                                                <p class="font-medium">Pasta Carbonara</p>
                                                <p class="text-xs text-gray-500">Klassisk italiensk ret med bacon, æg og parmesan</p>
                                            </td>
                                            <td class="py-3 pr-2 text-primary">18:00</td>
                                            <td class="py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Æg</span>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Torsdag -->
                                        <tr class="border-t border-gray-100">
                                            <td class="py-3 pr-2">
                                                <p class="font-medium text-gray-800">Torsdag</p>
                                                <p class="text-xs text-gray-500">4. maj</p>
                                            </td>
                                            <td class="py-3 pr-2">
                                                <p class="font-medium">Taco torsdag</p>
                                                <p class="text-xs text-gray-500">Tacos med oksekød, salsa, guacamole og tilbehør</p>
                                            </td>
                                            <td class="py-3 pr-2 text-primary">18:00</td>
                                            <td class="py-3">
                                                <div class="flex flex-wrap gap-1">
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                                    <span class="px-1.5 py-0.5 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
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

                <!-- Kommende Begivenheder og Seneste Aktiviteter Byttet -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-6 mt-4 sm:mt-8">
                    <!-- Kommende Begivenheder -->
                    <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in delay-100">
                        <div class="flex justify-between items-center mb-4 sm:mb-6">
                            <h2 class="text-lg sm:text-xl font-bold">Kommende Begivenheder</h2>
                            <a href="<?= $base ?>events/" class="text-primary hover:underline text-xs sm:text-sm">Se alle</a>
                        </div>
                        <div class="space-y-3 sm:space-y-4">
                            <div class="border-l-4 border-primary pl-2 sm:pl-4">
                                <div class="flex justify-between">
                                    <span class="text-primary font-medium text-xs sm:text-sm">Fredag, 5. maj</span>
                                    <span class="text-gray-500 text-xs">20:00</span>
                                </div>
                                <h3 class="font-bold text-sm sm:text-base">Filmaften</h3>
                                <p class="text-gray-600 text-xs">Fællesrummet • 15 tilmeldte</p>
                            </div>
                            <div class="border-l-4 border-secondary pl-2 sm:pl-4">
                                <div class="flex justify-between">
                                    <span class="text-secondary font-medium text-xs sm:text-sm">Lørdag, 6. maj</span>
                                    <span class="text-gray-500 text-xs">19:00</span>
                                </div>
                                <h3 class="font-bold text-sm sm:text-base">Brætspilsaften</h3>
                                <p class="text-gray-600 text-xs">Fællesrummet, 3. etage • 8 tilmeldte</p>
                            </div>
                            <div class="border-l-4 border-accent pl-2 sm:pl-4">
                                <div class="flex justify-between">
                                    <span class="text-accent font-medium text-xs sm:text-sm">Søndag, 14. maj</span>
                                    <span class="text-gray-500 text-xs">14:00</span>
                                </div>
                                <h3 class="font-bold text-sm sm:text-base">Generalforsamling</h3>
                                <p class="text-gray-600 text-xs">Fællesrummet • 32 tilmeldte</p>
                            </div>
                        </div>
                    </div>

                    <!-- Seneste Aktiviteter -->
                    <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in delay-200">
                        <div class="flex justify-between items-center mb-4 sm:mb-6">
                            <h2 class="text-lg sm:text-xl font-bold">Seneste Aktiviteter</h2>
                            <a href="#" class="text-primary hover:underline text-xs sm:text-sm">Se alle</a>
                        </div>
                        <div class="space-y-3 sm:space-y-4">
                            <div class="flex items-start gap-2 sm:gap-4">
                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center">
                                    <i class="fas fa-calendar-plus text-sm sm:text-base"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-sm sm:text-base">Ny begivenhed oprettet</p>
                                    <p class="text-gray-600 text-xs sm:text-sm">Filmaften er planlagt til fredag d. 15. maj</p>
                                    <p class="text-gray-400 text-xs mt-1">For 2 timer siden</p>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 my-2"></div>
                            <div class="flex items-start gap-2 sm:gap-4">
                                <div class="w-8 h-8 rounded-full bg-secondary/10 text-secondary flex items-center justify-center">
                                    <i class="fas fa-utensils text-sm sm:text-base"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-sm sm:text-base">Madplan opdateret</p>
                                    <p class="text-gray-600 text-xs sm:text-sm">Madplanen for uge 20 er blevet opdateret</p>
                                    <p class="text-gray-400 text-xs mt-1">For 5 timer siden</p>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 my-2"></div>
                            <div class="flex items-start gap-2 sm:gap-4">
                                <div class="w-8 h-8 rounded-full bg-accent/10 text-accent flex items-center justify-center">
                                    <i class="fas fa-user-plus text-sm sm:text-base"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-sm sm:text-base">Ny beboer tilføjet</p>
                                    <p class="text-gray-600 text-xs sm:text-sm">Mikkel Hansen er flyttet ind på værelse B12</p>
                                    <p class="text-gray-400 text-xs mt-1">For 1 dag siden</p>
                                </div>
                            </div>
                        </div>
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
</body>

</html>