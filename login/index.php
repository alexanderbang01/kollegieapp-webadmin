<?php
$page = "login";
include '../components/header.php';

// Start session hvis den ikke allerede er startet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../");
    exit();
}

// Process login form submission
$error = "";
$loginAttempted = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include '../database/db_conn.php';
    
    $username = $_POST['username'];
    $password = $_POST['password'];
    $loginAttempted = true;
    
    if (empty($username) || empty($password)) {
        $error = "Brugernavn og adgangskode er påkrævet";
    } else {
        // Prepare SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Validate password with password_verify
            if (password_verify($password, $user['password'])) {
                // Password is correct, create session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                
                // Redirect to dashboard
                header("Location: ../");
                exit();
            } else {
                $error = "Forkert brugernavn eller adgangskode";
            }
        } else {
            $error = "Forkert brugernavn eller adgangskode";
        }
        
        $stmt->close();
    }
}
?>

<body class="font-poppins bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-xl shadow-lg overflow-hidden animate-fade-in">
        <div class="bg-primary p-6 text-white text-center">
            <div class="mb-4 flex justify-center">
                <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center">
                    <i class="fas fa-building text-3xl"></i>
                </div>
            </div>
            <h1 class="text-2xl font-bold">Mercantec Kollegium</h1>
            <p class="text-white/80">Administration</p>
        </div>

        <div class="p-6">
            <!-- Fejlmeddelelse med JavaScript animation i stedet for page refresh -->
            <div id="error-message" class="<?php echo !empty($error) ? '' : 'hidden'; ?> bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 opacity-100 transition-opacity duration-300" role="alert">
                <p id="error-text"><?php echo $error; ?></p>
            </div>
            
            <!-- Login form med AJAX handling -->
            <form id="login-form" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Brugernavn</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required
                            placeholder="Indtast dit brugernavn"
                            class="pl-10 block w-full rounded-lg border-gray-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Adgangskode</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" required
                            placeholder="Indtast din adgangskode"
                            class="pl-10 block w-full rounded-lg border-gray-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
                        <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember"
                            class="h-4 w-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Husk mig
                        </label>
                    </div>
                    <a href="#" class="text-sm text-primary hover:text-primary/80">
                        Glemt adgangskode?
                    </a>
                </div>

                <button type="submit" id="login-button" class="w-full bg-primary hover:bg-primary/90 text-white py-2 rounded-lg transition-colors flex items-center justify-center">
                    <span>Log ind</span>
                    <span id="loading-spinner" class="ml-2 hidden">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                </button>
            </form>
        </div>

        <div class="border-t p-4 text-center text-gray-500 text-sm">
            &copy; <?= date('Y') ?> Mercantec Kollegium • Alle rettigheder forbeholdes
        </div>
    </div>

    <script>
        // Adgangskode synlighed toggle
        const togglePassword = document.getElementById('toggle-password');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            // Skift ikon baseret på synlighed
            togglePassword.querySelector('i').classList.toggle('fa-eye');
            togglePassword.querySelector('i').classList.toggle('fa-eye-slash');
        });

        // AJAX login håndtering
        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Vis loading spinner
            document.getElementById('loading-spinner').classList.remove('hidden');
            document.getElementById('login-button').disabled = true;
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            
            // Opret FormData objekt
            const formData = new FormData();
            formData.append('username', username);
            formData.append('password', password);
            if (remember) {
                formData.append('remember', '1');
            }
            
            // Send AJAX request
            fetch('login_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Skjul loading spinner
                document.getElementById('loading-spinner').classList.add('hidden');
                document.getElementById('login-button').disabled = false;
                
                if (data.success) {
                    // Redirect til dashboard
                    window.location.href = '../';
                } else {
                    // Vis fejlmeddelelse med animation
                    const errorMessage = document.getElementById('error-message');
                    const errorText = document.getElementById('error-text');
                    
                    errorText.textContent = data.message;
                    errorMessage.classList.remove('hidden');
                    
                    // Highlight animation
                    errorMessage.classList.add('bg-red-200');
                    setTimeout(() => {
                        errorMessage.classList.remove('bg-red-200');
                    }, 200);
                }
            })
            .catch(error => {
                // Skjul loading spinner og vis fejl
                document.getElementById('loading-spinner').classList.add('hidden');
                document.getElementById('login-button').disabled = false;
                
                const errorMessage = document.getElementById('error-message');
                const errorText = document.getElementById('error-text');
                
                errorText.textContent = 'Der opstod en fejl ved forbindelse til serveren';
                errorMessage.classList.remove('hidden');
            });
        });

        <?php if ($loginAttempted && !empty($error)): ?>
        // Auto-vis fejlmeddelelse hvis login fejler ved initial side-load
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.getElementById('error-message');
            errorMessage.classList.remove('hidden');
            
            // Highlight animation
            errorMessage.classList.add('bg-red-200');
            setTimeout(() => {
                errorMessage.classList.remove('bg-red-200');
            }, 200);
        });
        <?php endif; ?>
    </script>
</body>

</html>