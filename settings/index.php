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
    $stmt = $conn->prepare("SELECT id, username, name, email, role FROM users ORDER BY role DESC, name ASC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $staff_users[] = $row;
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
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Personlig profil -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h2 class="font-bold text-lg text-gray-800">Personlig profil</h2>
                        </div>
                        
                        <div class="p-4 sm:p-6">
                            <div class="space-y-4">                           
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bruger ID</label>
                                    <input type="text" value="<?php echo $_SESSION['user_id']; ?>" class="w-full bg-gray-100 border border-gray-300 rounded-lg px-3 py-2" readonly>
                                </div>
                                
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Fulde navn</label>
                                    <input type="text" id="name" name="name" value="<?php echo $_SESSION['name']; ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                                
                                <div>
                                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Brugernavn</label>
                                    <input type="text" id="username" name="username" value="<?php echo $_SESSION['username']; ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Rolle</label>
                                    <input type="text" value="<?php echo ucfirst($_SESSION['role']); ?>" class="w-full bg-gray-100 border border-gray-300 rounded-lg px-3 py-2" readonly>
                                </div>
                                
                                <div class="pt-2">
                                    <button id="save-personal-info" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                        Gem ændringer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Skift adgangskode -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h2 class="font-bold text-lg text-gray-800">Skift adgangskode</h2>
                        </div>
                        
                        <div class="p-4 sm:p-6 space-y-4">
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
                                <button id="save-password" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors">
                                    Opdater adgangskode
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Administratorsektion - vises kun for administratorer -->
                <?php if ($_SESSION['role'] === 'Administrator'): ?>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Tilføj bruger -->
                    <div class="bg-white rounded-xl shadow overflow-hidden">
                        <div class="border-b border-gray-200 px-4 py-3">
                            <h2 class="font-bold text-lg text-gray-800">Tilføj bruger</h2>
                        </div>
                        
                        <div class="p-4 sm:p-6 space-y-4">
                            <form id="add-user-form" action="add-user.php" method="POST">
                                <div class="space-y-4">
                                    <div>
                                        <label for="new-user-name" class="block text-sm font-medium text-gray-700 mb-1">Fulde navn</label>
                                        <input type="text" id="new-user-name" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>
                                    
                                    <div>
                                        <label for="new-user-email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                        <input type="email" id="new-user-email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>
                                    
                                    <div>
                                        <label for="new-username" class="block text-sm font-medium text-gray-700 mb-1">Brugernavn</label>
                                        <input type="text" id="new-username" name="username" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="new-user-password" class="block text-sm font-medium text-gray-700 mb-1">Adgangskode</label>
                                        <input type="password" id="new-user-password" name="password" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                    </div>

                                    <div>
                                        <label for="new-user-role" class="block text-sm font-medium text-gray-700 mb-1">Rolle</label>
                                        <select id="new-user-role" name="role" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                                            <option value="Personale">Personale</option>
                                            <option value="Administrator">Administrator</option>
                                        </select>
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
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Navn
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Brugernavn
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Rolle
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Handlinger
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($staff_users as $user): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $user['id']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                                                            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo $user['name']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $user['username']; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo isset($user['email']) ? $user['email'] : 'Ingen email'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $user['role'] === 'Administrator' ? 'bg-primary/10 text-primary' : 'bg-green-100 text-green-800'; ?>">
                                                    <?php echo $user['role']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                <a href="#" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 hover:text-primary mr-2">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <a href="#" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (count($staff_users) === 0): ?>
                                        <tr>
                                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Save personal info
            document.getElementById('save-personal-info').addEventListener('click', function() {
                const name = document.getElementById('name').value;
                const username = document.getElementById('username').value;
                
                if (name.trim() === '' || username.trim() === '') {
                    showAlert('Fejl', 'Alle felter skal udfyldes', 'error');
                    return;
                }
                
                // AJAX call her til backend (simuleret)
                setTimeout(() => {
                    showAlert('Success', 'Dine personlige oplysninger er blevet opdateret', 'success');
                }, 500);
            });
            
            // Save password
            document.getElementById('save-password').addEventListener('click', function() {
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (currentPassword.trim() === '' || newPassword.trim() === '' || confirmPassword.trim() === '') {
                    showAlert('Fejl', 'Alle felter skal udfyldes', 'error');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    showAlert('Fejl', 'De nye adgangskoder matcher ikke', 'error');
                    return;
                }
                
                if (newPassword.length < 8) {
                    showAlert('Fejl', 'Adgangskoden skal være mindst 8 tegn', 'error');
                    return;
                }
                
                // AJAX call her til backend (simuleret)
                setTimeout(() => {
                    showAlert('Success', 'Din adgangskode er blevet opdateret', 'success');
                    document.getElementById('current-password').value = '';
                    document.getElementById('new-password').value = '';
                    document.getElementById('confirm-password').value = '';
                }, 500);
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
                    showAlert('Fejl', 'Alle felter skal udfyldes', 'error');
                    return false;
                }
                
                if (newUserPassword.length < 8) {
                    e.preventDefault();
                    showAlert('Fejl', 'Adgangskoden skal være mindst 8 tegn', 'error');
                    return false;
                }
                
                // Formularen er valid og vil blive sendt
                return true;
            });
            <?php endif; ?>
            
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
    </script>
</body>
</html>