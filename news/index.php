<?php
$page = "settings";

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

// Hent brugerdata fra databasen hvis brugeren er administrator
$staff_users = [];
if ($_SESSION['role'] === 'Administrator' && isset($conn)) {
    $stmt = $conn->prepare("SELECT id, username, name, email, phone, role, profession, profile_image FROM users ORDER BY role DESC, name ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $staff_users[] = $row;
    }
}

// Hent den nuværende brugers fulde profil
$current_user = [];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $current_user = $result->fetch_assoc();
    }
}
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Settings content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Indstillinger</h1>
                        <p class="text-sm sm:text-base text-gray-600">Administrer din konto og brugerindstillinger</p>
                    </div>
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

                <!-- Main settings area med side-by-side layout -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- Personlig profil -->
                    <div class="bg-white rounded-xl shadow overflow-hidden h-fit">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h2 class="font-bold text-lg text-gray-800">Personlig profil</h2>
                        </div>

                        <div class="p-4 sm:p-6">
                            <form id="update-profile-form" action="update-profile.php" method="POST" enctype="multipart/form-data">
                                <div class="space-y-4">
                                    <!-- Profilbillede -->
                                    <div class="flex flex-col items-center mb-6">
                                        <div class="relative">
                                            <div id="current-profile-preview" class="w-24 h-24 rounded-full overflow-hidden border-4 border-gray-200 bg-gray-100 flex items-center justify-center">
                                                <?php if (!empty($current_user['profile_image']) && file_exists("../employees/images/" . $current_user['profile_image'])): ?>
                                                    <img src="../employees/images/<?php echo htmlspecialchars($current_user['profile_image']); ?>" alt="Profilbillede" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <?php
                                                    $name_parts = explode(' ', $current_user['name'] ?? '');
                                                    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));
                                                    ?>
                                                    <span class="text-2xl font-bold text-gray-500"><?php echo $initials; ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <label for="profile-image-input" class="absolute bottom-0 right-0 bg-primary text-white rounded-full w-8 h-8 flex items-center justify-center cursor-pointer hover:bg-primary/90 transition-colors">
                                                <i class="fas fa-camera text-sm"></i>
                                            </label>
                                        </div>
                                        <input type="file" id="profile-image-input" name="profile_image" accept="image/*" class="hidden">
                                        <p class="text-xs text-gray-500 mt-2 text-center">Klik på kamera-ikonet for at ændre billede</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Bruger ID</label>
                                        <input type="text" value="<?php echo $_SESSION['user_id']; ?>" class="w-full bg-gray-100 border border-gray-300 rounded-lg px-3 py-2" readonly>
                                    </div>

                                    <div>
                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Fulde navn</label>
                                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Brugernavn</label>
                                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($current_user['username'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Telefonnummer</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="profession" class="block text-sm font-medium text-gray-700 mb-1">Profession</label>
                                        <input type="text" id="profession" name="profession" value="<?php echo htmlspecialchars($current_user['profession'] ?? ''); ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Rolle</label>
                                        <input type="text" value="<?php echo ucfirst($current_user['role'] ?? ''); ?>" class="w-full bg-gray-100 border border-gray-300 rounded-lg px-3 py-2" readonly>
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                            Gem ændringer
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Skift adgangskode -->
                    <div class="bg-white rounded-xl shadow overflow-hidden h-fit">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h2 class="font-bold text-lg text-gray-800">Skift adgangskode</h2>
                        </div>

                        <div class="p-4 sm:p-6 space-y-4">
                            <form id="change-password-form" action="change-password.php" method="POST">
                                <div class="space-y-4">
                                    <div>
                                        <label for="current-password" class="block text-sm font-medium text-gray-700 mb-1">Nuværende adgangskode</label>
                                        <input type="password" id="current-password" name="current_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="new-password" class="block text-sm font-medium text-gray-700 mb-1">Ny adgangskode</label>
                                        <input type="password" id="new-password" name="new_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        <p class="text-xs text-gray-500 mt-1">Mindst 8 tegn</p>
                                    </div>

                                    <div>
                                        <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Bekræft adgangskode</label>
                                        <input type="password" id="confirm-password" name="confirm_password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div class="pt-2">
                                        <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                            Opdater adgangskode
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Administratorsektion - vises kun for administratorer -->
                <?php if ($_SESSION['role'] === 'Administrator'): ?>
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Tilføj bruger -->
                        <div class="bg-white rounded-xl shadow overflow-hidden h-fit">
                            <div class="border-b border-gray-200 px-4 py-3">
                                <h2 class="font-bold text-lg text-gray-800">Tilføj bruger</h2>
                                <p class="text-sm text-gray-600 mt-1">Kun administratorer kan tilføje nye brugere til systemet</p>
                            </div>

                            <div class="p-4 sm:p-6 space-y-4">
                                <form id="add-user-form" action="add-user.php" method="POST" enctype="multipart/form-data">
                                    <div class="space-y-4">
                                        <!-- Profilbillede upload -->
                                        <div class="flex flex-col items-center mb-4">
                                            <div class="relative">
                                                <div id="new-user-image-preview" class="w-20 h-20 rounded-full overflow-hidden border-4 border-gray-200 bg-gray-100 flex items-center justify-center">
                                                    <i class="fas fa-user text-2xl text-gray-400"></i>
                                                </div>
                                                <label for="new-user-image-input" class="absolute bottom-0 right-0 bg-primary text-white rounded-full w-6 h-6 flex items-center justify-center cursor-pointer hover:bg-primary/90 transition-colors">
                                                    <i class="fas fa-camera text-xs"></i>
                                                </label>
                                            </div>
                                            <input type="file" id="new-user-image-input" name="profile_image" accept="image/*" class="hidden">
                                            <p class="text-xs text-gray-500 mt-1 text-center">Valgfrit profilbillede</p>
                                        </div>

                                        <div>
                                            <label for="new-user-name" class="block text-sm font-medium text-gray-700 mb-1">Fulde navn <span class="text-red-500">*</span></label>
                                            <input type="text" id="new-user-name" name="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        </div>

                                        <div>
                                            <label for="new-user-email" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                            <input type="email" id="new-user-email" name="email" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        </div>

                                        <div>
                                            <label for="new-username" class="block text-sm font-medium text-gray-700 mb-1">Brugernavn <span class="text-red-500">*</span></label>
                                            <input type="text" id="new-username" name="username" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        </div>

                                        <div>
                                            <label for="new-user-password" class="block text-sm font-medium text-gray-700 mb-1">Adgangskode <span class="text-red-500">*</span></label>
                                            <input type="password" id="new-user-password" name="password" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                            <p class="text-xs text-gray-500 mt-1">Mindst 8 tegn</p>
                                        </div>

                                        <div>
                                            <label for="new-user-phone" class="block text-sm font-medium text-gray-700 mb-1">Telefonnummer</label>
                                            <input type="tel" id="new-user-phone" name="phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        </div>

                                        <div>
                                            <label for="new-user-profession" class="block text-sm font-medium text-gray-700 mb-1">Profession</label>
                                            <input type="text" id="new-user-profession" name="profession" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                        </div>

                                        <div>
                                            <label for="new-user-role" class="block text-sm font-medium text-gray-700 mb-1">Rolle <span class="text-red-500">*</span></label>
                                            <select id="new-user-role" name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                                <option value="Personale">Personale</option>
                                                <option value="Administrator">Administrator</option>
                                            </select>
                                            <p class="text-xs text-gray-500 mt-1">Administratorer kan tilføje andre brugere, personale kan ikke</p>
                                        </div>

                                        <div class="pt-2">
                                            <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                                <i class="fas fa-plus mr-1"></i> Tilføj bruger
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Personaleliste - spænder over 2 kolonner -->
                        <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
                            <div class="border-b border-gray-200 px-4 py-3">
                                <h2 class="font-bold text-lg text-gray-800">Personaleliste</h2>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="overflow-x-auto rounded-lg border border-gray-200 max-h-[800px] overflow-y-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Bruger
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Kontakt
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Rolle
                                                </th>
                                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Handling
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <?php foreach ($staff_users as $user): ?>
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="flex items-center">
                                                            <div class="flex-shrink-0 h-10 w-10">
                                                                <?php if (!empty($user['profile_image']) && file_exists("../employees/images/" . $user['profile_image'])): ?>
                                                                    <img class="h-10 w-10 rounded-full object-cover" src="../employees/images/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="<?php echo htmlspecialchars($user['name']); ?>">
                                                                <?php else: ?>
                                                                    <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                                                        <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900">
                                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                                </div>
                                                                <div class="text-sm text-gray-500">
                                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                                </div>
                                                                <?php if (!empty($user['profession'])): ?>
                                                                    <div class="text-xs text-gray-400">
                                                                        <?php echo htmlspecialchars($user['profession']); ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div><?php echo htmlspecialchars($user['email'] ?? 'Ingen email'); ?></div>
                                                        <?php if (!empty($user['phone'])): ?>
                                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($user['phone']); ?></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'Administrator' ? 'bg-primary/10 text-primary' : 'bg-green-100 text-green-800'; ?>">
                                                            <?php echo htmlspecialchars($user['role']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                        <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                            <button onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if (count($staff_users) === 0): ?>
                                                <tr>
                                                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                                                        Ingen brugere fundet
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div id="delete-user-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md scale-95 transition-transform duration-300 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Bekræft sletning</h3>
            <p class="text-gray-600 mb-2" id="delete-user-message">Er du sikker på, at du vil slette denne bruger?</p>
            <p class="text-gray-500 text-sm mb-6">Denne handling kan ikke fortrydes.</p>
            <div class="flex justify-end gap-3">
                <button id="cancel-delete-user" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    Annuller
                </button>
                <form id="delete-user-form" action="delete-user.php" method="POST">
                    <input type="hidden" id="delete-user-id" name="user_id" value="">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors">
                        Slet bruger
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Image preview for current user profile
            const profileImageInput = document.getElementById('profile-image-input');
            const currentProfilePreview = document.getElementById('current-profile-preview');

            if (profileImageInput && currentProfilePreview) {
                profileImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Tjek filtype
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        if (!allowedTypes.includes(file.type.toLowerCase())) {
                            alert('Kun JPEG, PNG, GIF og WebP billeder er tilladt');
                            this.value = '';
                            return;
                        }

                        // Tjek filstørrelse (5MB)
                        if (file.size > 5 * 1024 * 1024) {
                            alert('Billedet må maksimalt være 5MB');
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            currentProfilePreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Image preview for new user
            const newUserImageInput = document.getElementById('new-user-image-input');
            const newUserImagePreview = document.getElementById('new-user-image-preview');

            if (newUserImageInput && newUserImagePreview) {
                newUserImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        // Tjek filtype
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                        if (!allowedTypes.includes(file.type.toLowerCase())) {
                            alert('Kun JPEG, PNG, GIF og WebP billeder er tilladt');
                            this.value = '';
                            return;
                        }

                        // Tjek filstørrelse (5MB)
                        if (file.size > 5 * 1024 * 1024) {
                            alert('Billedet må maksimalt være 5MB');
                            this.value = '';
                            return;
                        }

                        const reader = new FileReader();
                        reader.onload = function(e) {
                            newUserImagePreview.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }

            // Form validation for update profile form
            document.getElementById('update-profile-form').addEventListener('submit', function(e) {
                const name = document.getElementById('name').value;
                const username = document.getElementById('username').value;
                const email = document.getElementById('email').value;

                if (name.trim() === '' || username.trim() === '' || email.trim() === '') {
                    e.preventDefault();
                    showAlert('Fejl', 'Navn, brugernavn og email skal udfyldes', 'error');
                    return false;
                }

                return true;
            });

            // Form validation for change password form
            document.getElementById('change-password-form').addEventListener('submit', function(e) {
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;

                if (currentPassword.trim() === '' || newPassword.trim() === '' || confirmPassword.trim() === '') {
                    e.preventDefault();
                    showAlert('Fejl', 'Alle felter skal udfyldes', 'error');
                    return false;
                }
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    showAlert('Fejl', 'De nye adgangskoder matcher ikke', 'error');
                    return false;
                }

                if (newPassword.length < 8) {
                    e.preventDefault();
                    showAlert('Fejl', 'Adgangskoden skal være mindst 8 tegn', 'error');
                    return false;
                }

                return true;
            });

            <?php if ($_SESSION['role'] === 'Administrator'): ?>
                // Form validation for add user form
                document.getElementById('add-user-form').addEventListener('submit', function(e) {
                    const newUserName = document.getElementById('new-user-name').value;
                    const newUserEmail = document.getElementById('new-user-email').value;
                    const newUsername = document.getElementById('new-username').value;
                    const newUserPassword = document.getElementById('new-user-password').value;

                    if (newUserName.trim() === '' || newUsername.trim() === '' || newUserPassword.trim() === '' || newUserEmail.trim() === '') {
                        e.preventDefault();
                        showAlert('Fejl', 'Alle påkrævede felter skal udfyldes', 'error');
                        return false;
                    }

                    if (newUserPassword.length < 8) {
                        e.preventDefault();
                        showAlert('Fejl', 'Adgangskoden skal være mindst 8 tegn', 'error');
                        return false;
                    }

                    // Validate email format
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(newUserEmail)) {
                        e.preventDefault();
                        showAlert('Fejl', 'Indtast en gyldig email-adresse', 'error');
                        return false;
                    }

                    return true;
                });
            <?php endif; ?>

            // Delete user confirmation modal functionality
            const deleteUserModal = document.getElementById('delete-user-modal');
            const deleteUserModalContainer = deleteUserModal.querySelector('.bg-white');
            const cancelDeleteUserBtn = document.getElementById('cancel-delete-user');
            const deleteUserIdField = document.getElementById('delete-user-id');
            const deleteUserMessage = document.getElementById('delete-user-message');

            function confirmDeleteUser(userId, userName) {
                deleteUserIdField.value = userId;
                deleteUserMessage.textContent = `Er du sikker på, at du vil slette brugeren "${userName}"?`;

                // Vis modal med animation
                deleteUserModal.classList.remove('hidden');
                setTimeout(() => {
                    deleteUserModal.classList.add('opacity-100');
                    deleteUserModalContainer.classList.remove('scale-95');
                    deleteUserModalContainer.classList.add('scale-100');
                }, 10);
                document.body.classList.add('overflow-hidden');
            }

            function closeDeleteUserModal() {
                // Skjul modal med animation
                deleteUserModal.classList.remove('opacity-100');
                deleteUserModalContainer.classList.remove('scale-100');
                deleteUserModalContainer.classList.add('scale-95');

                // Vent på at animationen er færdig før vi fjerner modalen helt
                setTimeout(() => {
                    deleteUserModal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }, 300);
            }

            cancelDeleteUserBtn.addEventListener('click', closeDeleteUserModal);

            // Luk delete modal når der klikkes udenfor
            deleteUserModal.addEventListener('click', (e) => {
                if (e.target === deleteUserModal) {
                    closeDeleteUserModal();
                }
            });

            // Luk delete modal med Escape-tasten
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !deleteUserModal.classList.contains('hidden')) {
                    closeDeleteUserModal();
                }
            });

            // Helper function to show alerts
            function showAlert(title, message, type) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'fixed top-6 right-6 p-4 rounded-lg shadow-lg z-50 flex items-center gap-3 ' +
                    (type === 'success' ? 'bg-green-100 border-l-4 border-green-500 text-green-700' : 'bg-red-100 border-l-4 border-red-500 text-red-700');

                alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <div>
                    <h3 class="font-bold">${title}</h3>
                    <p>${message}</p>
                </div>
            `;

                document.body.appendChild(alertDiv);

                // Fade in
                alertDiv.style.opacity = '0';
                alertDiv.style.transition = 'opacity 0.3s ease-in-out';
                setTimeout(() => {
                    alertDiv.style.opacity = '1';
                }, 10);

                // Remove after 3 seconds
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => {
                        document.body.removeChild(alertDiv);
                    }, 300);
                }, 3000);
            }
        });

        // Make confirmDeleteUser function globally available
        window.confirmDeleteUser = function(userId, userName) {
            const deleteUserIdField = document.getElementById('delete-user-id');
            const deleteUserMessage = document.getElementById('delete-user-message');
            const deleteUserModal = document.getElementById('delete-user-modal');
            const deleteUserModalContainer = deleteUserModal.querySelector('.bg-white');

            deleteUserIdField.value = userId;
            deleteUserMessage.textContent = `Er du sikker på, at du vil slette brugeren ${userName}?`;

            // Vis modal med animation
            deleteUserModal.classList.remove('hidden');
            setTimeout(() => {
                deleteUserModal.classList.add('opacity-100');
                deleteUserModalContainer.classList.remove('scale-95');
                deleteUserModalContainer.classList.add('scale-100');
            }, 10);
            document.body.classList.add('overflow-hidden');
        };
    </script>
</body>

</html>