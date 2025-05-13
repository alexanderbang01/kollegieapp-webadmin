<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
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
include '../database/db_conn.php';

// Hent den valgte måned og år
$current_month_index = isset($_GET['month']) ? (int)$_GET['month'] - 1 : date('n') - 1; // 0-baseret indeks
$current_year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Sikre gyldigt måneds-indeks
if ($current_month_index < 0 || $current_month_index > 11) {
    $current_month_index = date('n') - 1;
}

// Danske månedsnavne
$months = ['Januar', 'Februar', 'Marts', 'April', 'Maj', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'December'];
$danish_days = [
    'Monday' => 'Mandag',
    'Tuesday' => 'Tirsdag',
    'Wednesday' => 'Onsdag',
    'Thursday' => 'Torsdag',
    'Friday' => 'Fredag',
    'Saturday' => 'Lørdag',
    'Sunday' => 'Søndag'
];

// Formater måned til SQL (1-12)
$sql_month = $current_month_index + 1;
$sql_month_str = str_pad($sql_month, 2, '0', STR_PAD_LEFT);

// Hent begivenheder fra databasen
$events = [];
if (isset($conn)) {
    // Hent begivenheder for den valgte måned
    $start_date = $current_year . '-' . $sql_month_str . '-01';
    $end_date = $current_year . '-' . $sql_month_str . '-31'; // Simpel tilgang, ikke perfekt for alle måneder
    
    $query = "SELECT e.*, u.name AS organizer_name, u.email AS organizer_email,
                (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) AS participant_count
              FROM events e
              LEFT JOIN users u ON e.created_by = u.id
              WHERE e.date BETWEEN ? AND ?
              ORDER BY e.date ASC, e.time ASC";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    
    // Hent deltagerliste for hver begivenhed
    foreach ($events as &$event) {
        $event['attendees'] = [];
        
        $query = "SELECT r.* FROM event_participants ep
                  JOIN residents r ON ep.resident_id = r.id
                  WHERE ep.event_id = ?";
                  
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $event['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $event['attendees'][] = [
                'name' => $row['first_name'] . ' ' . $row['last_name'],
                'room' => $row['room_number']
            ];
        }
    }
}
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
                    <a href="create-event.php" class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Opret begivenhed</span>
                    </a>
                </div>

                <!-- Status message -->
                <?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
                    <?php
                    $message = '';
                    $message_type = '';
                    
                    if (isset($_SESSION['success_message'])) {
                        $message = $_SESSION['success_message'];
                        $message_type = 'success';
                        unset($_SESSION['success_message']);
                    } elseif (isset($_SESSION['error_message'])) {
                        $message = $_SESSION['error_message'];
                        $message_type = 'error';
                        unset($_SESSION['error_message']);
                    }
                    ?>
                    <div id="status-message" class="<?php echo $message_type == 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700'; ?> px-4 py-3 rounded shadow mb-6" role="alert">
                        <div class="flex">
                            <div class="py-1 mr-2">
                                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                            </div>
                            <div>
                                <p class="font-bold"><?php echo $message_type == 'success' ? 'Succes!' : 'Fejl!'; ?></p>
                                <p><?php echo $message; ?></p>
                            </div>
                        </div>
                    </div>
                    <script>
                        // Skjul besked efter 3 sekunder
                        setTimeout(function() {
                            const statusMessage = document.getElementById('status-message');
                            if (statusMessage) {
                                statusMessage.style.opacity = '0';
                                statusMessage.style.transition = 'opacity 0.5s';
                                setTimeout(function() {
                                    statusMessage.style.display = 'none';
                                }, 500);
                            }
                        }, 3000);
                    </script>
                <?php endif; ?>

                <!-- Month navigation and search tools -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex flex-col sm:flex-row items-center gap-4">
                        <!-- Left: Month navigation -->
                        <div class="flex items-center gap-4 sm:w-1/3">
                            <button id="prev-month" class="text-gray-600 hover:text-primary transition-colors text-xl">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h2 class="font-bold text-lg"><?php echo $months[$current_month_index] . ' ' . $current_year; ?></h2>
                            <button id="next-month" class="text-gray-600 hover:text-primary transition-colors text-xl">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Center: Search field -->
                        <div class="relative sm:w-1/3 w-full">
                            <input type="text" placeholder="Søg i begivenheder..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>

                        <!-- Right: Layout controls -->
                        <div class="sm:w-1/3 flex justify-end">
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
                    </div>
                </div>

                <!-- Events Grid -->
                <div id="events-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <?php if (empty($events)): ?>
                        <div class="col-span-3 bg-white rounded-xl shadow p-6 text-center">
                            <p class="text-gray-500">Ingen begivenheder fundet for <?php echo mb_strtolower($months[$current_month_index]); ?>.</p>
                            <a href="create-event.php" class="mt-2 inline-block text-primary hover:underline">Opret en ny begivenhed</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($events as $index => $event): ?>
                            <!-- Event -->
                            <div class="bg-white rounded-xl shadow animate-fade-in delay-<?php echo ($index % 4) * 100; ?>">
                                <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-primary/10 text-primary p-2 rounded-lg">
                                            <i class="fas fa-calendar-day text-lg"></i>
                                        </div>
                                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($event['title']); ?></h3>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="text-gray-400 hover:text-primary transition-colors">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button class="text-gray-400 hover:text-danger transition-colors" onclick="confirmDelete(<?php echo $event['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex flex-col mb-3">
                                        <div class="flex items-center gap-2 text-primary font-medium">
                                            <i class="fas fa-calendar-alt"></i>
                                            <span><?php 
                                                $event_date = new DateTime($event['date']);
                                                $weekday = $danish_days[$event_date->format('l')];
                                                echo $weekday . ', ' . $event_date->format('j.') . ' ' . mb_strtolower($months[$event_date->format('n')-1]);
                                            ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-gray-600 mt-1">
                                            <i class="far fa-clock"></i>
                                            <span><?php echo substr($event['time'], 0, 5); ?></span>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="flex items-center gap-2 text-gray-600 mb-1">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                                        </div>
                                        <div class="flex items-center gap-2 text-gray-600">
                                            <i class="fas fa-user-friends"></i>
                                            <span><?php echo $event['participant_count']; ?> tilmeldte<?php echo $event['max_participants'] ? ' / Max ' . $event['max_participants'] : ''; ?></span>
                                        </div>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($event['description'], 0, 120)) . (strlen($event['description']) > 120 ? '...' : ''); ?></p>
                                    <div class="flex justify-end">
                                        <button class="text-primary text-sm hover:underline hover:text-primary/70 transition-colors" onclick="showEventDetails(<?php echo $event['id']; ?>)">Se detaljer</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Event Details Modal -->
                <div id="event-details-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
                    <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto scale-95 transition-transform duration-300">
                        <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 text-primary p-2 rounded-lg">
                                    <i class="fas fa-calendar-day text-lg"></i>
                                </div>
                                <h3 class="font-bold text-gray-800 text-xl" id="modal-title">Begivenhedsdetaljer</h3>
                            </div>
                            <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>
                        <div class="p-6" id="modal-content">
                            <!-- Event details will be loaded here -->
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div id="delete-confirm-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
                    <div class="bg-white rounded-xl shadow-lg w-full max-w-md scale-95 transition-transform duration-300 p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Bekræft sletning</h3>
                        <p class="text-gray-600 mb-6">Er du sikker på, at du vil slette denne begivenhed? Denne handling kan ikke fortrydes.</p>
                        <div class="flex justify-end gap-3">
                            <button id="cancel-delete" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                                Annuller
                            </button>
                            <form id="delete-form" action="delete-event.php" method="POST">
                                <input type="hidden" id="delete-event-id" name="event_id" value="">
                                <button type="submit" class="bg-danger hover:bg-danger/90 text-white px-4 py-2 rounded-lg transition-colors">
                                    Slet begivenhed
                                </button>
                            </form>
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
        let currentMonthIndex = <?php echo $current_month_index; ?>; // 0-baseret indeks
        let currentYear = <?php echo $current_year; ?>;
        const monthTitle = document.querySelector('h2');

        prevMonthBtn.addEventListener('click', () => {
            currentMonthIndex--;
            if (currentMonthIndex < 0) {
                currentMonthIndex = 11;
                currentYear--;
            }
            updateMonthDisplay();
            window.location.href = `?month=${currentMonthIndex+1}&year=${currentYear}`;
        });

        nextMonthBtn.addEventListener('click', () => {
            currentMonthIndex++;
            if (currentMonthIndex > 11) {
                currentMonthIndex = 0;
                currentYear++;
            }
            updateMonthDisplay();
            window.location.href = `?month=${currentMonthIndex+1}&year=${currentYear}`;
        });

        function updateMonthDisplay() {
            monthTitle.textContent = `${months[currentMonthIndex]} ${currentYear}`;
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

        // Event details modal
        const modal = document.getElementById('event-details-modal');
        const modalContainer = modal.querySelector('.bg-white');
        const closeModalBtn = document.getElementById('close-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalContent = document.getElementById('modal-content');

        // Konverter begivenheder til JavaScript objekt
        const eventData = <?php echo json_encode($events); ?>;

        function showEventDetails(eventId) {
            // Find begivenheden ud fra ID
            const event = eventData.find(event => event.id == eventId);
            if (!event) return;

            // Konverter dato til dansk format
            const eventDate = new Date(event.date);
            const weekday = ['Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag'][eventDate.getDay()];
            const day = eventDate.getDate();
            const month = months[eventDate.getMonth()].toLowerCase();
            const formattedDate = `${weekday}, ${day}. ${month}`;

            // Opdater modal titel
            modalTitle.textContent = event.title;

            // Opdater modal indhold
            modalContent.innerHTML = `
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-bold text-gray-700 mb-3">Begivenhedsdetaljer</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-calendar-alt text-primary w-5"></i>
                                    <span class="font-medium">${formattedDate}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="far fa-clock text-primary w-5"></i>
                                    <span>${event.time.substr(0, 5)}</span>
                                </div>
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-map-marker-alt text-primary w-5 mt-1"></i>
                                    <span>${event.location}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user-friends text-primary w-5"></i>
                                    <span>${event.participant_count} tilmeldte${event.max_participants ? ' / Max ' + event.max_participants : ''}</span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-700 mb-3">Kontaktinformation</h4>
                            <div class="space-y-3">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user text-primary w-5"></i>
                                    <span>Arrangør: ${event.organizer_name || 'Ikke angivet'}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-envelope text-primary w-5"></i>
                                    <span>Email: ${event.organizer_email || 'Ikke angivet'}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-700 mb-2">Beskrivelse</h4>
                        <p class="text-gray-600">${event.description}</p>
                    </div>
                    
                    <div>
                        <h4 class="font-bold text-gray-700 mb-3">Deltagere (${event.attendees.length})</h4>
                        <div class="bg-gray-50 rounded-lg p-4 max-h-64 overflow-y-auto">
                            ${event.attendees.length > 0 ? `
                                <table class="w-full">
                                    <thead class="border-b border-gray-200">
                                        <tr>
                                            <th class="text-left font-medium text-gray-500 pb-2">Navn</th>
                                            <th class="text-left font-medium text-gray-500 pb-2">Værelse</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${event.attendees.map(attendee => `
                                            <tr class="border-b border-gray-100">
                                                <td class="py-2">${attendee.name}</td>
                                                <td class="py-2">${attendee.room}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            ` : `
                                <p class="text-gray-500 text-center py-2">Ingen tilmeldte deltagere</p>
                            `}
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors" onclick="closeModal()">
                            Luk
                        </button>
                    </div>
                </div>
            `;

            // Vis modal med animation
            modal.classList.remove('hidden');
            // Kort timeout for at sikre at transitioner virker korrekt efter at elementet er vist
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modalContainer.classList.remove('scale-95');
                modalContainer.classList.add('scale-100');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            // Skjul modal med animation
            modal.classList.remove('opacity-100');
            modalContainer.classList.remove('scale-100');
            modalContainer.classList.add('scale-95');

            // Vent på at animationen er færdig før vi fjerner modalen helt
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }

        closeModalBtn.addEventListener('click', closeModal);

        // Luk modal når der klikkes udenfor
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Luk modal med Escape-tasten
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });

        // Delete confirmation functionality
        const deleteModal = document.getElementById('delete-confirm-modal');
        const deleteModalContainer = deleteModal.querySelector('.bg-white');
        const cancelDeleteBtn = document.getElementById('cancel-delete');
        const deleteEventIdField = document.getElementById('delete-event-id');

        function confirmDelete(eventId) {
            deleteEventIdField.value = eventId;
            
            // Vis modal med animation
            deleteModal.classList.remove('hidden');
            setTimeout(() => {
                deleteModal.classList.add('opacity-100');
                deleteModalContainer.classList.remove('scale-95');
                deleteModalContainer.classList.add('scale-100');
            }, 10);
            document.body.classList.add('overflow-hidden');
        }

        function closeDeleteModal() {
            // Skjul modal med animation
            deleteModal.classList.remove('opacity-100');
            deleteModalContainer.classList.remove('scale-100');
            deleteModalContainer.classList.add('scale-95');

            // Vent på at animationen er færdig før vi fjerner modalen helt
            setTimeout(() => {
                deleteModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }, 300);
        }

        cancelDeleteBtn.addEventListener('click', closeDeleteModal);

        // Luk delete modal når der klikkes udenfor
        deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
        });

        // Luk delete modal med Escape-tasten
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !deleteModal.classList.contains('hidden')) {
                closeDeleteModal();
            }
        });
    </script>
</body>

</html>