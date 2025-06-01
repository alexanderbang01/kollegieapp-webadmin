<?php
$page = "employees";

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

// Hent medarbejdere fra users tabellen
$employees = [];
$total_employees = 0;

if (isset($conn)) {
    // Håndter søgning
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Håndter paginering
    $page_number = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 6;
    $offset = ($page_number - 1) * $items_per_page;

    // Opbyg forespørgsel - kun hent personale og administratorer
    $query_params = [];
    $where_clauses = ["role IN ('Administrator', 'Personale')"];

    if ($search_query) {
        $where_clauses[] = "(name LIKE ? OR email LIKE ? OR profession LIKE ?)";
        $search_param = "%{$search_query}%";
        $query_params[] = $search_param;
        $query_params[] = $search_param;
        $query_params[] = $search_param;
    }

    $where_sql = "WHERE " . implode(" AND ", $where_clauses);

    // Tæl total antal medarbejdere med de valgte filtre
    $count_sql = "SELECT COUNT(*) as total FROM users $where_sql";

    if (!empty($query_params)) {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param(str_repeat('s', count($query_params)), ...$query_params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
    } else {
        $count_result = $conn->query($count_sql);
    }

    $total_employees = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_employees / $items_per_page);

    // Hent medarbejdere med paginering
    $sql = "SELECT * FROM users $where_sql ORDER BY name ASC LIMIT ?, ?";

    $stmt = $conn->prepare($sql);

    if (!empty($query_params)) {
        // Tilføj paginering-parametre
        $query_params[] = $offset;
        $query_params[] = $items_per_page;

        $param_types = str_repeat('s', count($query_params) - 2) . 'ii';
        $stmt->bind_param($param_types, ...$query_params);
    } else {
        $stmt->bind_param("ii", $offset, $items_per_page);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- Employees content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Medarbejdere</h1>
                    <p class="text-sm sm:text-base text-gray-600">Administrer kollegiets medarbejdere</p>
                </div>

                <!-- Search and filter -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <!-- Search field -->
                        <div class="relative sm:w-1/2 w-full">
                            <input type="text" id="live-search" placeholder="Søg efter medarbejder..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-primary/50" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>

                        <!-- Layout controls -->
                        <div class="sm:w-1/2 flex items-center justify-end gap-4">
                            <div class="flex">
                                <button type="button" id="view-grid" class="w-10 h-10 flex items-center justify-center rounded bg-primary text-white" title="Grid visning">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button type="button" id="view-list" class="w-10 h-10 flex items-center justify-center rounded hover:bg-gray-100" title="Listevisning">
                                    <i class="fas fa-list"></i>
                                </button>
                            </div>
                        </div>
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

                <!-- Employees Grid View -->
                <div id="employees-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                    <?php if (empty($employees)): ?>
                        <div class="col-span-3 bg-white rounded-xl shadow p-6 text-center">
                            <p class="text-gray-500">Ingen medarbejdere fundet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($employees as $index => $employee): ?>
                            <?php
                            // Generer initialer til medarbejder-avatar
                            $name_parts = explode(' ', $employee['name']);
                            // Sikr korrekt UTF-8 håndtering
                            $first_initial = mb_substr($name_parts[0], 0, 1, 'UTF-8');
                            $last_initial = isset($name_parts[1]) ? mb_substr($name_parts[count($name_parts) - 1], 0, 1, 'UTF-8') : '';
                            $initials = mb_strtoupper($first_initial . $last_initial, 'UTF-8');

                            // Vælg en farve baseret på medarbejder-ID (for konsistens)
                            $colors = ['primary', 'secondary', 'accent'];
                            $color = $colors[$employee['id'] % count($colors)];
                            ?>
                            <!-- Employee Card -->
                            <div class="bg-white rounded-xl shadow animate-fade-in delay-<?php echo ($index % 4) * 100; ?> overflow-hidden employee-card" data-id="<?php echo $employee['id']; ?>" data-name="<?php echo htmlspecialchars($employee['name']); ?>" data-profesion="<?php echo htmlspecialchars($employee['profession'] ?? ''); ?>" data-email="<?php echo htmlspecialchars($employee['email']); ?>" data-phone="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                                <div class="flex justify-between items-center p-4 border-b border-gray-100">
                                    <div class="flex items-center gap-3">
                                        <?php if ($employee['profile_image']): ?>
                                            <div class="w-12 h-12 rounded-full bg-gray-200 overflow-hidden">
                                                <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="w-full h-full object-cover">
                                            </div>
                                        <?php else: ?>
                                            <div class="w-12 h-12 rounded-full bg-<?php echo $color; ?> text-white flex items-center justify-center text-lg font-medium">
                                                <?php echo $initials; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($employee['name']); ?></h3>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['profession'] ?? $employee['role']); ?></p>
                                        </div>
                                    </div>
                                    <div class="relative employee-dropdown">
                                        <button class="text-gray-500 hover:text-primary transition-colors p-1 dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown('<?php echo $employee['id']; ?>')">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div id="dropdown-<?php echo $employee['id']; ?>" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10 dropdown-menu">
                                            <div class="py-1">
                                                <a href="edit-employee.php?id=<?php echo $employee['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                                                    <i class="fas fa-pencil-alt mr-2"></i> Rediger
                                                </a>
                                                <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                                                    <i class="fas fa-envelope mr-2"></i> Send besked
                                                </a>
                                                <button onclick="event.stopPropagation(); confirmDelete(<?php echo $employee['id']; ?>)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-danger transition-colors">
                                                    <i class="fas fa-trash mr-2"></i> Slet
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                        <div>
                                            <p class="text-xs text-gray-500">Email</p>
                                            <p class="text-sm truncate"><?php echo htmlspecialchars($employee['email']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Telefon</p>
                                            <p class="text-sm"><?php echo htmlspecialchars($employee['phone'] ?? 'Ikke angivet'); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">Rolle</p>
                                            <p class="text-sm"><?php echo htmlspecialchars($employee['role']); ?></p>
                                        </div>
                                        <?php if ($employee['profession']): ?>
                                            <div>
                                                <p class="text-xs text-gray-500">Profession</p>
                                                <p class="text-sm"><?php echo htmlspecialchars($employee['profession']); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex justify-end mt-2">
                                        <button class="text-primary hover:text-primary/80 transition-colors text-sm font-medium" onclick="showEmployeeDetails(<?php echo $employee['id']; ?>)">
                                            Se oplysninger
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Employees List View (hidden by default) -->
                <div id="employees-list" class="hidden bg-white rounded-xl shadow overflow-hidden mb-6 animate-fade-in">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-50 border-b">
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Navn</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Profession</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Telefon</th>
                                <th class="px-4 py-3 text-center text-sm font-medium text-gray-500">Handlinger</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($employees)): ?>
                                <tr>
                                    <td colspan="5" class="px-4 py-3 text-center text-gray-500">Ingen medarbejdere fundet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($employees as $employee): ?>
                                    <?php
                                    // Generer initialer til medarbejder-avatar
                                    $name_parts = explode(' ', $employee['name']);
                                    $initials = strtoupper(substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? substr($name_parts[1], 0, 1) : ''));

                                    // Vælg en farve baseret på medarbejder-ID (for konsistens)
                                    $colors = ['primary', 'secondary', 'accent'];
                                    $color = $colors[$employee['id'] % count($colors)];
                                    ?>
                                    <tr class="border-b hover:bg-gray-50 employee-card" data-id="<?php echo $employee['id']; ?>" data-name="<?php echo htmlspecialchars($employee['name']); ?>" data-profesion="<?php echo htmlspecialchars($employee['profession'] ?? ''); ?>" data-email="<?php echo htmlspecialchars($employee['email']); ?>" data-phone="<?php echo htmlspecialchars($employee['phone'] ?? ''); ?>">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-2">
                                                <?php if ($employee['profile_image']): ?>
                                                    <div class="w-8 h-8 rounded-full bg-gray-200 overflow-hidden">
                                                        <img src="<?php echo htmlspecialchars($employee['profile_image']); ?>" alt="<?php echo htmlspecialchars($employee['name']); ?>" class="w-full h-full object-cover">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="w-8 h-8 rounded-full bg-<?php echo $color; ?> text-white flex items-center justify-center text-sm font-medium">
                                                        <?php echo $initials; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="font-medium"><?php echo htmlspecialchars($employee['name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($employee['profession'] ?? $employee['role']); ?></td>
                                        <td class="px-4 py-3 text-sm truncate max-w-[200px]"><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($employee['phone'] ?? 'Ikke angivet'); ?></td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-center gap-2">
                                                <button class="text-gray-500 hover:text-primary transition-colors p-1" title="Se oplysninger" onclick="showEmployeeDetails(<?php echo $employee['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <div class="relative employee-dropdown">
                                                    <button class="text-gray-500 hover:text-primary transition-colors p-1 dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown('<?php echo $employee['id']; ?>-list')">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div id="dropdown-<?php echo $employee['id']; ?>-list" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10 dropdown-menu">
                                                        <div class="py-1">
                                                            <a href="edit-employee.php?id=<?php echo $employee['id']; ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                                                                <i class="fas fa-pencil-alt mr-2"></i> Rediger
                                                            </a>
                                                            <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                                                                <i class="fas fa-envelope mr-2"></i> Send besked
                                                            </a>
                                                            <button onclick="event.stopPropagation(); confirmDelete(<?php echo $employee['id']; ?>)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-danger transition-colors">
                                                                <i class="fas fa-trash mr-2"></i> Slet
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div id="pagination-container">
                    <?php if ($total_pages > 1): ?>
                        <div class="flex justify-between items-center">
                            <div class="text-gray-500 text-sm">
                                Viser <?php echo $offset + 1; ?>-<?php echo min($offset + count($employees), $total_employees); ?> af <?php echo $total_employees; ?> medarbejdere
                            </div>
                            <div class="flex gap-1">
                                <a href="?page=<?php echo max(1, $page_number - 1); ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>" class="w-8 h-8 rounded flex items-center justify-center <?php echo $page_number > 1 ? 'bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors' : 'bg-gray-200 text-gray-400 cursor-not-allowed'; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>

                                <?php
                                // Vis begrænset antal sider, hvis der er mange
                                $max_visible_pages = 5;
                                $start_page = max(1, min($page_number - floor($max_visible_pages / 2), $total_pages - $max_visible_pages + 1));
                                $end_page = min($start_page + $max_visible_pages - 1, $total_pages);

                                if ($start_page > 1):
                                ?>
                                    <a href="?page=1&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>" class="w-8 h-8 rounded flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors">
                                        1
                                    </a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="w-8 h-8 flex items-center justify-center text-gray-500">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>" class="w-8 h-8 rounded flex items-center justify-center <?php echo $i == $page_number ? 'bg-primary text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="w-8 h-8 flex items-center justify-center text-gray-500">...</span>
                                    <?php endif; ?>
                                    <a href="?page=<?php echo $total_pages; ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>" class="w-8 h-8 rounded flex items-center justify-center bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors">
                                        <?php echo $total_pages; ?>
                                    </a>
                                <?php endif; ?>

                                <a href="?page=<?php echo min($total_pages, $page_number + 1); ?>&search=<?php echo isset($_GET['search']) ? urlencode($_GET['search']) : ''; ?>" class="w-8 h-8 rounded flex items-center justify-center <?php echo $page_number < $total_pages ? 'bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors' : 'bg-gray-200 text-gray-400 cursor-not-allowed'; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Search Results (hidden by default) -->
                <div id="search-results" class="hidden">
                    <!-- List view search results -->
                    <div id="results-list-view" class="hidden bg-white rounded-xl shadow overflow-hidden mb-6">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b">
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Navn</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Profession</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Email</th>
                                    <th class="px-4 py-3 text-left text-sm font-medium text-gray-500">Telefon</th>
                                    <th class="px-4 py-3 text-center text-sm font-medium text-gray-500"><i class="fas fa-eye"></i></th>
                                </tr>
                            </thead>
                            <tbody id="results-table-body">
                                <!-- Search results will be loaded here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Grid view search results -->
                    <div id="results-grid-view" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                        <!-- Search results will be loaded here -->
                    </div>

                    <!-- Message when no search results -->
                    <div id="no-results" class="hidden bg-white rounded-xl shadow p-6 text-center">
                        <p class="text-gray-500">Ingen medarbejdere matcher din søgning.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Employee Details Modal -->
    <div id="employee-details-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto scale-95 transition-transform duration-300">
            <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary p-2 rounded-lg">
                        <i class="fas fa-user text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-xl" id="modal-title">Medarbejderdetaljer</h3>
                </div>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6" id="modal-content">
                <!-- Employee details will be loaded here -->
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md scale-95 transition-transform duration-300 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Bekræft sletning</h3>
            <p class="text-gray-600 mb-6">Er du sikker på, at du vil slette denne medarbejder? Denne handling kan ikke fortrydes.</p>
            <div class="flex justify-end gap-3">
                <button id="cancel-delete" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    Annuller
                </button>
                <form id="delete-form" action="delete-employee.php" method="POST">
                    <input type="hidden" id="delete-employee-id" name="employee_id" value="">
                    <button type="submit" class="bg-danger hover:bg-danger/90 text-white px-4 py-2 rounded-lg transition-colors">
                        Slet medarbejder
                    </button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // View toggle functionality
        const viewGridBtn = document.getElementById('view-grid');
        const viewListBtn = document.getElementById('view-list');
        const employeesGrid = document.getElementById('employees-grid');
        const employeesList = document.getElementById('employees-list');
        const searchResults = document.getElementById('search-results');
        const resultsGridView = document.getElementById('results-grid-view');
        const resultsListView = document.getElementById('results-list-view');
        const paginationContainer = document.getElementById('pagination-container');
        const noResultsMessage = document.getElementById('no-results');

        // Global variabel til at holde styr på visningstype (grid eller list)
        let currentView = 'grid';

        // Funktion til at generere initialer med korrekt håndtering af danske tegn
        function generateInitials(name) {
            if (!name || name.trim() === '') return 'U';

            // Rens navnet
            name = name.trim().replace(/\s+/g, ' ');

            const nameParts = name.split(' ');

            if (nameParts.length >= 2) {
                // Tag første bogstav fra første og sidste navn
                const firstInitial = nameParts[0].charAt(0);
                const lastInitial = nameParts[nameParts.length - 1].charAt(0);
                return (firstInitial + lastInitial).toUpperCase();
            } else {
                // Hvis kun ét navn, tag de første to bogstaver
                return name.substring(0, Math.min(2, name.length)).toUpperCase();
            }
        }

        viewGridBtn.addEventListener('click', () => {
            currentView = 'grid';

            if (isSearchActive()) {
                resultsGridView.classList.remove('hidden');
                resultsListView.classList.add('hidden');
            } else {
                employeesGrid.classList.remove('hidden');
                employeesList.classList.add('hidden');
            }

            viewGridBtn.classList.add('bg-primary', 'text-white');
            viewGridBtn.classList.remove('hover:bg-gray-100');

            viewListBtn.classList.remove('bg-primary', 'text-white');
            viewListBtn.classList.add('hover:bg-gray-100');
        });

        viewListBtn.addEventListener('click', () => {
            currentView = 'list';

            if (isSearchActive()) {
                resultsGridView.classList.add('hidden');
                resultsListView.classList.remove('hidden');
            } else {
                employeesGrid.classList.add('hidden');
                employeesList.classList.remove('hidden');
            }

            viewListBtn.classList.add('bg-primary', 'text-white');
            viewListBtn.classList.remove('hover:bg-gray-100');

            viewGridBtn.classList.remove('bg-primary', 'text-white');
            viewGridBtn.classList.add('hover:bg-gray-100');
        });

        // Dropdown menu functionality
        function toggleDropdown(employeeId) {
            const targetDropdown = document.getElementById(`dropdown-${employeeId}`);
            if (!targetDropdown) return;

            // Luk alle andre dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                if (dropdown.id !== `dropdown-${employeeId}`) {
                    dropdown.classList.add('hidden');
                }
            });

            // Toggle den valgte dropdown
            targetDropdown.classList.toggle('hidden');

            // Forhindre andre event handlers i at blive udløst
            event.stopPropagation();
        }

        // Skjul dropdowns når der klikkes udenfor
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.dropdown-toggle') && !e.target.closest('.dropdown-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
                    dropdown.classList.add('hidden');
                });
            }
        });

        // Live søgning
        const liveSearch = document.getElementById('live-search');
        const resultsTableBody = document.getElementById('results-table-body');

        function isSearchActive() {
            return !searchResults.classList.contains('hidden');
        }

        // Søgefunktion
        function performSearch() {
            const query = liveSearch.value.trim().toLowerCase();

            if (query === '') {
                // Ingen søgning, vis standardindhold
                searchResults.classList.add('hidden');
                paginationContainer.classList.remove('hidden');

                if (currentView === 'grid') {
                    employeesGrid.classList.remove('hidden');
                    employeesList.classList.add('hidden');
                } else {
                    employeesGrid.classList.add('hidden');
                    employeesList.classList.remove('hidden');
                }
                return;
            }

            // Aktiver søgeresultater
            searchResults.classList.remove('hidden');
            paginationContainer.classList.add('hidden');
            employeesGrid.classList.add('hidden');
            employeesList.classList.add('hidden');

            // Ryd tidligere resultater
            resultsGridView.innerHTML = '';
            resultsTableBody.innerHTML = '';

            // Hent alle medarbejdere
            const allEmployeeCards = document.querySelectorAll('.employee-card');

            // Hold styr på hvilke medarbejder-IDs der allerede er tilføjet
            const addedEmployeeIds = new Set();

            // Filtrer medarbejdere
            let matchFound = false;

            allEmployeeCards.forEach(card => {
                const cardId = card.getAttribute('data-id');

                // Spring over hvis denne medarbejder allerede er tilføjet
                if (addedEmployeeIds.has(cardId)) return;

                const name = card.getAttribute('data-name').toLowerCase();
                const profesion = card.getAttribute('data-profesion').toLowerCase();
                const email = card.getAttribute('data-email').toLowerCase();
                const phone = card.getAttribute('data-phone').toLowerCase();

                // Tjek om medarbejderen matcher søgningen
                const matchesSearch = query === '' ||
                    name.includes(query) ||
                    profesion.includes(query) ||
                    email.includes(query) ||
                    phone.includes(query);

                if (matchesSearch) {
                    matchFound = true;
                    // Tilføj ID til set'et for at undgå dubletter
                    addedEmployeeIds.add(cardId);

                    // Tilføj til grid visning
                    const gridItem = createSearchResultGridItem(cardId, name, profesion, email, phone);
                    resultsGridView.appendChild(gridItem);

                    // Tilføj til liste visning
                    const listItem = createSearchResultListItem(cardId, name, profesion, email, phone);
                    resultsTableBody.appendChild(listItem);
                }
            });

            // Vis "Ingen resultater" hvis ingen resultater blev fundet
            if (!matchFound) {
                noResultsMessage.classList.remove('hidden');
                resultsGridView.classList.add('hidden');
                resultsListView.classList.add('hidden');
            } else {
                noResultsMessage.classList.add('hidden');

                // Vis den aktive visning
                if (currentView === 'grid') {
                    resultsGridView.classList.remove('hidden');
                    resultsListView.classList.add('hidden');
                } else {
                    resultsGridView.classList.add('hidden');
                    resultsListView.classList.remove('hidden');
                }
            }
        }

        // Opret grid element til søgeresultater
        function createSearchResultGridItem(id, name, profesion, email, phone) {
            // Generer initialer med korrekt håndtering
            const initials = generateInitials(name);

            // Vælg farve baseret på ID
            const colors = ['primary', 'secondary', 'accent'];
            const color = colors[id % colors.length];

            // Opret element
            const div = document.createElement('div');
            div.className = 'bg-white rounded-xl shadow overflow-hidden employee-card';
            div.setAttribute('data-id', id);

            div.innerHTML = `
       <div class="flex justify-between items-center p-4 border-b border-gray-100">
           <div class="flex items-center gap-3">
               <div class="w-12 h-12 rounded-full bg-${color} text-white flex items-center justify-center text-lg font-medium">
                   ${initials}
               </div>
               <div>
                   <h3 class="font-bold text-gray-800">${name}</h3>
                   <p class="text-sm text-gray-500">${profesion}</p>
               </div>
           </div>
           <div class="relative employee-dropdown">
               <button class="text-gray-500 hover:text-primary transition-colors p-1 dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown('${id}-search')">
                   <i class="fas fa-ellipsis-v"></i>
               </button>
               <div id="dropdown-${id}-search" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10 dropdown-menu">
                   <div class="py-1">
                       <a href="edit-employee.php?id=${id}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                           <i class="fas fa-pencil-alt mr-2"></i> Rediger
                       </a>
                       <a href="mailto:${email}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                           <i class="fas fa-envelope mr-2"></i> Send besked
                       </a>
                       <button onclick="event.stopPropagation(); confirmDelete(${id})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-danger transition-colors">
                           <i class="fas fa-trash mr-2"></i> Slet
                       </button>
                   </div>
               </div>
           </div>
       </div>
       <div class="p-4">
           <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
               <div>
                   <p class="text-xs text-gray-500">Email</p>
                   <p class="text-sm truncate">${email}</p>
               </div>
               <div>
                   <p class="text-xs text-gray-500">Telefon</p>
                   <p class="text-sm">${phone}</p>
               </div>
               <div>
                   <p class="text-xs text-gray-500">Profession</p>
                   <p class="text-sm">${profesion}</p>
               </div>
           </div>
           <div class="flex justify-end mt-2">
               <button class="text-primary hover:text-primary/80 transition-colors text-sm font-medium" onclick="showEmployeeDetails(${id})">
                   Se oplysninger
               </button>
           </div>
       </div>
   `;

            return div;
        }

        // Opret liste element til søgeresultater
        function createSearchResultListItem(id, name, profesion, email, phone) {
            // Generer initialer med korrekt håndtering
            const initials = generateInitials(name);

            // Vælg farve baseret på ID
            const colors = ['primary', 'secondary', 'accent'];
            const color = colors[id % colors.length];

            // Opret element
            const tr = document.createElement('tr');
            tr.className = 'border-b hover:bg-gray-50 employee-card';
            tr.setAttribute('data-id', id);

            tr.innerHTML = `
       <td class="px-4 py-3">
           <div class="flex items-center gap-2">
               <div class="w-8 h-8 rounded-full bg-${color} text-white flex items-center justify-center text-sm font-medium">
                   ${initials}
               </div>
               <span class="font-medium">${name}</span>
           </div>
       </td>
       <td class="px-4 py-3 text-sm">${profesion}</td>
       <td class="px-4 py-3 text-sm truncate max-w-[200px]">${email}</td>
       <td class="px-4 py-3 text-sm">${phone}</td>
       <td class="px-4 py-3">
           <div class="flex justify-center gap-2">
               <button class="text-gray-500 hover:text-primary transition-colors p-1" title="Se oplysninger" onclick="showEmployeeDetails(${id})">
                   <i class="fas fa-eye"></i>
               </button>
               <div class="relative employee-dropdown">
                   <button class="text-gray-500 hover:text-primary transition-colors p-1 dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown('${id}-list-search')">
                       <i class="fas fa-ellipsis-v"></i>
                   </button>
                   <div id="dropdown-${id}-list-search" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden z-10 dropdown-menu">
                       <div class="py-1">
                           <a href="edit-employee.php?id=${id}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                               <i class="fas fa-pencil-alt mr-2"></i> Rediger
                           </a>
                           <a href="mailto:${email}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-primary transition-colors">
                               <i class="fas fa-envelope mr-2"></i> Send besked
                           </a>
                           <button onclick="event.stopPropagation(); confirmDelete(${id})" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-danger transition-colors">
                               <i class="fas fa-trash mr-2"></i> Slet
                           </button>
                       </div>
                   </div>
               </div>
           </div>
       </td>
   `;

            return tr;
        }

        // Tilføj event listeners til søgning
        liveSearch.addEventListener('input', performSearch);

        // Employee details modal
        const modal = document.getElementById('employee-details-modal');
        const modalContainer = modal.querySelector('.bg-white');
        const closeModalBtn = document.getElementById('close-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalContent = document.getElementById('modal-content');

        function showEmployeeDetails(employeeId) {
            // Vis loading indikator
            modalContent.innerHTML = `
                <div class="flex justify-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-primary"></div>
                </div>
            `;

            // Vis modal med animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modalContainer.classList.remove('scale-95');
                modalContainer.classList.add('scale-100');
            }, 10);
            document.body.classList.add('overflow-hidden');

            // Hent medarbejderdata via AJAX
            fetch(`get-employee-details.php?id=${employeeId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Netværksfejl ved hentning af data');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const employee = data.employee;

                        // Opdater modal titel
                        modalTitle.textContent = employee.first_name + ' ' + employee.last_name;

                        // Generer initialer og vælg en farve
                        const initials = (employee.first_name.charAt(0) + employee.last_name.charAt(0)).toUpperCase();
                        const colors = ['primary', 'secondary', 'accent'];
                        const color = colors[employee.id % colors.length];

                        // Opdater modal indhold
                        modalContent.innerHTML = `
                            <div class="flex flex-col sm:flex-row gap-6">
                                <div class="sm:w-1/3 flex flex-col items-center">
                                    ${employee.profile_image 
                                        ? `<div class="w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200">
                                            <img src="${employee.profile_image}" alt="${employee.first_name} ${employee.last_name}" class="w-full h-full object-cover">
                                          </div>`
                                        : `<div class="w-32 h-32 rounded-full bg-${color} text-white flex items-center justify-center text-4xl font-medium">
                                            ${initials}
                                          </div>`
                                    }
                                    <h3 class="text-xl font-bold mt-4 text-center">${employee.first_name} ${employee.last_name}</h3>
                                    <p class="text-gray-500 text-center">${employee.profesion}</p>
                                </div>
                                
                                <div class="sm:w-2/3">
                                    <div class="grid grid-cols-1 gap-4 mb-6">
                                        <div>
                                            <h4 class="font-semibold text-gray-700 mb-4">Kontaktoplysninger</h4>
                                            <div class="space-y-2">
                                                <div>
                                                    <p class="text-xs text-gray-500">Email</p>
                                                    <p>${employee.email}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-gray-500">Telefon</p>
                                                    <p>${employee.phone}</p>
                                                </div>
                                                <div class="mt-4">
                                                    <p class="text-xs text-gray-500">Profession</p>
                                                    <p>${employee.profesion}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4 mt-4 flex justify-end gap-2">
                                <a href="mailto:${employee.email}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                                    <i class="fas fa-envelope"></i>
                                    <span>Send email</span>
                                </a>
                                <a href="edit-employee.php?id=${employee.id}" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                                    <i class="fas fa-pencil-alt"></i>
                                    <span>Rediger</span>
                                </a>
                            </div>
                        `;
                    } else {
                        // Vis fejlbesked
                        modalContent.innerHTML = `
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <p class="text-red-500">Der opstod en fejl: ${data.message || 'Kunne ikke hente medarbejderdata'}</p>
                            </div>
                            <div class="flex justify-end mt-4">
                                <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors" onclick="closeModal()">
                                    Luk
                                </button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching employee:', error);
                    modalContent.innerHTML = `
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <p class="text-red-500">Der opstod en fejl ved hentning af medarbejderdata: ${error.message}</p>
                            <p class="text-sm text-red-400 mt-2">Kontroller, at filen get-employee-details.php eksisterer og fungerer korrekt.</p>
                        </div>
                        <div class="flex justify-end mt-4">
                            <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors" onclick="closeModal()">
                                Luk
                            </button>
                        </div>
                    `;
                });
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

        // Delete confirmation
        const deleteModal = document.getElementById('delete-confirm-modal');
        const deleteModalContainer = deleteModal.querySelector('.bg-white');
        const cancelDeleteBtn = document.getElementById('cancel-delete');
        const deleteEmployeeIdField = document.getElementById('delete-employee-id');

        function confirmDelete(employeeId) {
            deleteEmployeeIdField.value = employeeId;

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