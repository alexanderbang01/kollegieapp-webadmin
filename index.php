<?php include 'components/header.php'; ?>
<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include 'components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Top header -->
            <header class="bg-white shadow-sm p-4 flex items-center justify-between md:justify-end">
                <!-- Mobile menu button -->
                <button id="mobile-menu-btn" class="md:hidden text-gray-700 text-xl">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button class="text-gray-700 p-2 rounded-full hover:bg-gray-100 transition-colors relative">
                            <i class="fas fa-bell text-xl"></i>
                            <span class="absolute top-1 right-1 flex h-4 w-4">
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-danger"></span>
                                <span class="animate-ping-slow absolute inline-flex h-full w-full rounded-full bg-danger opacity-75"></span>
                            </span>
                        </button>
                    </div>
                    
                    <!-- User dropdown -->
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center gap-2 text-gray-700 hover:text-primary transition-colors">
                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                <span class="font-medium text-sm">AJ</span>
                            </div>
                            <span class="hidden sm:inline font-medium">Admin Jensen</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <!-- User dropdown menu -->
                        <div id="user-dropdown" class="absolute right-0 mt-2 w-48 bg-white shadow-lg rounded-md hidden z-10">
                            <ul class="py-1">
                                <li>
                                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-user mr-2"></i>Min profil
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                        <i class="fas fa-cog mr-2"></i>Indstillinger
                                    </a>
                                </li>
                                <li>
                                    <hr class="my-1">
                                </li>
                                <li>
                                    <a href="#" class="block px-4 py-2 text-danger hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Log ud
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard content -->
            <div class="p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Dashboard</h1>
                    <p class="text-gray-600">Velkommen tilbage, Admin Jensen</p>
                </div>

                <!-- Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Beboere Card -->
                    <div class="bg-white rounded-xl p-6 shadow animate-fade-in delay-100 flex items-center">
                        <div class="rounded-full p-3 bg-primary/10 text-primary mr-4">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Totale Beboere</p>
                            <p class="text-2xl font-bold">112</p>
                        </div>
                    </div>
                    
                    <!-- Aktive Begivenheder Card -->
                    <div class="bg-white rounded-xl p-6 shadow animate-fade-in delay-200 flex items-center">
                        <div class="rounded-full p-3 bg-secondary/10 text-secondary mr-4">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Aktive Begivenheder</p>
                            <p class="text-2xl font-bold">24</p>
                        </div>
                    </div>
                    
                    <!-- Madplan Status Card -->
                    <div class="bg-white rounded-xl p-6 shadow animate-fade-in delay-300 flex items-center">
                        <div class="rounded-full p-3 bg-accent/10 text-accent mr-4">
                            <i class="fas fa-utensils text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Madplan Status</p>
                            <p class="text-2xl font-bold">
                                <span class="text-secondary">Ajourført</span>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Aktive Anmodninger Card -->
                    <div class="bg-white rounded-xl p-6 shadow animate-fade-in delay-400 flex items-center">
                        <div class="rounded-full p-3 bg-danger/10 text-danger mr-4">
                            <i class="fas fa-clipboard-list text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm">Aktive Anmodninger</p>
                            <p class="text-2xl font-bold">5</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity and Quick Actions -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Activity -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow p-6 animate-fade-in delay-100">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold">Seneste Aktiviteter</h2>
                                <a href="#" class="text-primary hover:underline text-sm">Se alle</a>
                            </div>
                            <div class="space-y-4">
                                <div class="flex items-start gap-4">
                                    <div class="rounded-full p-2 bg-primary/10 text-primary">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">Ny begivenhed oprettet</p>
                                        <p class="text-gray-600 text-sm">Filmaften er planlagt til fredag d. 15. maj</p>
                                        <p class="text-gray-400 text-xs mt-1">For 2 timer siden</p>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 my-2"></div>
                                <div class="flex items-start gap-4">
                                    <div class="rounded-full p-2 bg-secondary/10 text-secondary">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">Madplan opdateret</p>
                                        <p class="text-gray-600 text-sm">Madplanen for uge 20 er blevet opdateret</p>
                                        <p class="text-gray-400 text-xs mt-1">For 5 timer siden</p>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 my-2"></div>
                                <div class="flex items-start gap-4">
                                    <div class="rounded-full p-2 bg-accent/10 text-accent">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">Ny beboer tilføjet</p>
                                        <p class="text-gray-600 text-sm">Mikkel Hansen er flyttet ind på værelse B12</p>
                                        <p class="text-gray-400 text-xs mt-1">For 1 dag siden</p>
                                    </div>
                                </div>
                                <div class="border-t border-gray-200 my-2"></div>
                                <div class="flex items-start gap-4">
                                    <div class="rounded-full p-2 bg-danger/10 text-danger">
                                        <i class="fas fa-exclamation-circle"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium">Vedligeholdelsesanmodning</p>
                                        <p class="text-gray-600 text-sm">Vandhane i køkken 3 drypper og skal repareres</p>
                                        <p class="text-gray-400 text-xs mt-1">For 1 dag siden</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow p-6 animate-fade-in delay-200">
                            <h2 class="text-xl font-bold mb-6">Hurtige Handlinger</h2>
                            <div class="space-y-3">
                                <a href="foodplan/index.html" class="flex items-center gap-4 p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="rounded-full p-2 bg-primary/10 text-primary">
                                        <i class="fas fa-utensils"></i>
                                    </div>
                                    <span class="font-medium">Opdater madplan</span>
                                </a>
                                <a href="events/index.html" class="flex items-center gap-4 p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="rounded-full p-2 bg-primary/10 text-primary">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <span class="font-medium">Opret begivenhed</span>
                                </a>
                                <a href="news/index.html" class="flex items-center gap-4 p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="rounded-full p-2 bg-primary/10 text-primary">
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                    <span class="font-medium">Tilføj nyhed</span>
                                </a>
                                <a href="#" class="flex items-center gap-4 p-3 rounded-lg bg-primary/5 hover:bg-primary/10 transition-colors">
                                    <div class="rounded-full p-2 bg-primary/10 text-primary">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <span class="font-medium">Send meddelelse</span>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Tasks Card -->
                        <div class="bg-white rounded-xl shadow p-6 mt-6 animate-fade-in delay-300">
                            <div class="flex justify-between items-center mb-6">
                                <h2 class="text-xl font-bold">Opgaver</h2>
                                <span class="text-primary bg-primary/10 text-xs font-medium px-2 py-1 rounded-full">5 aktive</span>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary">
                                    <span class="text-gray-700">Opdater madplan for næste uge</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary">
                                    <span class="text-gray-700">Godkend nye begivenheder</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary" checked>
                                    <span class="text-gray-400 line-through">Send velkommen til nye beboere</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary">
                                    <span class="text-gray-700">Bestil varer til køkkenet</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="w-4 h-4 rounded text-primary focus:ring-primary">
                                    <span class="text-gray-700">Tjek vedligeholdelsesanmodninger</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Madplan og Kommende Begivenheder -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-8">
                    <!-- Dagens Madplan -->
                    <div class="bg-white rounded-xl shadow p-6 animate-fade-in delay-100">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold">Dagens Madplan</h2>
                            <a href="foodplan/index.html" class="text-primary hover:underline text-sm">Redigér</a>
                        </div>
                        <div class="bg-primary/5 rounded-lg p-4 mb-4">
                            <div class="flex justify-between mb-2">
                                <span class="font-medium">Onsdag, 3. maj</span>
                                <span class="text-primary font-medium">18:00</span>
                            </div>
                            <h3 class="text-lg font-bold mb-1">Pasta Carbonara</h3>
                            <p class="text-gray-600 text-sm">Klassisk italiensk ret med bacon, æg og parmesan.</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Æg</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Kommende Begivenheder -->
                    <div class="bg-white rounded-xl shadow p-6 animate-fade-in delay-200">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-bold">Kommende Begivenheder</h2>
                            <a href="events/index.html" class="text-primary hover:underline text-sm">Se alle</a>
                        </div>
                        <div class="space-y-4">
                            <div class="border-l-4 border-primary pl-4">
                                <div class="flex justify-between">
                                    <span class="text-primary font-medium">Fredag, 5. maj</span>
                                    <span class="text-gray-500 text-sm">20:00</span>
                                </div>
                                <h3 class="font-bold">Filmaften</h3>
                                <p class="text-gray-600 text-sm">Fællesrummet • 15 tilmeldte</p>
                            </div>
                            <div class="border-l-4 border-secondary pl-4">
                                <div class="flex justify-between">
                                    <span class="text-secondary font-medium">Lørdag, 6. maj</span>
                                    <span class="text-gray-500 text-sm">19:00</span>
                                </div>
                                <h3 class="font-bold">Brætspilsaften</h3>
                                <p class="text-gray-600 text-sm">Fællesrummet, 3. etage • 8 tilmeldte</p>
                            </div>
                            <div class="border-l-4 border-accent pl-4">
                                <div class="flex justify-between">
                                    <span class="text-accent font-medium">Søndag, 14. maj</span>
                                    <span class="text-gray-500 text-sm">14:00</span>
                                </div>
                                <h3 class="font-bold">Generalforsamling</h3>
                                <p class="text-gray-600 text-sm">Fællesrummet • 32 tilmeldte</p>
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