<?php 
$page = "foodplan";
include '../components/header.php';
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
                    <button class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Opret ny</span>
                    </button>
                </div>

                <!-- Week selector -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex justify-center items-center gap-4">
                        <button id="prev-week" class="text-gray-600 hover:text-primary transition-colors text-xl">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <h2 class="font-bold text-lg">Uge 18 • Maj 2025</h2>
                        <button id="next-week" class="text-gray-600 hover:text-primary transition-colors text-xl">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Current week meals -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
                    <!-- Monday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-100">
                        <div class="p-3 sm:p-4 border-b border-gray-100">
                            <h3 class="font-bold text-primary">Mandag <span class="text-gray-500">1. maj</span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs text-gray-500">18:00</span>
                                <div class="flex gap-1">
                                    <button class="text-gray-400 hover:text-primary transition-colors">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-danger transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-bold mb-1">Lasagne med salat</h4>
                            <p class="text-gray-600 text-sm mb-2">Hjemmelavet lasagne med oksekød og grøn salat.</p>
                            <div class="flex flex-wrap gap-1">
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tuesday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-200">
                        <div class="p-3 sm:p-4 border-b border-gray-100">
                            <h3 class="font-bold text-primary">Tirsdag <span class="text-gray-500">2. maj</span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs text-gray-500">18:00</span>
                                <div class="flex gap-1">
                                    <button class="text-gray-400 hover:text-primary transition-colors">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-danger transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-bold mb-1">Kylling i karry</h4>
                            <p class="text-gray-600 text-sm mb-2">Kylling i karrysauce med ris og nanbrød.</p>
                            <div class="flex flex-wrap gap-1">
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                            </div>
                        </div>
                    </div>

                    <!-- Wednesday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-300">
                        <div class="p-3 sm:p-4 border-b border-gray-100 bg-primary/10">
                            <h3 class="font-bold text-primary">Onsdag <span class="text-gray-500">3. maj</span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs text-gray-500">18:00</span>
                                <div class="flex gap-1">
                                    <button class="text-gray-400 hover:text-primary transition-colors">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-danger transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-bold mb-1">Pasta Carbonara</h4>
                            <p class="text-gray-600 text-sm mb-2">Klassisk italiensk ret med bacon, æg og parmesan.</p>
                            <div class="flex flex-wrap gap-1">
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Æg</span>
                            </div>
                        </div>
                    </div>

                    <!-- Thursday -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-400">
                        <div class="p-3 sm:p-4 border-b border-gray-100">
                            <h3 class="font-bold text-primary">Torsdag <span class="text-gray-500">4. maj</span></h3>
                        </div>
                        <div class="p-3 sm:p-4">
                            <div class="flex justify-between items-start mb-3">
                                <span class="text-xs text-gray-500">18:00</span>
                                <div class="flex gap-1">
                                    <button class="text-gray-400 hover:text-primary transition-colors">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-400 hover:text-danger transition-colors">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <h4 class="font-bold mb-1">Taco torsdag</h4>
                            <p class="text-gray-600 text-sm mb-2">Tacos med oksekød, salsa, guacamole og tilbehør.</p>
                            <div class="flex flex-wrap gap-1">
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Gluten</span>
                                <span class="px-2 py-1 bg-gray-200 text-gray-700 text-xs rounded-full">Laktose</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Week navigation
        const prevWeekBtn = document.getElementById('prev-week');
        const nextWeekBtn = document.getElementById('next-week');
        
        let currentWeek = 18;
        const weekTitle = document.querySelector('h2');
        
        prevWeekBtn.addEventListener('click', () => {
            currentWeek--;
            updateWeekDisplay();
        });
        
        nextWeekBtn.addEventListener('click', () => {
            currentWeek++;
            updateWeekDisplay();
        });
        
        function updateWeekDisplay() {
            weekTitle.textContent = `Uge ${currentWeek} • Maj 2025`;
            // Her ville du normalt hente nye data for den valgte uge
            // via AJAX eller lignende
        }
    </script>
</body>
</html>