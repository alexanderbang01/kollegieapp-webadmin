<?php
$page = "messages";

include '../components/header.php';
include '../database/db_conn.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/");
    exit();
}

// Funktion til at generere bruger-avatars
function generateAvatar($name, $size = 12)
{
    $initials = '';
    $nameParts = explode(" ", $name);

    if (count($nameParts) >= 2) {
        $initials = mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8');
    } else {
        $initials = mb_substr($name, 0, 2, 'UTF-8');
    }

    return '<div class="w-' . $size . ' h-' . $size . ' rounded-full flex items-center justify-center text-white font-medium">' . strtoupper($initials) . '</div>';
}

// Hent personale
$staff = [];
$stmt = $conn->prepare("
    SELECT id, name, role, profile_image
    FROM users 
    WHERE role IN ('Administrator', 'Personale') 
    AND id != ?
    ORDER BY role DESC, name ASC
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $staff[] = $row;
}

// Hent beboere
$residents = [];
$stmt = $conn->prepare("
    SELECT id, CONCAT(first_name, ' ', last_name) AS name, room_number, profile_image
    FROM residents
    ORDER BY first_name, last_name
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $residents[] = $row;
}

// Hent antal ulæste beskeder
$unreadMessages = [];
$stmt = $conn->prepare("
    SELECT 
        CONCAT(sender_type, '_', sender_id) as sender_key,
        COUNT(*) as count
    FROM messages
    WHERE recipient_id = ? AND recipient_type = 'staff' AND read_at IS NULL
    GROUP BY sender_type, sender_id
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $unreadMessages[$row['sender_key']] = $row['count'];
}
?>

