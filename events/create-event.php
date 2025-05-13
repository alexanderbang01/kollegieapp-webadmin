<?php
$page = "events";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}
include '../components/header.php';
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Create Event content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Opret begivenhed</h1>
                        <p class="text-sm sm:text-base text-gray-600">Tilføj en ny begivenhed til kollegiets kalender</p>
                    </div>
                    <a href="./" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        <span>Tilbage til oversigt</span>
                    </a>
                </div>

                <!-- Create Event Form -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in">
                    <form id="create-event-form" action="save-event.php" method="POST" class="space-y-6">
                        <!-- Skjult felt til user ID -->
                        <input type="hidden" name="created_by" value="<?php echo $_SESSION['user_id']; ?>">

                        <!-- Basic Information -->
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Grundlæggende oplysninger</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel <span class="text-danger">*</span></label>
                                    <input type="text" id="title" name="title" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                                <div>
                                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Sted <span class="text-danger">*</span></label>
                                    <input type="text" id="location" name="location" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                                <div>
                                    <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Dato <span class="text-danger">*</span></label>
                                    <input type="date" id="date" name="date" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                                <div>
                                    <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Tidspunkt <span class="text-danger">*</span></label>
                                    <input type="time" id="time" name="time" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                            </div>
                        </div>

                        <!-- Description and Participants -->
                        <div>
                            <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Beskrivelse og deltagere</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Beskrivelse <span class="text-danger">*</span></label>
                                    <textarea id="description" name="description" rows="4" required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"></textarea>
                                </div>
                                <div>
                                    <label for="max_participants" class="block text-sm font-medium text-gray-700 mb-1">Maks. antal deltagere</label>
                                    <div class="flex items-center">
                                        <input type="number" id="max_participants" name="max_participants" min="0" step="1"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        <span class="text-sm text-gray-500 ml-2">Lad feltet stå tomt for ubegrænset</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmation -->
                        <div class="flex justify-end gap-3 pt-2">
                            <a href="./" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                                Annuller
                            </a>
                            <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                                <i class="fas fa-save"></i>
                                <span>Gem begivenhed</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Form validation and enhancement
        const form = document.getElementById('create-event-form');
        const titleInput = document.getElementById('title');
        const dateInput = document.getElementById('date');

        // Set min date to today
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        dateInput.setAttribute('min', `${year}-${month}-${day}`);

        // Set default date to today
        dateInput.value = `${year}-${month}-${day}`;

        // Set default time (18:00)
        document.getElementById('time').value = '18:00';

        // Form submission handling
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Simple validation
            let isValid = true;

            if (titleInput.value.trim() === '') {
                isValid = false;
                highlightInvalidField(titleInput);
            } else {
                resetField(titleInput);
            }

            if (dateInput.value === '') {
                isValid = false;
                highlightInvalidField(dateInput);
            } else {
                resetField(dateInput);
            }

            if (isValid) {
                // Submit formular til serveren
                this.submit();
            }
        });

        function highlightInvalidField(field) {
            field.classList.add('border-danger');
            field.classList.add('bg-danger/5');

            // Tilføj fejlmeddelelse hvis den ikke allerede findes
            const parent = field.parentElement;
            if (!parent.querySelector('.error-message')) {
                const errorMessage = document.createElement('p');
                errorMessage.className = 'text-danger text-xs mt-1 error-message';
                errorMessage.textContent = 'Dette felt er påkrævet';
                parent.appendChild(errorMessage);
            }
        }

        function resetField(field) {
            field.classList.remove('border-danger');
            field.classList.remove('bg-danger/5');

            // Fjern fejlmeddelelse hvis den findes
            const parent = field.parentElement;
            const errorMessage = parent.querySelector('.error-message');
            if (errorMessage) {
                parent.removeChild(errorMessage);
            }
        }
    </script>
</body>

</html>