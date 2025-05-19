<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Aktiver fejlrapportering for at diagnosticere problemer
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Sæt header til JSON respons
header('Content-Type: application/json');

// Standard respons
$response = [
    'success' => false,
    'message' => 'Der opstod en fejl',
    'html' => '',
    'mobileHtml' => '',
    'debug' => []
];

// Logfunktion - skriv alle vigtige begivenheder
function logDebug($message, $data = null) {
    $logEntry = date('Y-m-d H:i:s') . ' - ' . $message;
    if ($data !== null) {
        $logEntry .= ' - ' . json_encode($data);
    }
    error_log($logEntry);
    return $logEntry;
}

logDebug("Request received for get_conversation.php: " . json_encode($_GET));

// Tjek login
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Du skal være logget ind';
    echo json_encode($response);
    exit;
}

// Tjek parametre
if (!isset($_GET['user_id']) || !isset($_GET['user_type'])) {
    $response['message'] = 'Manglende parametre';
    echo json_encode($response);
    exit;
}

require_once '../database/db_conn.php';

$user_id = (int)$_GET['user_id'];
$user_type = $_GET['user_type'];
$current_user_id = $_SESSION['user_id'];

if ($user_id <= 0 || !in_array($user_type, ['staff', 'resident'])) {
    $response['message'] = 'Ugyldige parametre';
    echo json_encode($response);
    exit;
}