<body class="font-poppins bg-gray-100 min-h-screen">
    <div class="flex h-full">
        <?php include '../components/sidebar.php'; ?>

        <!-- Hovedindhold -->
        <div class="flex-1 flex flex-col h-screen overflow-hidden page-content">
            <!-- Mobil header bar -->
            <div class="md:hidden flex items-center justify-between p-4 bg-white border-b shadow-sm">
                <div class="flex items-center gap-3">
                    <button id="mobile-menu-btn" class="text-gray-500 hover:text-gray-700 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800" id="mobile-page-title">Beskeder</h1>
                </div>
                <button id="toggle-view-btn" class="text-gray-500 hover:text-gray-700 transition-colors hidden">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </button>
            </div>

            <!-- Beskedsystem container -->
            <div class="flex-grow flex h-full overflow-hidden">
                <!-- Kontaktliste -->
                <div id="contacts-sidebar" class="w-full md:w-1/3 lg:w-1/4 border-r border-gray-200 flex flex-col h-full bg-white contacts-sidebar">
                    <!-- Søgefelt -->
                    <div class="p-3 border-b border-gray-200">
                        <div class="relative">
                            <input
                                type="text"
                                id="message-search"
                                placeholder="Søg efter kontakter..."
                                class="w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <div class="absolute left-3 top-2.5 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Kontaktliste -->
                    <div class="overflow-y-auto flex-grow">
                        <!-- Personale sektion -->
                        <div class="bg-gray-50 px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200" id="staff-header">
                            Personale
                        </div>
                        <div id="staff-list">
                            <?php if (empty($staff)): ?>
                                <div class="p-4 text-center text-gray-500">Ingen personale tilgængelig</div>
                            <?php else: ?>
                                <?php foreach ($staff as $person): ?>
                                    <div class="contact-item p-3 flex items-center gap-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors" data-id="<?= $person['id'] ?>" data-type="staff">
                                        <div class="relative flex-shrink-0">
                                            <?php if (!empty($person['profile_image'])): ?>
                                                <div class="w-12 h-12 rounded-full bg-cover bg-center" style="background-image: url('<?= $person['profile_image'] ?>')"></div>
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-full bg-blue-600 flex items-center justify-center">
                                                    <?= generateAvatar($person['name']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                            $key = "staff_" . $person['id'];
                                            if (isset($unreadMessages[$key]) && $unreadMessages[$key] > 0):
                                            ?>
                                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                                    <?= $unreadMessages[$key] > 9 ? '9+' : $unreadMessages[$key] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-grow min-w-0">
                                            <p class="font-medium text-gray-900 truncate contact-name"><?= htmlspecialchars($person['name']) ?></p>
                                            <p class="text-xs text-gray-500">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?= $person['role'] ?>
                                                </span>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Beboere sektion -->
                        <div class="bg-gray-50 px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider border-b border-gray-200" id="resident-header">
                            Beboere
                        </div>
                        <div id="resident-list">
                            <?php if (empty($residents)): ?>
                                <div class="p-4 text-center text-gray-500">Ingen beboere fundet</div>
                            <?php else: ?>
                                <?php foreach ($residents as $resident): ?>
                                    <div class="contact-item p-3 flex items-center gap-3 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors" data-id="<?= $resident['id'] ?>" data-type="resident">
                                        <div class="relative flex-shrink-0">
                                            <?php if (!empty($resident['profile_image'])): ?>
                                                <div class="w-12 h-12 rounded-full bg-cover bg-center" style="background-image: url('<?= $resident['profile_image'] ?>')"></div>
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-full bg-gray-500 flex items-center justify-center">
                                                    <?= generateAvatar($resident['name']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php
                                            $key = "resident_" . $resident['id'];
                                            if (isset($unreadMessages[$key]) && $unreadMessages[$key] > 0):
                                            ?>
                                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
                                                    <?= $unreadMessages[$key] > 9 ? '9+' : $unreadMessages[$key] ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="flex-grow min-w-0">
                                            <p class="font-medium text-gray-900 truncate contact-name"><?= htmlspecialchars($resident['name']) ?></p>
                                            <p class="text-xs text-gray-500">
                                                Værelse <?= $resident['room_number'] ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Samtalevisning -->
                <div id="conversation-area" class="hidden md:flex flex-col w-full md:w-2/3 lg:w-3/4 h-full conversation-area">
                    <!-- Startside for samtaler -->
                    <div class="flex items-center justify-center h-full bg-gray-50">
                        <div class="text-center max-w-md p-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h2 class="text-xl font-semibold text-gray-700 mb-2">Dine beskeder</h2>
                            <p class="text-gray-500">Vælg en person fra listen for at starte en samtale.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Action Modal -->
    <div id="message-action-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg w-full max-w-sm mx-4 shadow-lg">
            <div class="p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Besked handlinger</h3>
            </div>
            <div class="p-5 space-y-4">
                <button id="edit-message-btn" class="w-full py-2 px-4 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Rediger besked
                </button>
                <button id="delete-message-btn" class="w-full py-2 px-4 bg-red-50 hover:bg-red-100 text-red-700 rounded flex items-center gap-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Slet besked
                </button>
            </div>
            <div class="p-4 border-t border-gray-200 flex justify-end">
                <button id="close-modal-btn" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors">
                    Annuller
                </button>
            </div>
        </div>
    </div>

    <!-- Message Edit Modal -->
    <div id="message-edit-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg w-full max-w-md mx-4 shadow-lg">
            <div class="p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Rediger besked</h3>
            </div>
            <form id="edit-form" class="p-5 space-y-4">
                <input type="hidden" id="edit-message-id">
                <textarea id="edit-message-content" class="w-full p-3 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="4" required></textarea>
                <div class="flex justify-end gap-2">
                    <button type="button" id="cancel-edit-btn" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors">
                        Annuller
                    </button>
                    <button type="submit" class="py-2 px-4 bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors">
                        Gem ændringer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg w-full max-w-sm mx-4 shadow-lg">
            <div class="p-5 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Bekræft sletning</h3>
            </div>
            <div class="p-5">
                <p class="text-gray-700">Er du sikker på, at du vil slette denne besked? Denne handling kan ikke fortrydes.</p>
            </div>
            <div class="p-4 border-t border-gray-200 flex justify-end gap-2">
                <button id="cancel-delete-btn" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded transition-colors">
                    Annuller
                </button>
                <button id="confirm-delete-btn" class="py-2 px-4 bg-red-500 hover:bg-red-600 text-white rounded transition-colors">
                    Slet besked
                </button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Variabler til at holde styr på aktuel samtale
            let currentConversation = {
                id: null,
                type: null,
                name: null
            };

            // Modal-relaterede variabler
            let selectedMessageId = null;
            let selectedMessageContent = '';

            // DOM-elementer
            const searchInput = document.getElementById('message-search');
            const contactItems = document.querySelectorAll('.contact-item');
            const conversationArea = document.getElementById('conversation-area');
            const contactsSidebar = document.getElementById('contacts-sidebar');
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const toggleViewBtn = document.getElementById('toggle-view-btn');
            const mobilePageTitle = document.getElementById('mobile-page-title');

            // Modal-elementer
            const messageActionModal = document.getElementById('message-action-modal');
            const messageEditModal = document.getElementById('message-edit-modal');
            const deleteConfirmModal = document.getElementById('delete-confirm-modal');
            const editMessageBtn = document.getElementById('edit-message-btn');
            const deleteMessageBtn = document.getElementById('delete-message-btn');
            const closeModalBtn = document.getElementById('close-modal-btn');
            const editForm = document.getElementById('edit-form');
            const editMessageId = document.getElementById('edit-message-id');
            const editMessageContent = document.getElementById('edit-message-content');
            const cancelEditBtn = document.getElementById('cancel-edit-btn');
            const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
            const confirmDeleteBtn = document.getElementById('confirm-delete-btn');

            // Funktion til at skifte mellem kontakter og samtale på mobil
            function toggleMobileView(showConversation) {
                if (showConversation) {
                    // Vis samtalevisning, skjul kontakter
                    contactsSidebar.style.display = 'none';
                    conversationArea.style.display = 'flex';
                    toggleViewBtn.style.display = 'block';

                    // Opdater titlen
                    if (currentConversation.name) {
                        mobilePageTitle.textContent = currentConversation.name;
                    }
                } else {
                    // Vis kontaktliste, skjul samtale
                    contactsSidebar.style.display = 'flex';
                    conversationArea.style.display = 'none';
                    toggleViewBtn.style.display = 'none';

                    // Nulstil titlen
                    mobilePageTitle.textContent = 'Beskeder';
                }
            }

            // Håndtér klik på mobilmenu-knappen
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    document.getElementById('mobile-sidebar').classList.remove('hidden');
                });
            }

            // Håndtér klik på tilbage-knappen
            if (toggleViewBtn) {
                toggleViewBtn.addEventListener('click', function() {
                    toggleMobileView(false);
                });
            }

            // Tilføj globale event listeners for at lytte efter klik på egne beskeder
            document.addEventListener('click', function(e) {
                // Tjek om det er en egen besked der blev klikket på
                if (e.target.closest('.own-message')) {
                    const messageElement = e.target.closest('.own-message');
                    selectedMessageId = messageElement.getAttribute('data-message-id');
                    selectedMessageContent = messageElement.textContent.trim();

                    // Vis handling modal
                    messageActionModal.classList.remove('hidden');
                    messageActionModal.classList.add('flex');
                }
            });

            // Skjul action modal når der klikkes på annuller
            closeModalBtn.addEventListener('click', function() {
                messageActionModal.classList.add('hidden');
                messageActionModal.classList.remove('flex');
            });

            // Håndter klik på Rediger besked knappen
            editMessageBtn.addEventListener('click', function() {
                // Skjul action modal
                messageActionModal.classList.add('hidden');
                messageActionModal.classList.remove('flex');

                // Sæt editMessageContent med beskedindholdet og vis edit modal
                editMessageId.value = selectedMessageId;
                editMessageContent.value = selectedMessageContent;
                messageEditModal.classList.remove('hidden');
                messageEditModal.classList.add('flex');

                // Fokus på text area
                editMessageContent.focus();
            });

            // Håndter klik på Slet besked knappen
            deleteMessageBtn.addEventListener('click', function() {
                // Skjul action modal
                messageActionModal.classList.add('hidden');
                messageActionModal.classList.remove('flex');

                // Vis bekræft sletning modal
                deleteConfirmModal.classList.remove('hidden');
                deleteConfirmModal.classList.add('flex');
            });

            // Håndter klik på Annuller knapper i edit og delete modals
            cancelEditBtn.addEventListener('click', function() {
                messageEditModal.classList.add('hidden');
                messageEditModal.classList.remove('flex');
            });

            cancelDeleteBtn.addEventListener('click', function() {
                deleteConfirmModal.classList.add('hidden');
                deleteConfirmModal.classList.remove('flex');
            });

            // Håndter indsendelse af edit form
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const messageId = editMessageId.value;
                const newContent = editMessageContent.value.trim();

                if (newContent) {
                    // Send ændring til serveren
                    updateMessage(messageId, newContent);

                    // Skjul modal
                    messageEditModal.classList.add('hidden');
                    messageEditModal.classList.remove('flex');
                }
            });

            // Håndter bekræftelse af sletning
            confirmDeleteBtn.addEventListener('click', function() {
                // Send sletning til serveren
                deleteMessage(selectedMessageId);

                // Skjul modal
                deleteConfirmModal.classList.add('hidden');
                deleteConfirmModal.classList.remove('flex');
            });

            // Funktion til at opdatere en besked
            function updateMessage(messageId, newContent) {
                const formData = new FormData();
                formData.append('message_id', messageId);
                formData.append('content', newContent);

                fetch('update_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Genindlæs samtalen for at vise den opdaterede besked
                            loadConversation(currentConversation.id, currentConversation.type);
                        } else {
                            alert('Fejl ved opdatering af besked: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fejl:', error);
                        alert('Der opstod en fejl ved opdatering af beskeden.');
                    });
            }

            // Funktion til at slette en besked
            function deleteMessage(messageId) {
                const formData = new FormData();
                formData.append('message_id', messageId);

                fetch('delete_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Genindlæs samtalen for at vise uden den slettede besked
                            loadConversation(currentConversation.id, currentConversation.type);
                        } else {
                            alert('Fejl ved sletning af besked: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fejl:', error);
                        alert('Der opstod en fejl ved sletning af beskeden.');
                    });
            }

            // Tilføj håndtering af Enter-tast til textarea'er
            document.addEventListener('keydown', function(e) {
                // Find den aktive textarea
                const activeTextarea = document.activeElement;

                // Hvis det er en textarea og Enter trykkes uden Shift
                if (activeTextarea.tagName.toLowerCase() === 'textarea' &&
                    e.key === 'Enter' && !e.shiftKey &&
                    activeTextarea.id !== 'edit-message-content') { // Undlad edit-textarea

                    e.preventDefault(); // Forhindrer ny linje

                    // Find den relevante formular
                    const form = activeTextarea.closest('form');
                    if (form) {
                        // Simulér et klik på send-knappen
                        const submitButton = form.querySelector('button[type="submit"]');
                        if (submitButton) {
                            submitButton.click();
                        }
                    }
                }
            });

            // Søgefunktion
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const staffItems = document.querySelectorAll('#staff-list .contact-item');
                    const residentItems = document.querySelectorAll('#resident-list .contact-item');

                    // Filtrer personale
                    let staffVisible = filterContacts(staffItems, searchTerm);
                    document.getElementById('staff-header').style.display = staffVisible ? 'block' : 'none';

                    // Filtrer beboere
                    let residentsVisible = filterContacts(residentItems, searchTerm);
                    document.getElementById('resident-header').style.display = residentsVisible ? 'block' : 'none';
                });
            }

            // Filtrer kontakter baseret på søgeterm
            function filterContacts(items, searchTerm) {
                let anyVisible = false;

                items.forEach(item => {
                    const nameElement = item.querySelector('.contact-name');
                    if (nameElement) {
                        const name = nameElement.textContent.toLowerCase();

                        if (searchTerm === '' || name.includes(searchTerm)) {
                            item.style.display = 'flex';
                            anyVisible = true;
                        } else {
                            item.style.display = 'none';
                        }
                    }
                });

                return anyVisible;
            }

            // Håndter klik på kontakt
            contactItems.forEach(item => {
                item.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const userType = this.getAttribute('data-type');
                    const userName = this.querySelector('.contact-name').textContent;

                    console.log(`Kontakt klikket: ${userName} (ID: ${userId}, Type: ${userType})`);

                    // Marker den aktive kontakt
                    contactItems.forEach(contact => contact.classList.remove('bg-gray-100'));
                    this.classList.add('bg-gray-100');

                    // Opdater aktuel samtale
                    currentConversation.id = userId;
                    currentConversation.type = userType;
                    currentConversation.name = userName;

                    // Hvis på mobil, skift til samtale-visning
                    if (window.innerWidth < 768) {
                        toggleMobileView(true);
                    }

                    // Indlæs samtale
                    loadConversation(userId, userType);
                });
            });

            // Resize textarea, når brugeren skriver
            document.addEventListener('input', function(e) {
                if (e.target.tagName.toLowerCase() === 'textarea') {
                    e.target.style.height = 'auto';
                    e.target.style.height = e.target.scrollHeight + 'px';
                }
            });

            // Indlæs samtale via fetch API
            function loadConversation(userId, userType) {
                // Vis indlæsningsindikator
                conversationArea.innerHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500 mb-4"></div>
                            <p class="text-gray-500">Indlæser samtale...</p>
                        </div>
                    </div>
                `;
                conversationArea.classList.remove('hidden');

                // Hent samtale fra serveren
                fetch(`get_conversation.php?user_id=${userId}&user_type=${userType}&timestamp=${Date.now()}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`Netværksfejl: ${response.status} ${response.statusText}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Samtale hentet:', data);

                        if (data.success) {
                            // Opdater samtalevisning
                            conversationArea.innerHTML = data.html;

                            // Scroll til bunden af beskederne
                            const messageContainer = document.getElementById('message-container');
                            if (messageContainer) {
                                messageContainer.scrollTop = messageContainer.scrollHeight;
                            }

                            // Opsæt beskedformular
                            setupMessageForm();
                        } else {
                            showError(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Fejl ved indlæsning af samtale:', error);
                        showError('Der opstod en fejl. Prøv igen senere.');
                    });
            }

            // Vis fejlbesked
            function showError(message) {
                const errorHTML = `
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-red-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="text-gray-700 font-medium mb-2">Der opstod en fejl</p>
                            <p class="text-gray-500 mb-4">${message}</p>
                            <button class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors" onclick="window.location.reload()">
                                Prøv igen
                            </button>
                        </div>
                    </div>
                `;

                conversationArea.innerHTML = errorHTML;
            }

            // Opsæt beskedformular
            function setupMessageForm() {
                const messageForm = document.getElementById('message-form');

                if (messageForm) {
                    messageForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        sendMessage(this);
                    });
                }
            }

            // Send besked
            function sendMessage(form) {
                const messageInput = document.getElementById('message-input');
                const message = messageInput.value.trim();

                if (!message) return;

                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;

                // Vis besked med det samme (optimistisk UI)
                const messageContainer = document.getElementById('message-container');

                if (messageContainer) {
                    // Generer et midlertidigt ID for den nye besked (for optimistisk UI)
                    const tempId = 'temp-' + Date.now();

                    const messageElement = document.createElement('div');
                    messageElement.className = 'text-right mb-3';
                    messageElement.innerHTML = `
                        <div class="inline-block max-w-[75%] bg-blue-500 text-white rounded-lg p-3 own-message cursor-pointer hover:opacity-90" data-message-id="${tempId}">
                            ${message.replace(/\n/g, '<br>')}
                        </div>
                    `;

                    messageContainer.appendChild(messageElement);
                    messageContainer.scrollTop = messageContainer.scrollHeight;
                }

                // Send besked til serveren
                const formData = new FormData();
                formData.append('message', message);
                formData.append('recipient_id', currentConversation.id);
                formData.append('recipient_type', currentConversation.type);

                fetch('send_message.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Besked sendt:', data);

                        if (!data.success) {
                            alert('Fejl ved afsendelse: ' + data.message);
                        }

                        // Nulstil input og genaktiver knap
                        messageInput.value = '';
                        messageInput.style.height = 'auto';
                        submitButton.disabled = false;
                    })
                    .catch(error => {
                        console.error('Fejl:', error);
                        alert('Der opstod en fejl. Prøv igen senere.');
                        submitButton.disabled = false;
                    });
            }

            // Tjek efter nye beskeder periodisk
            setInterval(function() {
                if (currentConversation.id && currentConversation.type) {
                    fetch(`check_messages.php?user_id=${currentConversation.id}&user_type=${currentConversation.type}`)
                        .then(response => response.json())
                        .then(data => {
                            console.log('Check for nye beskeder:', data);

                            if (data.success && data.hasNewMessages) {
                                loadConversation(currentConversation.id, currentConversation.type);
                            }
                        })
                        .catch(error => console.error('Fejl ved tjek af nye beskeder:', error));
                }
            }, 1000);

            // Luk alle modaler når man klikker udenfor dem
            window.addEventListener('click', function(e) {
                if (e.target === messageActionModal) {
                    messageActionModal.classList.add('hidden');
                    messageActionModal.classList.remove('flex');
                }

                if (e.target === messageEditModal) {
                    messageEditModal.classList.add('hidden');
                    messageEditModal.classList.remove('flex');
                }

                if (e.target === deleteConfirmModal) {
                    deleteConfirmModal.classList.add('hidden');
                    deleteConfirmModal.classList.remove('flex');
                }
            });

            // Lyt efter vindue-resize for at opdatere visninger ved skift mellem mobil og desktop
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 768) { // md breakpoint
                    // På desktop: Vis altid både kontaktliste og samtale (hvis en samtale er aktiv)
                    contactsSidebar.style.display = 'flex';
                    conversationArea.classList.add('md:flex');

                    if (currentConversation.id) {
                        conversationArea.style.display = 'flex';
                    }

                    toggleViewBtn.style.display = 'none';
                    mobilePageTitle.textContent = 'Beskeder';
                } else {
                    // På mobil: Vis enten kontaktliste eller samtale afhængigt af den aktuelle tilstand
                    if (currentConversation.id && conversationArea.style.display === 'flex') {
                        contactsSidebar.style.display = 'none';
                        toggleViewBtn.style.display = 'block';
                        mobilePageTitle.textContent = currentConversation.name;
                    } else {
                        contactsSidebar.style.display = 'flex';
                        conversationArea.style.display = 'none';
                        toggleViewBtn.style.display = 'none';
                        mobilePageTitle.textContent = 'Beskeder';
                    }
                }
            });

            // Initialisér tilstand for mobile view
            if (window.innerWidth < 768) {
                toggleMobileView(false);
            }
        });
    </script>
</body>

</html>