<?php 
$page = "settings";
include '../components/header.php';
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Settings content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Indstillinger</h1>
                    <p class="text-sm sm:text-base text-gray-600">Administrer systemindstillinger og konfiguration</p>
                </div>

                <!-- Settings navigation -->
                <div class="bg-white rounded-xl shadow mb-6">
                    <div class="border-b border-gray-200">
                        <nav class="flex overflow-x-auto">
                            <button class="text-primary border-b-2 border-primary font-medium px-4 py-3 whitespace-nowrap">
                                Generelt
                            </button>
                            <button class="text-gray-500 hover:text-gray-700 px-4 py-3 whitespace-nowrap">
                                Brugere & Tilladelser
                            </button>
                            <button class="text-gray-500 hover:text-gray-700 px-4 py-3 whitespace-nowrap">
                                Notifikationer
                            </button>
                            <button class="text-gray-500 hover:text-gray-700 px-4 py-3 whitespace-nowrap">
                                Integration
                            </button>
                            <button class="text-gray-500 hover:text-gray-700 px-4 py-3 whitespace-nowrap">
                                Backup & Gendan
                            </button>
                            <button class="text-gray-500 hover:text-gray-700 px-4 py-3 whitespace-nowrap">
                                System
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- General Settings Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Left sidebar with categories -->
                    <div class="bg-white rounded-xl shadow p-4 h-min">
                        <h2 class="font-bold text-gray-800 mb-3">Kategorier</h2>
                        <nav class="space-y-1">
                            <a href="#app-settings" class="flex items-center gap-2 px-3 py-2 rounded-lg bg-primary/10 text-primary font-medium">
                                <i class="fas fa-cog"></i>
                                <span>App indstillinger</span>
                            </a>
                            <a href="#college-info" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-building"></i>
                                <span>Kollegieoplysninger</span>
                            </a>
                            <a href="#appearance" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-palette"></i>
                                <span>Udseende</span>
                            </a>
                            <a href="#modules" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-puzzle-piece"></i>
                                <span>Moduler</span>
                            </a>
                            <a href="#email-templates" class="flex items-center gap-2 px-3 py-2 rounded-lg text-gray-700 hover:bg-gray-100 transition-colors">
                                <i class="fas fa-envelope-open-text"></i>
                                <span>Email skabeloner</span>
                            </a>
                        </nav>
                    </div>

                    <!-- Main settings area -->
                    <div class="md:col-span-2 space-y-6">
                        <!-- App Settings -->
                        <section id="app-settings" class="bg-white rounded-xl shadow overflow-hidden">
                            <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center">
                                <h2 class="font-bold text-lg text-gray-800">App indstillinger</h2>
                                <button class="text-primary hover:text-primary/80 transition-colors">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                            <div class="p-4 sm:p-6 space-y-5">
                                <!-- App Name -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-1">
                                        <label for="app-name" class="font-medium">App navn</label>
                                        <p class="text-sm text-gray-500">Navnet på app'en</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <input type="text" id="app-name" value="Mercantec Kollegium" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>
                                </div>

                                <!-- Language -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-1">
                                        <label for="language" class="font-medium">Sprog</label>
                                        <p class="text-sm text-gray-500">App'ens standardsprog</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <select id="language" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                            <option value="da">Dansk</option>
                                            <option value="en">Engelsk</option>
                                            <option value="de">Tysk</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Timezone -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-1">
                                        <label for="timezone" class="font-medium">Tidszone</label>
                                        <p class="text-sm text-gray-500">App'ens tidszone</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <select id="timezone" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                            <option value="Europe/Copenhagen">Europa/København (UTC+2)</option>
                                            <option value="Europe/London">Europa/London (UTC+1)</option>
                                            <option value="Europe/Berlin">Europa/Berlin (UTC+2)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Session Timeout -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-1">
                                        <label for="session-timeout" class="font-medium">Session timeout</label>
                                        <p class="text-sm text-gray-500">Tid før automatisk log ud</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <select id="session-timeout" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                            <option value="30">30 minutter</option>
                                            <option value="60">1 time</option>
                                            <option value="120">2 timer</option>
                                            <option value="240">4 timer</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Maintenance Mode -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-1">
                                        <label class="font-medium">Vedligeholdelsestilstand</label>
                                        <p class="text-sm text-gray-500">Aktivér ved opdateringer</p>
                                    </div>
                                    <div class="md:col-span-2 flex items-center gap-4">
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer">
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                        <span class="text-gray-500 text-sm">Deaktiveret</span>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                                <button class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                    Gem ændringer
                                </button>
                            </div>
                        </section>

                        <!-- Appearance Settings -->
                        <section id="appearance" class="bg-white rounded-xl shadow overflow-hidden">
                            <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center">
                                <h2 class="font-bold text-lg text-gray-800">Udseende</h2>
                                <button class="text-primary hover:text-primary/80 transition-colors">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                            <div class="p-4 sm:p-6 space-y-5">
                                <!-- Logo Upload -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                    <div class="md:col-span-1">
                                        <label class="font-medium">Logo</label>
                                        <p class="text-sm text-gray-500">Upload app logo</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="flex items-center gap-4">
                                            <div class="w-16 h-16 bg-gray-100 rounded flex items-center justify-center">
                                                <img src="<?=$base?>assets/logo.png" alt="App logo" class="max-w-full max-h-full">
                                            </div>
                                            <div>
                                                <label for="logo-upload" class="bg-primary/10 text-primary hover:bg-primary/20 font-medium px-3 py-1.5 rounded cursor-pointer inline-block">
                                                    <i class="fas fa-upload mr-1"></i> Upload nyt logo
                                                </label>
                                                <input id="logo-upload" type="file" class="hidden">
                                                <p class="text-xs text-gray-500 mt-1">Anbefalet størrelse: 512x512px</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Color Theme -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                                    <div class="md:col-span-1">
                                        <label class="font-medium">Farvetema</label>
                                        <p class="text-sm text-gray-500">Vælg app'ens farver</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <div class="space-y-3">
                                            <div class="flex items-center gap-3">
                                                <label for="primary-color" class="text-sm w-24">Primær:</label>
                                                <div class="relative">
                                                    <input type="text" id="primary-color" value="#007AFF" class="w-32 border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                    <input type="color" id="primary-color-picker" value="#007AFF" class="absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 p-0 border-0 rounded cursor-pointer">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <label for="secondary-color" class="text-sm w-24">Sekundær:</label>
                                                <div class="relative">
                                                    <input type="text" id="secondary-color" value="#34C759" class="w-32 border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                    <input type="color" id="secondary-color-picker" value="#34C759" class="absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 p-0 border-0 rounded cursor-pointer">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <label for="accent-color" class="text-sm w-24">Accent:</label>
                                                <div class="relative">
                                                    <input type="text" id="accent-color" value="#FF9500" class="w-32 border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                    <input type="color" id="accent-color-picker" value="#FF9500" class="absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 p-0 border-0 rounded cursor-pointer">
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <label for="danger-color" class="text-sm w-24">Danger:</label>
                                                <div class="relative">
                                                    <input type="text" id="danger-color" value="#FF3B30" class="w-32 border border-gray-300 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                    <input type="color" id="danger-color-picker" value="#FF3B30" class="absolute right-2 top-1/2 transform -translate-y-1/2 w-6 h-6 p-0 border-0 rounded cursor-pointer">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Font Selection -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center">
                                    <div class="md:col-span-1">
                                        <label for="font-family" class="font-medium">Skrifttype</label>
                                        <p class="text-sm text-gray-500">Vælg app'ens skrifttype</p>
                                    </div>
                                    <div class="md:col-span-2">
                                        <select id="font-family" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                            <option value="poppins">Poppins</option>
                                            <option value="roboto">Roboto</option>
                                            <option value="open-sans">Open Sans</option>
                                            <option value="montserrat">Montserrat</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                                <button class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                    Gem ændringer
                                </button>
                            </div>
                        </section>

                        <!-- Modules Settings -->
                        <section id="modules" class="bg-white rounded-xl shadow overflow-hidden">
                            <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center">
                                <h2 class="font-bold text-lg text-gray-800">Moduler</h2>
                                <button class="text-primary hover:text-primary/80 transition-colors">
                                    <i class="fas fa-info-circle"></i>
                                </button>
                            </div>
                            <div class="p-4 sm:p-6">
                                <p class="text-sm text-gray-500 mb-4">Aktivér eller deaktivér funktionsmoduler i app'en</p>
                                
                                <div class="space-y-3">
                                    <!-- Madplan Module -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-full p-2 bg-primary/10 text-primary">
                                                <i class="fas fa-utensils"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-medium">Madplan</h3>
                                                <p class="text-xs text-gray-500">Ukentlig madplanlægning</p>
                                            </div>
                                        </div>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer" checked>
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </div>

                                    <!-- Begivenheder Module -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-full p-2 bg-primary/10 text-primary">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-medium">Begivenheder</h3>
                                                <p class="text-xs text-gray-500">Planlægning af arrangementer</p>
                                            </div>
                                        </div>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer" checked>
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </div>

                                    <!-- Beskeder Module -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-full p-2 bg-primary/10 text-primary">
                                                <i class="fas fa-comments"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-medium">Beskeder</h3>
                                                <p class="text-xs text-gray-500">Intern kommunikation</p>
                                            </div>
                                        </div>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer" checked>
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </div>

                                    <!-- Vaskeri Module -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-full p-2 bg-primary/10 text-primary">
                                                <i class="fas fa-tshirt"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-medium">Vaskeri</h3>
                                                <p class="text-xs text-gray-500">Vasketidsbooking</p>
                                            </div>
                                        </div>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer">
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </div>

                                    <!-- Vedligehold Module -->
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <div class="rounded-full p-2 bg-primary/10 text-primary">
                                                <i class="fas fa-tools"></i>
                                            </div>
                                            <div>
                                                <h3 class="font-medium">Vedligehold</h3>
                                                <p class="text-xs text-gray-500">Reparationsanmodninger</p>
                                            </div>
                                        </div>
                                        <label class="inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer" checked>
                                            <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary/50 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-4 flex justify-end">
                                <button class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                    Gem ændringer
                                </button>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Sync color inputs with their pickers
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = [
                { text: 'primary-color', picker: 'primary-color-picker' },
                { text: 'secondary-color', picker: 'secondary-color-picker' },
                { text: 'accent-color', picker: 'accent-color-picker' },
                { text: 'danger-color', picker: 'danger-color-picker' }
            ];
            
            colorInputs.forEach(pair => {
                const textInput = document.getElementById(pair.text);
                const colorPicker = document.getElementById(pair.picker);
                
                colorPicker.addEventListener('input', () => {
                    textInput.value = colorPicker.value;
                });
                
                textInput.addEventListener('input', () => {
                    if (/^#[0-9A-F]{6}$/i.test(textInput.value)) {
                        colorPicker.value = textInput.value;
                    }
                });
            });
        });
    </script>
</body>
</html>