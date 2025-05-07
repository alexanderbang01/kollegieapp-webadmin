<?php
$page = "residents";
include '../components/header.php';
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Residents content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Beboere</h1>
                        <p class="text-sm sm:text-base text-gray-600">Administrer kollegiets beboere</p>
                    </div>
                </div>

                <!-- Search and filter -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4">
                            <select class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                <option value="all">Alle etager</option>
                                <option value="ground">Stuen</option>
                                <option value="1st">1. etage</option>
                                <option value="2nd">2. etage</option>
                                <option value="3rd">3. etage</option>
                                <option value="4th">4. etage</option>
                            </select>
                        </div>

                        <div class="flex gap-3 w-full sm:w-auto">
                            <div class="relative flex-grow sm:w-64">
                                <input type="text" placeholder="Søg efter beboer..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 p-2 rounded-lg transition-colors">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- View toggle -->
                <div class="mb-4 flex justify-end">
                    <div class="bg-white rounded-lg shadow p-2 flex gap-2">
                        <button id="view-grid" class="w-8 h-8 flex items-center justify-center rounded bg-primary text-white" title="Grid visning">
                            <i class="fas fa-th"></i>
                        </button>
                        <button id="view-list" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100" title="Listevisning">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <!-- Residents Grid View -->
                <div id="residents-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <!-- Resident 1 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-100 overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-lg font-medium">
                                    MH
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Mikkel Hansen</h3>
                                    <p class="text-sm text-gray-500">Værelse B12 • Stuen</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm">mikkel.hansen@example.com</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Telefon</p>
                                    <p class="text-sm">+45 12 34 56 78</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Værelse</p>
                                    <p class="text-sm">B12 - Stuen</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Uddannelse</p>
                                    <p class="text-sm">Datamatiker</p>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resident 2 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-200 overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-secondary text-white flex items-center justify-center text-lg font-medium">
                                    LJ
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Laura Jensen</h3>
                                    <p class="text-sm text-gray-500">Værelse A05 • 1. etage</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm">laura.jensen@example.com</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Telefon</p>
                                    <p class="text-sm">+45 23 45 67 89</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Værelse</p>
                                    <p class="text-sm">A05 - 1. etage</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Uddannelse</p>
                                    <p class="text-sm">Sygeplejerske</p>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resident 3 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-300 overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-accent text-white flex items-center justify-center text-lg font-medium">
                                    AP
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Anders Petersen</h3>
                                    <p class="text-sm text-gray-500">Værelse C21 • 2. etage</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm">anders.p@example.com</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Telefon</p>
                                    <p class="text-sm">+45 34 56 78 90</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Værelse</p>
                                    <p class="text-sm">C21 - 2. etage</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Uddannelse</p>
                                    <p class="text-sm">Multimediedesigner</p>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resident 4 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-400 overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center text-lg font-medium">
                                    SN
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Sofia Nielsen</h3>
                                    <p class="text-sm text-gray-500">Værelse D34 • 3. etage</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm">sofia.nielsen@example.com</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Telefon</p>
                                    <p class="text-sm">+45 45 67 89 01</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Værelse</p>
                                    <p class="text-sm">D34 - 3. etage</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Uddannelse</p>
                                    <p class="text-sm">N/A</p>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resident 5 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-100 overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-secondary text-white flex items-center justify-center text-lg font-medium">
                                    MA
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Mathias Andersen</h3>
                                    <p class="text-sm text-gray-500">Værelse B08 • Stuen</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm">mathias.a@example.com</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Telefon</p>
                                    <p class="text-sm">+45 56 78 90 12</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Værelse</p>
                                    <p class="text-sm">B08 - Stuen</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Uddannelse</p>
                                    <p class="text-sm">Serviceøkonom</p>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Resident 6 -->
                    <div class="bg-white rounded-xl shadow animate-fade-in delay-200 overflow-hidden">
                        <div class="flex justify-between items-center p-4 border-b border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-accent text-white flex items-center justify-center text-lg font-medium">
                                    EH
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800">Emma Hansen</h3>
                                    <p class="text-sm text-gray-500">Værelse A12 • 1. etage</p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-gray-500">Email</p>
                                    <p class="text-sm">emma.h@example.com</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Telefon</p>
                                    <p class="text-sm">+45 67 89 01 23</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Værelse</p>
                                    <p class="text-sm">A12 - 1. etage</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Uddannelse</p>
                                    <p class="text-sm">Markedsføringsøkonom</p>
                                </div>
                            </div>
                            <div class="flex justify-between mt-2">
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-phone"></i>
                                    </button>
                                </div>
                                <div class="flex gap-1">
                                    <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Residents List View (hidden by default) -->
                <div id="residents-list" class="hidden bg-white rounded-xl shadow overflow-hidden mb-6 animate-fade-in">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Navn</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Værelse</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Telefon</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Uddannelse</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">Handlinger</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                            MH
                                        </div>
                                        <span class="font-medium">Mikkel Hansen</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">B12 - Stuen</td>
                                <td class="px-4 py-3 text-sm">mikkel.hansen@example.com</td>
                                <td class="px-4 py-3 text-sm">+45 12 34 56 78</td>
                                <td class="px-4 py-3 text-sm">Datamatiker</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center text-sm font-medium">
                                            LJ
                                        </div>
                                        <span class="font-medium">Laura Jensen</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">A05 - 1. etage</td>
                                <td class="px-4 py-3 text-sm">laura.jensen@example.com</td>
                                <td class="px-4 py-3 text-sm">+45 23 45 67 89</td>
                                <td class="px-4 py-3 text-sm">Sygeplejerske</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center text-sm font-medium">
                                            AP
                                        </div>
                                        <span class="font-medium">Anders Petersen</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">C21 - 2. etage</td>
                                <td class="px-4 py-3 text-sm">anders.p@example.com</td>
                                <td class="px-4 py-3 text-sm">+45 34 56 78 90</td>
                                <td class="px-4 py-3 text-sm">Multimediedesigner</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center text-sm font-medium">
                                            SN
                                        </div>
                                        <span class="font-medium">Sofia Nielsen</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">D34 - 3. etage</td>
                                <td class="px-4 py-3 text-sm">sofia.nielsen@example.com</td>
                                <td class="px-4 py-3 text-sm">+45 45 67 89 01</td>
                                <td class="px-4 py-3 text-sm">N/A</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-secondary text-white flex items-center justify-center text-sm font-medium">
                                            MA
                                        </div>
                                        <span class="font-medium">Mathias Andersen</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">B08 - Stuen</td>
                                <td class="px-4 py-3 text-sm">mathias.a@example.com</td>
                                <td class="px-4 py-3 text-sm">+45 56 78 90 12</td>
                                <td class="px-4 py-3 text-sm">Serviceøkonom</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-accent text-white flex items-center justify-center text-sm font-medium">
                                            EH
                                        </div>
                                        <span class="font-medium">Emma Hansen</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">A12 - 1. etage</td>
                                <td class="px-4 py-3 text-sm">emma.h@example.com</td>
                                <td class="px-4 py-3 text-sm">+45 67 89 01 23</td>
                                <td class="px-4 py-3 text-sm">Markedsføringsøkonom</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-2">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-envelope"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-phone"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-danger transition-colors p-1">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex justify-between items-center">
                    <div class="text-gray-500 text-sm">
                        Viser 1-6 af 112 beboere
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
        // View toggle functionality
        const viewGridBtn = document.getElementById('view-grid');
        const viewListBtn = document.getElementById('view-list');
        const residentsGrid = document.getElementById('residents-grid');
        const residentsList = document.getElementById('residents-list');

        viewGridBtn.addEventListener('click', () => {
            residentsGrid.classList.remove('hidden');
            residentsList.classList.add('hidden');

            viewGridBtn.classList.add('bg-primary', 'text-white');
            viewGridBtn.classList.remove('hover:bg-gray-100');

            viewListBtn.classList.remove('bg-primary', 'text-white');
            viewListBtn.classList.add('hover:bg-gray-100');
        });

        viewListBtn.addEventListener('click', () => {
            residentsGrid.classList.add('hidden');
            residentsList.classList.remove('hidden');

            viewListBtn.classList.add('bg-primary', 'text-white');
            viewListBtn.classList.remove('hover:bg-gray-100');

            viewGridBtn.classList.remove('bg-primary', 'text-white');
            viewGridBtn.classList.add('hover:bg-gray-100');
        });
    </script>
</body>

</html>