try {
    // Log vigtige variabler
    logDebug("Processing conversation", [
        'user_id' => $user_id,
        'user_type' => $user_type,
        'current_user_id' => $current_user_id
    ]);

    // 1. Hent samtalepartner info
    $partner = null;
    
    if ($user_type === 'staff') {
        $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $partner = $result->fetch_assoc();
        
        if ($partner) {
            $partner['type'] = 'staff';
            $partner['additional'] = $partner['role'];
        }
    } else {
        $stmt = $conn->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, room_number FROM residents WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $partner = $result->fetch_assoc();
        
        if ($partner) {
            $partner['type'] = 'resident';
            $partner['additional'] = 'Værelse ' . $partner['room_number'];
        }
    }
    
    if (!$partner) {
        logDebug("Partner not found", ['user_id' => $user_id, 'user_type' => $user_type]);
        $response['message'] = 'Kunne ikke finde samtalepartner';
        echo json_encode($response);
        exit;
    }
    
    logDebug("Partner found", $partner);
    
    // 2. Hent ALLE beskeder mellem de to parter - med to separate queries for klarhed
    $messages = [];
    
    // 2.1 Beskeder: Partner til Mig
    $incomingSQL = "
        SELECT 
            m.*,
            'incoming' as direction,
            CASE 
                WHEN m.sender_type = 'staff' THEN u.name
                ELSE CONCAT(r.first_name, ' ', r.last_name)
            END as sender_name
        FROM 
            messages m
            LEFT JOIN users u ON m.sender_type = 'staff' AND m.sender_id = u.id
            LEFT JOIN residents r ON m.sender_type = 'resident' AND m.sender_id = r.id
        WHERE 
            m.sender_id = ? AND 
            m.sender_type = ? AND 
            m.recipient_id = ? AND 
            m.recipient_type = 'staff'
    ";
    
    logDebug("Incoming SQL", $incomingSQL);
    
    $stmt = $conn->prepare($incomingSQL);
    $stmt->bind_param("isi", $user_id, $user_type, $current_user_id);
    $stmt->execute();
    $incomingResult = $stmt->get_result();
    
    $incomingCount = 0;
    while ($row = $incomingResult->fetch_assoc()) {
        $incomingCount++;
        $messages[] = $row;
    }
    
    logDebug("Incoming messages found", $incomingCount);
    
    // 2.2 Beskeder: Mig til Partner
    $outgoingSQL = "
        SELECT 
            m.*,
            'outgoing' as direction,
            CASE 
                WHEN m.sender_type = 'staff' THEN u.name
                ELSE CONCAT(r.first_name, ' ', r.last_name)
            END as sender_name
        FROM 
            messages m
            LEFT JOIN users u ON m.sender_type = 'staff' AND m.sender_id = u.id
            LEFT JOIN residents r ON m.sender_type = 'resident' AND m.sender_id = r.id
        WHERE 
            m.sender_id = ? AND 
            m.sender_type = 'staff' AND 
            m.recipient_id = ? AND 
            m.recipient_type = ?
    ";
    
    logDebug("Outgoing SQL", $outgoingSQL);
    
    $stmt = $conn->prepare($outgoingSQL);
    $stmt->bind_param("iis", $current_user_id, $user_id, $user_type);
    $stmt->execute();
    $outgoingResult = $stmt->get_result();
    
    $outgoingCount = 0;
    while ($row = $outgoingResult->fetch_assoc()) {
        $outgoingCount++;
        $messages[] = $row;
    }
    
    logDebug("Outgoing messages found", $outgoingCount);
    
    // 2.3 Sortér alle beskeder efter tidspunkt
    usort($messages, function($a, $b) {
        return strtotime($a['created_at']) - strtotime($b['created_at']);
    });
    
    logDebug("Total messages (after sorting)", count($messages));
    
    // 3. Marker beskeder som læst
    $stmt = $conn->prepare("
        UPDATE messages
        SET read_at = NOW()
        WHERE 
            recipient_id = ? 
            AND recipient_type = 'staff' 
            AND sender_id = ?
            AND sender_type = ?
            AND read_at IS NULL
    ");
    
    $stmt->bind_param("iis", $current_user_id, $user_id, $user_type);
    $stmt->execute();
    $updatedRows = $stmt->affected_rows;
    
    logDebug("Messages marked as read", $updatedRows);
    
    // 4. Behandl og dekrypter beskeder
    $processedMessages = [];
    foreach ($messages as $index => $message) {
        // Dekrypter beskeden hvis den er krypteret
        if (isset($message['is_encrypted']) && $message['is_encrypted'] == 1) {
            $message['original_content'] = $message['content']; // Gem for debugging
            
            try {
                $message['content'] = decryptMessage($message['content'], $message['encryption_iv']);
            } catch (Exception $e) {
                logDebug("Decryption error for message ID: " . $message['id'], $e->getMessage());
                $message['content'] = "[Kunne ikke dekryptere besked]";
            }
        }
        
        // Håndter null eller tomme beskeder
        if (empty($message['content'])) {
            if (!empty($message['original_length']) && $message['original_length'] > 0) {
                $message['content'] = "[Krypteret besked]";
            } else {
                $message['content'] = "[Tom besked]";
            }
        }
        
        $processedMessages[] = $message;
    }
    
    logDebug("Messages processed for decryption", count($processedMessages));
    
    // 5. Generer HTML-output
    $html = generateConversationHTML($partner, $processedMessages, $current_user_id);
    $mobileHtml = generateMobileConversationHTML($partner, $processedMessages, $current_user_id);
    
    // 6. Byg succesfuld respons
    $response['success'] = true;
    $response['message'] = 'Samtale hentet';
    $response['html'] = $html;
    $response['mobileHtml'] = $mobileHtml;
    $response['debug'] = [
        'partner' => $partner,
        'incomingCount' => $incomingCount,
        'outgoingCount' => $outgoingCount,
        'totalMessages' => count($processedMessages)
    ];
    
} catch (Exception $e) {
    logDebug("Error in get_conversation.php", $e->getMessage());
    $response['message'] = 'Fejl: ' . $e->getMessage();
    $response['debug']['error'] = $e->getMessage();
    $response['debug']['trace'] = $e->getTraceAsString();
}

echo json_encode($response);
exit;

// Sikker dekrypteringsmetode
function decryptMessage($encryptedContent, $encryptionIv) {
    // Hvis ingen IV eller indhold, kan vi ikke dekryptere
    if (empty($encryptionIv) || empty($encryptedContent)) {
        return "[Krypteret besked]";
    }
    
    try {
        // Krypteringsnøgle - den samme som i encryptMessage
        $key = "Mercantec2025KollegieHemmeligKrypteringsNogle";
        
        // Konverter IV tilbage fra hex til binær
        $iv = hex2bin($encryptionIv);
        
        // Dekrypter med OpenSSL
        $decrypted = openssl_decrypt(
            $encryptedContent,      // Krypteret indhold
            'AES-256-CBC',          // Krypteringsalgoritme
            $key,                   // Krypteringsnøgle
            0,                      // Optioner
            $iv                     // Initialization Vector
        );
        
        // Tjek om dekryptering lykkedes
        if ($decrypted === false) {
            error_log("Dekryptering fejlede for besked");
            return "[Besked kunne ikke dekrypteres]";
        }
        
        return $decrypted;
    } catch (Exception $e) {
        error_log("Dekrypteringsfejl: " . $e->getMessage());
        return "[Fejl ved dekryptering]";
    }
}

// Generer desktop HTML med forbedret metode
function generateConversationHTML($partner, $messages, $currentUserId) {
    ob_start(); // Start output buffering
    ?>
    <div class="flex flex-col h-full">
        <!-- Partner info header -->
        <div class="bg-white p-4 border-b border-gray-200 flex items-center gap-3">
            <!-- Avatar -->
            <?php 
            $bgColor = $partner['type'] === 'staff' ? 'bg-blue-600' : 'bg-gray-500';
            $initials = generateInitials($partner['name']);
            ?>
            <div class="w-10 h-10 rounded-full <?= $bgColor ?> flex items-center justify-center text-white font-medium">
                <?= $initials ?>
            </div>
            
            <!-- Info -->
            <div>
                <h3 class="font-semibold text-gray-900"><?= htmlspecialchars($partner['name']) ?></h3>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($partner['additional']) ?></p>
            </div>
        </div>
        
        <!-- Messages container -->
        <div id="message-container" class="flex-grow p-4 overflow-y-auto bg-gray-50 space-y-4">
            <?php if (empty($messages)): ?>
                <div class="flex items-center justify-center h-full">
                    <div class="text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <p class="text-gray-500">Ingen beskeder endnu. Send en besked for at starte samtalen.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php 
                $lastDate = null;
                
                foreach ($messages as $index => $message): 
                    $date = date('Y-m-d', strtotime($message['created_at']));
                    
                    // Dato-header hvis ny dag
                    if ($lastDate !== $date): 
                        $today = date('Y-m-d');
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        
                        $dateLabel = '';
                        if ($date === $today) {
                            $dateLabel = 'I dag';
                        } elseif ($date === $yesterday) {
                            $dateLabel = 'I går';
                        } else {
                            $dateLabel = danskDato($date);
                        }
                ?>
                    <div class="flex justify-center py-2">
                        <span class="px-3 py-1 bg-gray-200 rounded-full text-xs text-gray-600">
                            <?= $dateLabel ?>
                        </span>
                    </div>
                <?php 
                        $lastDate = $date;
                    endif; 
                
                    // Beskedboble
                    $isCurrentUser = ($message['sender_type'] === 'staff' && $message['sender_id'] == $currentUserId);
                    $alignment = $isCurrentUser ? 'text-right' : 'text-left';
                    $bubbleColor = $isCurrentUser ? 'bg-blue-500 text-white' : 'bg-white text-gray-800';
                    $bubbleShadow = $isCurrentUser ? '' : 'shadow-sm';
                    
                    // Tilføj class og data-attributter til egne beskeder for klik-funktionalitet
                    $messageClass = $isCurrentUser ? 'own-message cursor-pointer hover:opacity-90' : '';
                    $messageDataAttr = $isCurrentUser ? 'data-message-id="' . $message['id'] . '"' : '';
                    
                    // Debug info i kommentarer
                    $debugInfo = "MSG #" . ($index + 1) . 
                                 " ID:" . $message['id'] . 
                                 " Dir:" . ($isCurrentUser ? "OUT" : "IN");
                ?>
                    <!-- <?= $debugInfo ?> -->
                    <div class="<?= $alignment ?> mb-3">
                        <div class="inline-block max-w-[75%] <?= $bubbleColor ?> <?= $bubbleShadow ?> rounded-lg p-3 <?= $messageClass ?>" <?= $messageDataAttr ?>>
                            <?= nl2br(htmlspecialchars($message['content'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Input area -->
        <div class="bg-white border-t border-gray-200 p-3">
            <form id="message-form" class="flex gap-2">
                <input type="hidden" name="recipient_id" value="<?= $partner['id'] ?>">
                <input type="hidden" name="recipient_type" value="<?= strtolower($partner['type']) ?>">
                
                <textarea id="message-input" name="message" placeholder="Skriv en besked..." class="flex-grow p-2 border border-gray-300 rounded-lg resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" rows="1"></textarea>
                
                <button type="submit" class="bg-blue-500 text-white rounded-lg px-4 py-2 flex items-center justify-center hover:bg-blue-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                    </svg>
                </button>
            </form>
        </div>
    </div>
    <?php
    return ob_get_clean(); // Return captured output
}

// Generer mobil HTML med forbedret metode
function generateMobileConversationHTML($partner, $messages, $currentUserId) {
    ob_start(); // Start output buffering
    
    if (empty($messages)): ?>
        <div class="flex items-center justify-center h-full">
            <div class="text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
                <p class="text-gray-500">Ingen beskeder endnu.<br>Send en besked for at starte samtalen.</p>
            </div>
        </div>
    <?php else:
        $lastDate = null;
        
        foreach ($messages as $index => $message): 
            $date = date('Y-m-d', strtotime($message['created_at']));
            
            // Dato-header hvis ny dag
            if ($lastDate !== $date): 
                $today = date('Y-m-d');
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                
                $dateLabel = '';
                if ($date === $today) {
                    $dateLabel = 'I dag';
                } elseif ($date === $yesterday) {
                    $dateLabel = 'I går';
                } else {
                    $dateLabel = danskDato($date);
                }
        ?>
            <div class="flex justify-center py-2">
                <span class="px-3 py-1 bg-gray-200 rounded-full text-xs text-gray-600">
                    <?= $dateLabel ?>
                </span>
            </div>
        <?php 
                $lastDate = $date;
            endif; 
        
            // Beskedboble
            $isCurrentUser = ($message['sender_type'] === 'staff' && $message['sender_id'] == $currentUserId);
            $alignment = $isCurrentUser ? 'text-right' : 'text-left';
            $bubbleColor = $isCurrentUser ? 'bg-blue-500 text-white' : 'bg-white text-gray-800';
            $bubbleShadow = $isCurrentUser ? '' : 'shadow-sm';
            
            // Tilføj class og data-attributter til egne beskeder for klik-funktionalitet
            $messageClass = $isCurrentUser ? 'own-message cursor-pointer hover:opacity-90' : '';
            $messageDataAttr = $isCurrentUser ? 'data-message-id="' . $message['id'] . '"' : '';
            
            // Debug info i kommentarer
            $debugInfo = "MSG #" . ($index + 1) . 
                         " ID:" . $message['id'] . 
                         " Dir:" . ($isCurrentUser ? "OUT" : "IN");
        ?>
            <!-- <?= $debugInfo ?> -->
            <div class="<?= $alignment ?> mb-4">
                <div class="inline-block max-w-[75%] <?= $bubbleColor ?> <?= $bubbleShadow ?> rounded-lg p-3 <?= $messageClass ?>" <?= $messageDataAttr ?>>
                    <?= nl2br(htmlspecialchars($message['content'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif;
    
    return ob_get_clean(); // Return captured output
}

// Hjælpefunktioner
function generateInitials($name) {
    $nameParts = explode(' ', $name);
    
    if (count($nameParts) >= 2) {
        return mb_strtoupper(mb_substr($nameParts[0], 0, 1, 'UTF-8') . mb_substr($nameParts[count($nameParts) - 1], 0, 1, 'UTF-8'), 'UTF-8');
    } else {
        return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
    }
}

// Forbedret funktion til at vise danske datoer
function danskDato($date) {
    $timestamp = strtotime($date);
    
    // Array med måneder på dansk
    $danske_maaneder = [
        '01' => 'Jan',
        '02' => 'Feb',
        '03' => 'Mar',
        '04' => 'Apr',
        '05' => 'Maj',
        '06' => 'Jun',
        '07' => 'Jul',
        '08' => 'Aug',
        '09' => 'Sep',
        '10' => 'Okt',
        '11' => 'Nov',
        '12' => 'Dec'
    ];
    
    $dag = date('d', $timestamp);
    $maaned_nr = date('m', $timestamp);
    $aar = date('Y', $timestamp);
    
    return $dag . '. ' . $danske_maaneder[$maaned_nr] . ' ' . $aar;
}

function formatTime($timestamp) {
    return date('H:i', strtotime($timestamp));
}