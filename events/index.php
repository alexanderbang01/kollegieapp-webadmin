<?php 
$page = "events";
include '../components/header.php';
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Events content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Begivenheder</h1>
                        <p class="text-sm sm:text-base text-gray-600">Administrer kollegiets begivenheder</p>
                    </div>
                    <button class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Opret begivenhed</span>
                    </button>
                </div>

                <!-- Month navigation and search tools -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <button id="prev-month" class="text-gray-600 hover:text-primary transition-colors text-xl">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h2 class="font-bold text-lg">Maj 2025</h2>
                            <button id="next-month" class="text-gray-600 hover:text-primary transition-colors text-xl">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="flex gap-3 w-full sm:w-auto">
                            <div class="relative flex-grow sm:w-64">
                                <input type="text" placeholder="Søg i begivenheder..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 p-2 rounded-lg transition-colors">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Layout controls -->
                <div class="mb-4 flex justify-end">
                    <div class="bg-white rounded-lg shadow p-2 flex gap-2">
                        <button id="layout-1" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100" title="1 begivenhed pr. række">
                            <i class="fas fa-list"></i>
                        </button>
                        <button id="layout-2" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100" title="2 begivenheder pr. række">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button id="layout-3" class="w-8 h-8 flex items-center justify-center rounded bg-primary text-white" title="3 begivenheder pr. række">
                            <i class="fas fa-th"></i>
                        </button>
                    </div>
                </div>

                <!-- Events Grid -->
                <div id="events-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- Event 1 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-100">
                        <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 text-primary p-2 rounded-lg">
                                    <i class="fas fa-film text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Filmaften</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-col mb-3">
                                <div class="flex items-center gap-2 text-primary font-medium">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Fredag, 5. maj</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 mt-1">
                                    <i class="far fa-clock"></i>
                                    <span>20:00</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Fællesrummet, Stueetagen</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-user-friends"></i>
                                    <span>15 tilmeldte / Max 25</span>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">Vi ser filmen "Dune: Part Two" på storskærm med popcorn og sodavand. Kom og hyg med!</p>
                            <div class="flex justify-between">
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Godkendt</span>
                                <a href="#" class="text-primary text-sm hover:underline">Se detaljer</a>
                            </div>
                        </div>
                    </div>

                    <!-- Event 2 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-200">
                        <div class="p-4 border-b border-gray-100 bg-secondary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-secondary/10 text-secondary p-2 rounded-lg">
                                    <i class="fas fa-chess-board text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Brætspilsaften</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-col mb-3">
                                <div class="flex items-center gap-2 text-secondary font-medium">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Lørdag, 6. maj</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 mt-1">
                                    <i class="far fa-clock"></i>
                                    <span>19:00</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Fællesrummet, 3. etage</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-user-friends"></i>
                                    <span>8 tilmeldte / Ubegrænset</span>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">Tag dit yndlingsbrætspil med eller kom og spil et af vores spil. Alle er velkomne!</p>
                            <div class="flex justify-between">
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Godkendt</span>
                                <a href="#" class="text-primary text-sm hover:underline">Se detaljer</a>
                            </div>
                        </div>
                    </div>

                    <!-- Event 3 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-300">
                        <div class="p-4 border-b border-gray-100 bg-accent/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-accent/10 text-accent p-2 rounded-lg">
                                    <i class="fas fa-comments text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Generalforsamling</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-col mb-3">
                                <div class="flex items-center gap-2 text-accent font-medium">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Søndag, 14. maj</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 mt-1">
                                    <i class="far fa-clock"></i>
                                    <span>14:00</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Fællesrummet, Stueetagen</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-user-friends"></i>
                                    <span>32 tilmeldte / Ubegrænset</span>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">Årlig generalforsamling med valg til bestyrelsen. Revideret dagsorden udsendes en uge før.</p>
                            <div class="flex justify-between">
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Godkendt</span>
                                <a href="#" class="text-primary text-sm hover:underline">Se detaljer</a>
                            </div>
                        </div>
                    </div>

                    <!-- Event 4 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-400">
                        <div class="p-4 border-b border-gray-100 bg-gray-100 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-gray-200 text-gray-700 p-2 rounded-lg">
                                    <i class="fas fa-utensils text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Fællesspisning</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-col mb-3">
                                <div class="flex items-center gap-2 text-gray-700 font-medium">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Onsdag, 17. maj</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 mt-1">
                                    <i class="far fa-clock"></i>
                                    <span>18:30</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Spisesalen, Stueetagen</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-user-friends"></i>
                                    <span>12 tilmeldte / Max 40</span>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">Ekstra fællesspisning med tema "Mexicansk". Tilmelding senest dagen før kl. 12:00.</p>
                            <div class="flex justify-between">
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Godkendt</span>
                                <a href="#" class="text-primary text-sm hover:underline">Se detaljer</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Event 5 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-100">
                        <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 text-primary p-2 rounded-lg">
                                    <i class="fas fa-gamepad text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">FIFA Turnering</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-col mb-3">
                                <div class="flex items-center gap-2 text-primary font-medium">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Lørdag, 20. maj</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 mt-1">
                                    <i class="far fa-clock"></i>
                                    <span>19:00</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Fællesrummet, 2. etage</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-user-friends"></i>
                                    <span>10 tilmeldte / Max 16</span>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">FIFA turnering på PS5. Tilmelding nødvendig. Der vil være præmier til top 3.</p>
                            <div class="flex justify-between">
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Godkendt</span>
                                <a href="#" class="text-primary text-sm hover:underline">Se detaljer</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Event 6 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-200">
                        <div class="p-4 border-b border-gray-100 bg-secondary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-secondary/10 text-secondary p-2 rounded-lg">
                                    <i class="fas fa-running text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Yoga i parken</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex flex-col mb-3">
                                <div class="flex items-center gap-2 text-secondary font-medium">
                                    <i class="fas fa-calendar-alt"></i>
                                    <span>Søndag, 21. maj</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600 mt-1">
                                    <i class="far fa-clock"></i>
                                    <span>10:00</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center gap-2 text-gray-600 mb-1">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span>Østre Anlæg, ved søen</span>
                                </div>
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="fas fa-user-friends"></i>
                                    <span>8 tilmeldte / Ubegrænset</span>
                                </div>
                            </div>
                            <p class="text-gray-600 text-sm mb-3">Yoga for begyndere i parken. Medbring et tæppe eller yogamåtte. Alle niveauer er velkomne.</p>
                            <div class="flex justify-between">
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Godkendt</span>
                                <a href="#" class="text-primary text-sm hover:underline">Se detaljer</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Month navigation
        const prevMonthBtn = document.getElementById('prev-month');
        const nextMonthBtn = document.getElementById('next-month');
        
        const months = ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December'];
        let currentMonthIndex = 4; // Maj (0-baseret indeks)
        let currentYear = 2025;
        const monthTitle = document.querySelector('h2');
        
        prevMonthBtn.addEventListener('click', () => {
            currentMonthIndex--;
            if (currentMonthIndex < 0) {
                currentMonthIndex = 11;
                currentYear--;
            }
            updateMonthDisplay();
        });
        
        nextMonthBtn.addEventListener('click', () => {
            currentMonthIndex++;
            if (currentMonthIndex > 11) {
                currentMonthIndex = 0;
                currentYear++;
            }
            updateMonthDisplay();
        });
        
        function updateMonthDisplay() {
            monthTitle.textContent = `${months[currentMonthIndex]} ${currentYear}`;
            // Her ville du normalt hente nye data for den valgte måned
            // via AJAX eller lignende
        }
        
        // Layout-skift funktionalitet
        const layout1Btn = document.getElementById('layout-1');
        const layout2Btn = document.getElementById('layout-2');
        const layout3Btn = document.getElementById('layout-3');
        const eventsGrid = document.getElementById('events-grid');
        
        layout1Btn.addEventListener('click', () => {
            eventsGrid.className = 'grid grid-cols-1 gap-4 mb-6';
            setActiveLayout(layout1Btn);
        });
        
        layout2Btn.addEventListener('click', () => {
            eventsGrid.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-6';
            setActiveLayout(layout2Btn);
        });
        
        layout3Btn.addEventListener('click', () => {
            eventsGrid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6';
            setActiveLayout(layout3Btn);
        });
        
        function setActiveLayout(button) {
            // Fjern aktiv klasse fra alle knapper
            [layout1Btn, layout2Btn, layout3Btn].forEach(btn => {
                btn.className = 'w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100';
            });
            
            // Tilføj aktiv klasse til den valgte knap
            button.className = 'w-8 h-8 flex items-center justify-center rounded bg-primary text-white';
        }
    </script>
</body>
</html>