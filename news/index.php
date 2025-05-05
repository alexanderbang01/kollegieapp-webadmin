<?php 
$page = "news";
include '../components/header.php';
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- News content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Nyheder</h1>
                        <p class="text-sm sm:text-base text-gray-600">Administrer kollegiets nyheder og meddelelser</p>
                    </div>
                    <button class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Opret nyhed</span>
                    </button>
                </div>

                <!-- Search and filter -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <select class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                <option value="all">Alle kategorier</option>
                                <option value="important">Vigtige meddelelser</option>
                                <option value="events">Begivenheder</option>
                                <option value="maintenance">Vedligeholdelse</option>
                                <option value="general">Generelle nyheder</option>
                            </select>
                        </div>
                        
                        <div class="flex gap-3 w-full sm:w-auto">
                            <div class="relative flex-grow sm:w-64">
                                <input type="text" placeholder="Søg i nyheder..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 p-2 rounded-lg transition-colors">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Featured news -->
                <div class="mb-6">
                    <h2 class="text-lg font-bold mb-3">Fremhævede nyheder</h2>
                    <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in">
                        <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-danger/10 text-danger p-2 rounded-lg">
                                    <i class="fas fa-exclamation-circle text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Vigtig meddelelse: Renovering af badeværelser</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                        <span class="font-medium text-xs">AJ</span>
                                    </div>
                                    <span class="font-medium">Admin Jensen</span>
                                </div>
                                <div class="text-gray-500 text-sm">3. maj 2025 · 10:15</div>
                            </div>
                            <p class="text-gray-700 mb-4">
                                Der vil være renovering af badeværelserne på 2. og 3. etage i perioden 10.-17. maj. I denne periode vil I have adgang til midlertidige badefaciliteter i kælderen. Vi beklager ulejligheden og takker for jeres tålmodighed under renoveringen.
                            </p>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full">Vigtig</span>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full ml-1">Vedligeholdelse</span>
                                </div>
                                <div class="flex gap-3 text-gray-500 text-sm">
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-eye"></i>
                                        <span>98</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-comment"></i>
                                        <span>12</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- News list -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <!-- News item 1 -->
                    <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in delay-100">
                        <div class="p-4 border-b border-gray-100 bg-secondary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-secondary/10 text-secondary p-2 rounded-lg">
                                    <i class="fas fa-wifi text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Opgradering af Wi-Fi-netværk</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                        <span class="font-medium text-xs">AJ</span>
                                    </div>
                                    <span class="font-medium">Admin Jensen</span>
                                </div>
                                <div class="text-gray-500 text-sm">1. maj 2025 · 14:30</div>
                            </div>
                            <p class="text-gray-700 mb-4">
                                Vi har opgraderet kollegiets Wi-Fi-netværk til fiberforbindelse. Dette betyder at I nu skulle opleve markant hurtigere og mere stabil internetforbindelse på hele kollegiet.
                            </p>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Faciliteter</span>
                                </div>
                                <div class="flex gap-3 text-gray-500 text-sm">
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-eye"></i>
                                        <span>112</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-comment"></i>
                                        <span>8</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- News item 2 -->
                    <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in delay-200">
                        <div class="p-4 border-b border-gray-100 bg-accent/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-accent/10 text-accent p-2 rounded-lg">
                                    <i class="fas fa-tree text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Havedag d. 13. maj</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                        <span class="font-medium text-xs">AJ</span>
                                    </div>
                                    <span class="font-medium">Admin Jensen</span>
                                </div>
                                <div class="text-gray-500 text-sm">28. april 2025 · 09:45</div>
                            </div>
                            <p class="text-gray-700 mb-4">
                                Vi inviterer alle beboere til en fælles havedag lørdag d. 13. maj fra kl. 10:00-14:00. Vi skal plante nye blomster, ordne køkkenhaven og male havemøbler. Der vil være grillmad og forfriskninger til alle deltagere.
                            </p>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Begivenhed</span>
                                </div>
                                <div class="flex gap-3 text-gray-500 text-sm">
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-eye"></i>
                                        <span>87</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-comment"></i>
                                        <span>15</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- News item 3 -->
                    <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in delay-300">
                        <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 text-primary p-2 rounded-lg">
                                    <i class="fas fa-user-plus text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Velkommen til nye beboere</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                        <span class="font-medium text-xs">AJ</span>
                                    </div>
                                    <span class="font-medium">Admin Jensen</span>
                                </div>
                                <div class="text-gray-500 text-sm">25. april 2025 · 16:20</div>
                            </div>
                            <p class="text-gray-700 mb-4">
                                Vi byder velkommen til fem nye beboere, der er flyttet ind den sidste måned. Vi håber I vil tage godt imod dem. Der afholdes velkomstreception i fællesrummet på lørdag d. 6. maj kl. 15:00.
                            </p>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Beboere</span>
                                </div>
                                <div class="flex gap-3 text-gray-500 text-sm">
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-eye"></i>
                                        <span>102</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-comment"></i>
                                        <span>23</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- News item 4 -->
                    <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in delay-400">
                        <div class="p-4 border-b border-gray-100 bg-gray-100 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-gray-200 text-gray-700 p-2 rounded-lg">
                                    <i class="fas fa-broom text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800">Rengøring af fællesområder</h3>
                            </div>
                            <div class="flex gap-2">
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                                <button class="text-gray-400 hover:text-danger transition-colors">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="text-gray-400 hover:text-primary transition-colors">
                                    <i class="fas fa-thumbtack"></i>
                                </button>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                        <span class="font-medium text-xs">AJ</span>
                                    </div>
                                    <span class="font-medium">Admin Jensen</span>
                                </div>
                                <div class="text-gray-500 text-sm">22. april 2025 · 11:10</div>
                            </div>
                            <p class="text-gray-700 mb-4">
                                Husk at nye rengøringslister for maj måned er hængt op i alle køkkener. Vi minder om, at alle skal deltage i rengøringen af fællesområderne efter den fastlagte turnus.
                            </p>
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded-full">Husorden</span>
                                </div>
                                <div class="flex gap-3 text-gray-500 text-sm">
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-eye"></i>
                                        <span>78</span>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <i class="far fa-comment"></i>
                                        <span>3</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div class="flex justify-between items-center">
                    <div class="text-gray-500 text-sm">
                        Viser 1-5 af 12 nyheder
                    </div>
                    <div class="flex gap-1">
                        <button class="w-8 h-8 rounded flex items-center justify-center bg-gray-200 text-gray-400 cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="w-8 h-8 rounded flex items-center justify-center bg-primary text-white">
                            1
                        </button>
                        <button class="w-8 h-8 rounded flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors">
                            2
                        </button>
                        <button class="w-8 h-8 rounded flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors">
                            3
                        </button>
                        <button class="w-8 h-8 rounded flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Hvis der er behov for særlig JavaScript-funktionalitet til nyhedssiden
        document.addEventListener('DOMContentLoaded', function() {
            // Thumbnail-håndtering eller anden funktionalitet kan tilføjes her
        });
    </script>
</body>
</html>