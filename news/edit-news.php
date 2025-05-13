<?php 
$page = "news";

// Start session
if (session_status() == PHP_SESSION_NONE) {
   session_start();
}

// Tjek om bruger er logget ind, ellers redirect til login
if (!isset($_SESSION['user_id'])) {
   header("Location: ../login/");
   exit();
}

// Database forbindelse
include '../database/db_conn.php';
include '../components/header.php';

// Tjek om news_id er angivet
$news_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$news_id) {
   $_SESSION['error_message'] = "Intet nyhed-ID angivet";
   header("Location: ./");
   exit();
}

// Hent nyheden fra databasen
$news = null;
if (isset($conn)) {
   $stmt = $conn->prepare("SELECT * FROM news WHERE id = ?");
   $stmt->bind_param("i", $news_id);
   $stmt->execute();
   $result = $stmt->get_result();
   
   if ($result->num_rows > 0) {
       $news = $result->fetch_assoc();
   } else {
       $_SESSION['error_message'] = "Nyheden blev ikke fundet";
       header("Location: ./");
       exit();
   }
}

// Tjek om brugeren har ret til at redigere nyheden
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$can_edit = false;

if ($user_role == 'Administrator') {
   $can_edit = true;
} elseif ($news['created_by'] == $user_id) {
   $can_edit = true;
}

if (!$can_edit) {
   $_SESSION['error_message'] = "Du har ikke tilladelse til at redigere denne nyhed";
   header("Location: ./");
   exit();
}
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
   <div class="flex flex-grow">
       <?php include '../components/sidebar.php'; ?>

       <!-- Main content -->
       <main class="flex-grow">
           <!-- Edit News content -->
           <div class="p-3 sm:p-6">
               <div class="mb-4 sm:mb-6 flex justify-between items-center">
                   <div>
                       <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Rediger nyhed</h1>
                       <p class="text-sm sm:text-base text-gray-600">Opdater indholdet og indstillingerne for denne nyhed</p>
                   </div>
                   <a href="./" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                       <i class="fas fa-arrow-left"></i>
                       <span>Tilbage til oversigt</span>
                   </a>
               </div>

               <!-- Edit News Form -->
               <div class="bg-white rounded-xl shadow p-4 sm:p-6 animate-fade-in">
                   <form id="edit-news-form" action="save-news.php" method="POST" class="space-y-6">
                       <!-- Skjulte felter til news ID og user ID -->
                       <input type="hidden" name="news_id" value="<?php echo $news['id']; ?>">
                       <input type="hidden" name="created_by" value="<?php echo $news['created_by']; ?>">
                       
                       <!-- Basic Information -->
                       <div>
                           <h2 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-100">Nyhedsdetaljer</h2>

                           <!-- Title and options -->
                           <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                               <div class="md:col-span-2">
                                   <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titel <span class="text-danger">*</span></label>
                                   <input type="text" id="title" name="title" required
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"
                                       value="<?php echo htmlspecialchars($news['title']); ?>">
                               </div>
                               <div>
                                   <label class="block text-sm font-medium text-gray-700 mb-1">Indstillinger</label>
                                   <div class="space-y-2">
                                       <label class="flex items-center gap-2 text-sm">
                                           <input type="checkbox" name="is_featured" id="is_featured" <?php echo $news['is_featured'] ? 'checked' : ''; ?>>
                                           <span class="text-primary">Markér som vigtig</span>
                                       </label>
                                   </div>
                               </div>
                           </div>

                           <!-- Content -->
                           <div class="mb-4">
                               <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Indhold <span class="text-danger">*</span></label>
                               <textarea id="content" name="content" rows="8" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/50"><?php echo htmlspecialchars($news['content']); ?></textarea>
                               <p class="text-xs text-gray-500 mt-1">Skriv nyheden i almindeligt tekstformat. Nye linjer vil blive bevaret.</p>
                           </div>
                       </div>

                       <!-- Confirmation -->
                       <div class="flex justify-end gap-3 pt-2">
                           <a href="./" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                               Annuller
                           </a>
                           <button type="submit" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                               <i class="fas fa-save"></i>
                               <span>Gem ændringer</span>
                           </button>
                       </div>
                   </form>
               </div>
           </div>
       </main>
   </div>

   <script>
       // Form validation and enhancement
       const form = document.getElementById('edit-news-form');
       const titleInput = document.getElementById('title');
       const contentInput = document.getElementById('content');
       
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

           if (contentInput.value.trim() === '') {
               isValid = false;
               highlightInvalidField(contentInput);
           } else {
               resetField(contentInput);
           }

           if (isValid) {
               // Submit formularen
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