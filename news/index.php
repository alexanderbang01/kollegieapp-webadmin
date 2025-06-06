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
include '../components/header.php';
include '../database/db_conn.php';

// Hent nyheder fra databasen
$news = [];
$featuredNews = null;

if (isset($conn)) {
    // Hent fremhævede nyheder
    $featured_query = "SELECT n.*, u.name AS author_name, u.username,
                      (SELECT COUNT(*) FROM news_reads WHERE news_id = n.id) AS read_count
                      FROM news n
                      LEFT JOIN users u ON n.created_by = u.id
                      WHERE n.is_featured = 1
                      ORDER BY n.published_at DESC
                      LIMIT 1";

    $featured_result = $conn->query($featured_query);
    if ($featured_result && $featured_result->num_rows > 0) {
        $featuredNews = $featured_result->fetch_assoc();
    }

    // Hent almindelige nyheder (ikke fremhævede)
    $page_number = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $items_per_page = 4;
    $offset = ($page_number - 1) * $items_per_page;

    $news_query = "SELECT n.*, u.name AS author_name, u.username,
                  (SELECT COUNT(*) FROM news_reads WHERE news_id = n.id) AS read_count
                  FROM news n
                  LEFT JOIN users u ON n.created_by = u.id
                  WHERE n.is_featured = 0
                  ORDER BY n.published_at DESC
                  LIMIT ?, ?";

    $stmt = $conn->prepare($news_query);
    $stmt->bind_param("ii", $offset, $items_per_page);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }

    // Hent samlet antal nyheder til pagination
    $count_query = "SELECT COUNT(*) as total FROM news WHERE is_featured = 0";
    $count_result = $conn->query($count_query);
    $total_news = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_news / $items_per_page);
}
?>

