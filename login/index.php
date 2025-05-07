<?php
$page = "login";
include '../components/header.php';
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
            <form action="<?= $base ?>" method="GET" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Brugernavn</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required
                            placeholder="Indtast dit brugernavn"
                            class="pl-10 block w-full rounded-lg border-gray-300 border px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50">
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

                <button type="submit" class="w-full bg-primary hover:bg-primary/90 text-white py-2 rounded-lg transition-colors">
                    Log ind
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
    </script>
</body>

</html>