<body class="font-poppins bg-gray-100 min-h-screen flex flex-col">
    <div class="flex flex-grow">
        <?php include '../components/sidebar.php'; ?>

        <!-- Main content -->
        <main class="flex-grow">
            <!-- News content -->
            <div class="p-3 sm:p-6">
                <div class="mb-4 sm:mb-6 flex justify-between items-center">
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Nyheder</h1>
                        <p class="text-sm sm:text-base text-gray-600">Administrer kollegiets nyheder og meddelelser</p>
                    </div>
                    <a href="create-news.php" class="bg-primary hover:bg-primary/90 text-white px-3 py-2 sm:px-4 sm:py-2 rounded-lg text-sm sm:text-base transition-colors flex items-center gap-2">
                        <i class="fas fa-plus"></i>
                        <span>Opret nyhed</span>
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

                <!-- Search and filter -->
                <div class="bg-white rounded-xl shadow p-4 sm:p-6 mb-4 sm:mb-6 animate-fade-in">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <!-- Search field -->
                        <div class="relative sm:w-1/3 w-full">
                            <input type="text" id="search-input" placeholder="Søg i nyheder..." class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-9 focus:outline-none focus:ring-2 focus:ring-primary/50">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>

                        <!-- Layout controls -->
                        <div class="flex justify-end">
                            <div class="bg-white rounded-lg shadow p-2 flex gap-2">
                                <button id="layout-1" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100" title="1 nyhed pr. række">
                                    <i class="fas fa-list"></i>
                                </button>
                                <button id="layout-2" class="w-8 h-8 flex items-center justify-center rounded bg-primary text-white" title="2 nyheder pr. række">
                                    <i class="fas fa-th-large"></i>
                                </button>
                                <button id="layout-3" class="w-8 h-8 flex items-center justify-center rounded hover:bg-gray-100" title="Kompakt visning">
                                    <i class="fas fa-th"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search results -->
                <div id="search-results" class="hidden mb-6">
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="text-lg font-bold">Søgeresultater</h2>
                        <button id="clear-search" class="text-primary hover:text-primary/80 text-sm flex items-center gap-1">
                            <i class="fas fa-times"></i>
                            <span>Ryd søgning</span>
                        </button>
                    </div>
                    <div id="results-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search results will be loaded here dynamically -->
                    </div>
                </div>

                <!-- Featured news -->
                <div id="regular-content">
                    <?php if ($featuredNews): ?>
                        <div class="mb-6">
                            <h2 class="text-lg font-bold mb-3">Fremhævede nyheder</h2>
                            <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in">
                                <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-<?php echo isset($featuredNews['is_important']) && $featuredNews['is_important'] ? 'danger' : 'primary'; ?>/10 text-<?php echo isset($featuredNews['is_important']) && $featuredNews['is_important'] ? 'danger' : 'primary'; ?> p-2 rounded-lg">
                                            <i class="fas fa-<?php echo isset($featuredNews['is_important']) && $featuredNews['is_important'] ? 'exclamation-circle' : 'newspaper'; ?> text-lg"></i>
                                        </div>
                                        <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($featuredNews['title']); ?></h3>
                                    </div>
                                    <div class="flex gap-2">
                                        <a href="edit-news.php?id=<?php echo $featuredNews['id']; ?>" class="text-gray-400 hover:text-primary transition-colors" title="Rediger">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button class="text-gray-400 hover:text-danger transition-colors" onclick="confirmDelete(<?php echo $featuredNews['id']; ?>)" title="Slet">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="text-primary hover:text-primary/90 transition-colors text-xl" onclick="toggleFeatured(<?php echo $featuredNews['id']; ?>, 0)" title="Fjern fremhævning">
                                            <i class="fas fa-thumbtack"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <div class="flex justify-between mb-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                                <span class="font-medium text-xs"><?php echo strtoupper(substr($featuredNews['username'] ?? 'U', 0, 2)); ?></span>
                                            </div>
                                            <span class="font-medium"><?php echo htmlspecialchars($featuredNews['author_name'] ?? 'Ukendt'); ?></span>
                                        </div>
                                        <div class="text-gray-500 text-sm">
                                            <?php
                                            $date = new DateTime($featuredNews['published_at']);
                                            echo $date->format('j. F Y · H:i');
                                            ?>
                                        </div>
                                    </div>
                                    <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($featuredNews['content'])); ?></p>
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <?php if (isset($featuredNews['is_important']) && $featuredNews['is_important']): ?>
                                                <span class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full">Vigtig</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex items-center gap-1 text-gray-500 text-sm cursor-pointer hover:text-primary transition-colors" onclick="showReaders(<?php echo $featuredNews['id']; ?>)">
                                            <i class="far fa-eye"></i>
                                            <span><?php echo $featuredNews['read_count'] ?? 0; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- News list -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6" id="news-grid">
                        <?php if (empty($news)): ?>
                            <div class="col-span-2 bg-white rounded-xl shadow p-6 text-center">
                                <p class="text-gray-500">Ingen nyheder fundet.</p>
                                <a href="create-news.php" class="mt-2 inline-block text-primary hover:underline">Opret en ny nyhed</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($news as $index => $item): ?>
                                <!-- News item -->
                                <div class="bg-white rounded-xl shadow overflow-hidden animate-fade-in delay-<?php echo ($index % 4) * 100; ?> news-item"
                                    data-title="<?php echo htmlspecialchars($item['title']); ?>"
                                    data-content="<?php echo htmlspecialchars($item['content']); ?>"
                                    data-featured="<?php echo $item['is_featured'] ?? 0; ?>"
                                    data-id="<?php echo $item['id']; ?>">
                                    <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-<?php echo isset($item['is_important']) && $item['is_important'] ? 'danger' : 'primary'; ?>/10 text-<?php echo isset($item['is_important']) && $item['is_important'] ? 'danger' : 'primary'; ?> p-2 rounded-lg">
                                                <i class="fas fa-<?php echo isset($item['is_important']) && $item['is_important'] ? 'exclamation-circle' : 'newspaper'; ?> text-lg"></i>
                                            </div>
                                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($item['title']); ?></h3>
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="edit-news.php?id=<?php echo $item['id']; ?>" class="text-gray-400 hover:text-primary transition-colors" title="Rediger">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <button class="text-gray-400 hover:text-danger transition-colors" onclick="confirmDelete(<?php echo $item['id']; ?>)" title="Slet">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <button class="text-gray-400 hover:text-primary transition-colors text-xl" onclick="toggleFeatured(<?php echo $item['id']; ?>, 1)" title="Fremhæv denne nyhed">
                                                <i class="fa fa-thumbtack"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="p-4">
                                        <div class="flex justify-between mb-3">
                                            <div class="flex items-center gap-2">
                                                <div class="w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center">
                                                    <span class="font-medium text-xs"><?php echo strtoupper(substr($item['username'] ?? 'U', 0, 2)); ?></span>
                                                </div>
                                                <span class="font-medium"><?php echo htmlspecialchars($item['author_name'] ?? 'Ukendt'); ?></span>
                                            </div>
                                            <div class="text-gray-500 text-sm">
                                                <?php
                                                $date = new DateTime($item['published_at']);
                                                echo $date->format('j. F Y · H:i');
                                                ?>
                                            </div>
                                        </div>
                                        <p class="text-gray-700 mb-4 news-content">
                                            <?php
                                            // Vis en forkortet version af indholdet
                                            $content = htmlspecialchars($item['content']);
                                            echo strlen($content) > 200 ? nl2br(substr($content, 0, 200)) . '...' : nl2br($content);
                                            ?>
                                        </p>
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <?php if (isset($item['is_important']) && $item['is_important']): ?>
                                                    <span class="px-2 py-1 bg-danger/10 text-danger text-xs rounded-full">Vigtig</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex items-center gap-1 text-gray-500 text-sm cursor-pointer hover:text-primary transition-colors" onclick="showReaders(<?php echo $item['id']; ?>)">
                                                <i class="far fa-eye"></i>
                                                <span><?php echo $item['read_count'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if (!empty($news) && $total_pages > 1): ?>
                        <div class="flex justify-between items-center">
                            <div class="text-gray-500 text-sm">
                                Viser <?php echo $offset + 1; ?>-<?php echo min($offset + count($news), $total_news); ?> af <?php echo $total_news; ?> nyheder
                            </div>
                            <div class="flex gap-1">
                                <a href="?page=<?php echo max(1, $page_number - 1); ?>" class="w-8 h-8 rounded flex items-center justify-center <?php echo $page_number > 1 ? 'bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors' : 'bg-gray-200 text-gray-400 cursor-not-allowed'; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?php echo $i; ?>" class="w-8 h-8 rounded flex items-center justify-center <?php echo $i == $page_number ? 'bg-primary text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors'; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>

                                <a href="?page=<?php echo min($total_pages, $page_number + 1); ?>" class="w-8 h-8 rounded flex items-center justify-center <?php echo $page_number < $total_pages ? 'bg-gray-200 hover:bg-gray-300 text-gray-700 transition-colors' : 'bg-gray-200 text-gray-400 cursor-not-allowed'; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Readers Modal -->
    <div id="readers-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto scale-95 transition-transform duration-300">
            <div class="p-4 border-b border-gray-100 bg-primary/5 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <div class="bg-primary/10 text-primary p-2 rounded-lg">
                        <i class="far fa-eye text-lg"></i>
                    </div>
                    <h3 class="font-bold text-gray-800 text-xl" id="modal-title">Læst af</h3>
                </div>
                <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-4" id="modal-content">
                <!-- Readers list will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-confirm-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 hidden opacity-0 transition-opacity duration-300">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-md scale-95 transition-transform duration-300 p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Bekræft sletning</h3>
            <p class="text-gray-600 mb-6">Er du sikker på, at du vil slette denne nyhed? Denne handling kan ikke fortrydes.</p>
            <div class="flex justify-end gap-3">
                <button id="cancel-delete" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors">
                    Annuller
                </button>
                <form id="delete-form" action="delete-news.php" method="POST">
                    <input type="hidden" id="delete-news-id" name="news_id" value="">
                    <button type="submit" class="bg-danger hover:bg-danger/90 text-white px-4 py-2 rounded-lg transition-colors">
                        Slet nyhed
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Layout-skift funktionalitet
        const layout1Btn = document.getElementById('layout-1');
        const layout2Btn = document.getElementById('layout-2');
        const layout3Btn = document.getElementById('layout-3');
        const newsGrid = document.getElementById('news-grid');

        layout1Btn.addEventListener('click', () => {
            newsGrid.className = 'grid grid-cols-1 gap-4 mb-6';
            setActiveLayout(layout1Btn);
        });

        layout2Btn.addEventListener('click', () => {
            newsGrid.className = 'grid grid-cols-1 md:grid-cols-2 gap-4 mb-6';
            setActiveLayout(layout2Btn);
        });

        layout3Btn.addEventListener('click', () => {
            newsGrid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6';
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

        // Live søgefunktion
        const searchInput = document.getElementById('search-input');
        const searchResults = document.getElementById('search-results');
        const resultsContainer = document.getElementById('results-container');
        const regularContent = document.getElementById('regular-content');
        const clearSearchBtn = document.getElementById('clear-search');
        const newsItems = document.querySelectorAll('.news-item');

        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();

            if (query.length >= 2) {
                // Vis søgeresultater og skjul almindeligt indhold
                searchResults.classList.remove('hidden');
                regularContent.classList.add('hidden');

                // Ryd tidligere resultater
                resultsContainer.innerHTML = '';

                // Filtrer nyheder
                let matchCount = 0;

                // Lav et klon af hver matchende nyhed og tilføj til resultaterne
                newsItems.forEach(item => {
                    const title = item.getAttribute('data-title').toLowerCase();
                    const content = item.getAttribute('data-content').toLowerCase();
                    const isFeatured = item.hasAttribute('data-featured') ? item.getAttribute('data-featured') === '1' : false;
                    const newsId = item.getAttribute('data-id');

                    if (title.includes(query) || content.includes(query)) {
                        const clone = item.cloneNode(true);

                        // Find pin-knappen og opdater ikonet baseret på status
                        const pinButton = clone.querySelector('[onclick^="toggleFeatured"]');
                        if (pinButton) {
                            if (isFeatured) {
                                pinButton.className = "text-primary hover:text-primary/90 transition-colors text-xl";
                                pinButton.setAttribute("title", "Fjern fremhævning");
                                pinButton.querySelector('i').className = "fas fa-thumbtack"; // Solid ikon for pinned
                            } else {
                                pinButton.className = "text-gray-400 hover:text-primary transition-colors text-xl";
                                pinButton.setAttribute("title", "Fremhæv denne nyhed");
                                pinButton.querySelector('i').className = "fa fa-thumbtack"; // Regular ikon for unpinned
                            }
                        }

                        // Fremhæv søgeordet i titel og indhold
                        const titleEl = clone.querySelector('h3');
                        const contentEl = clone.querySelector('.news-content');

                        if (titleEl && title.includes(query)) {
                            const highlightedTitle = titleEl.textContent.replace(
                                new RegExp(query, 'gi'),
                                match => `<span class="bg-yellow-100">${match}</span>`
                            );
                            titleEl.innerHTML = highlightedTitle;
                        }

                        if (contentEl && content.includes(query)) {
                            // Find konteksten omkring søgeordet
                            const index = content.indexOf(query);
                            let start = Math.max(0, index - 50);
                            let end = Math.min(content.length, index + query.length + 50);

                            // Juster start og slut til at starte og slutte ved ordgrænser
                            while (start > 0 && content[start] !== ' ') start--;
                            while (end < content.length && content[end] !== ' ') end++;

                            let snippet = content.substring(start, end);
                            if (start > 0) snippet = '...' + snippet;
                            if (end < content.length) snippet = snippet + '...';

                            // Fremhæv søgeordet i snippeten
                            const highlightedSnippet = snippet.replace(
                                new RegExp(query, 'gi'),
                                match => `<span class="bg-yellow-100">${match}</span>`
                            );

                            contentEl.innerHTML = highlightedSnippet;
                        }

                        resultsContainer.appendChild(clone);
                        matchCount++;
                    }
                });

                // Vis besked hvis ingen resultater
                if (matchCount === 0) {
                    resultsContainer.innerHTML = `
                        <div class="col-span-2 bg-white rounded-xl shadow p-6 text-center">
                            <p class="text-gray-500">Ingen nyheder fundet, der matcher "${query}".</p>
                        </div>
                    `;
                }
            } else {
                // Vis almindeligt indhold og skjul søgeresultater
                searchResults.classList.add('hidden');
                regularContent.classList.remove('hidden');
            }
        });

        // Ryd søgning
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchResults.classList.add('hidden');
            regularContent.classList.remove('hidden');
        });

        // Readers modal
        const modal = document.getElementById('readers-modal');
        const modalContainer = modal.querySelector('.bg-white');
        const closeModalBtn = document.getElementById('close-modal');
        const modalTitle = document.getElementById('modal-title');
        const modalContent = document.getElementById('modal-content');

        function showReaders(newsId) {
            // AJAX-kald for at hente læserliste
            fetch(`get-readers.php?news_id=${newsId}`)
                .then(response => response.json())
                .then(data => {
                    // Opdater modal titel
                    modalTitle.textContent = "Læst af";

                    // Opdater modal indhold
                    if (data.readers.length > 0) {
                        modalContent.innerHTML = `
                            <div class="bg-gray-50 rounded-lg p-4 max-h-80 overflow-y-auto">
                                <table class="w-full">
                                    <thead class="border-b border-gray-200">
                                        <tr>
                                            <th class="text-left font-medium text-gray-500 pb-2">Navn</th>
                                            <th class="text-left font-medium text-gray-500 pb-2">Værelse</th>
                                            <th class="text-left font-medium text-gray-500 pb-2">Tidspunkt</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${data.readers.map(reader => `
                                            <tr class="border-b border-gray-100">
                                                <td class="py-2">${reader.name}</td>
                                                <td class="py-2">${reader.room}</td>
                                               <td class="py-2 text-gray-500 text-sm">${reader.time}</td>
                                           </tr>
                                       `).join('')}
                                   </tbody>
                               </table>
                           </div>
                       `;
                    } else {
                        modalContent.innerHTML = `
                           <div class="bg-gray-50 rounded-lg p-4 text-center">
                               <p class="text-gray-500">Ingen har læst denne nyhed endnu.</p>
                           </div>
                       `;
                    }

                    modalContent.innerHTML += `
                       <div class="flex justify-end mt-4">
                           <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors" onclick="closeModal()">
                               Luk
                           </button>
                       </div>
                   `;

                    // Vis modal med animation
                    showModal();
                })
                .catch(error => {
                    console.error('Error fetching readers:', error);
                    modalContent.innerHTML = `
                       <div class="bg-red-50 rounded-lg p-4 text-center">
                           <p class="text-red-500">Der opstod en fejl ved hentning af læserlisten.</p>
                       </div>
                       <div class="flex justify-end mt-4">
                           <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition-colors" onclick="closeModal()">
                               Luk
                           </button>
                       </div>
                   `;
                    showModal();
                });
        }

        function showModal() {
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

        // Delete confirmation
        const deleteModal = document.getElementById('delete-confirm-modal');
        const deleteModalContainer = deleteModal.querySelector('.bg-white');
        const cancelDeleteBtn = document.getElementById('cancel-delete');
        const deleteNewsIdField = document.getElementById('delete-news-id');

        function confirmDelete(newsId) {
            deleteNewsIdField.value = newsId;

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

        // Fremhævet nyhed toggle
        function toggleFeatured(newsId, isFeatured) {
            // Send AJAX-kald for at ændre fremhævet status
            fetch('toggle-featured.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `news_id=${newsId}&is_featured=${isFeatured}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Genindlæs siden for at vise ændringerne
                        window.location.reload();
                    } else {
                        alert('Der opstod en fejl: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Der opstod en fejl ved opdatering af nyheden.');
                });
        }

        // Gendan event listeners til søgeresultater
        document.addEventListener('click', function(e) {
            // Find den nærmeste .news-item-forælder
            const newsItem = e.target.closest('.news-item');

            if (newsItem) {
                // Find den knap, der blev klikket på
                const deleteBtn = e.target.closest('[onclick^="confirmDelete"]');
                const featuredBtn = e.target.closest('[onclick^="toggleFeatured"]');
                const readersBtn = e.target.closest('[onclick^="showReaders"]');

                if (deleteBtn) {
                    const match = deleteBtn.getAttribute('onclick').match(/confirmDelete\((\d+)\)/);
                    if (match && match[1]) {
                        confirmDelete(parseInt(match[1]));
                    }
                } else if (featuredBtn) {
                    const match = featuredBtn.getAttribute('onclick').match(/toggleFeatured\((\d+),\s*(\d+)\)/);
                    if (match && match[1] && match[2] !== undefined) {
                        toggleFeatured(parseInt(match[1]), parseInt(match[2]));
                    }
                } else if (readersBtn) {
                    const match = readersBtn.getAttribute('onclick').match(/showReaders\((\d+)\)/);
                    if (match && match[1]) {
                        showReaders(parseInt(match[1]));
                    }
                }
            }
        });
    </script>
</body>

</html>