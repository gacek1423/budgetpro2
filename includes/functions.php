<?php
// budgetpro2/includes/functions.php
// PEŁNY, POPRAWIONY KOD - GOTOWY DO UŻYCIA

// Bezpieczne ładowanie config
$config_path = __DIR__ . '/../config.php';
if (!file_exists($config_path)) {
    die("❌ CRITICAL: config.php not found at: $config_path");
}
require_once $config_path;

// ====================
// PREFERENCJE UŻYTKOWNIKA
// ====================
function getUserPreferences($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    
    if ($user_id > 0 && isset($_SESSION['user_prefs'])) {
        return $_SESSION['user_prefs'];
    }
    
    try {
        $db = db();
        $prefs = $db->selectOne("SELECT * FROM user_preferences WHERE user_id = ?", [$user_id]);
        if ($prefs) {
            $_SESSION['user_prefs'] = $prefs;
            return $prefs;
        }
    } catch (Exception $e) {
        if (DEBUG_MODE) error_log("Prefs error: " . $e->getMessage());
    }
    
    // Domyślne
    return [
        'currency_format' => 'pl',
        'date_format' => 'Y-m-d',
        'language' => 'pl',
        'theme' => 'light',
        'theme_color' => 'blue',
        'start_page' => 'dashboard.php',
        'email_notifications' => 1
    ];
}

// ====================
// FORMATOWANIE
// ====================
function formatCurrency($amount) {
    $prefs = getUserPreferences();
    $amount = (float)$amount;
    return number_format($amount, 2, ',', ' ') . ($prefs['currency_format'] === 'en' ? ' PLN' : ' zł');
}

function formatDate($dateString) {
    if (!$dateString) return '-';
    $prefs = getUserPreferences();
    $timestamp = strtotime($dateString);
    return $timestamp ? date($prefs['date_format'], $timestamp) : $dateString;
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function dd($data) {
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:10px;border-radius:5px;overflow:auto;">';
    print_r($data);
    echo '</pre>';
    die();
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map(__FUNCTION__, $data);
    }
    return htmlspecialchars(strip_tags(trim((string)$data)), ENT_QUOTES, 'UTF-8');
}

// ====================
// BEZPIECZEŃSTWO
// ====================
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ====================
// NOWE FUNKCJE BIZNESOWE
// ====================

/**
 * Pobiera statystyki biznesowe użytkownika
 */
function getBusinessStats($user_id) {
    return db()->transaction(function($db) use ($user_id) {
        return [
            'projects' => $db->count("SELECT COUNT(*) FROM business_projects WHERE user_id = ?", [$user_id]),
            'invoices' => $db->count("SELECT COUNT(*) FROM business_invoices WHERE user_id = ?", [$user_id]),
            'clients' => $db->count("SELECT COUNT(*) FROM business_clients WHERE user_id = ?", [$user_id]),
        ];
    });
}

/**
 * Pobiera aktywne cele finansowe z progresem
 */
function getActiveGoals($user_id) {
    $goals = db()->select("SELECT * FROM financial_goals WHERE user_id = ? AND is_active = 1 ORDER BY target_date ASC", [$user_id]);
    foreach ($goals as &$goal) {
        $progress = $goal['target_amount'] > 0 ? ($goal['current_amount'] / $goal['target_amount']) * 100 : 0;
        $goal['progress'] = min(100, max(0, round($progress, 2)));
    }
    return $goals;
}

/**
 * Pobiera członków rodziny
 */
function getFamilyMembers($user_id) {
    return db()->select("
        SELECT u.id, u.username, u.email, f.relationship, f.can_edit_transactions
        FROM family_links f
        JOIN users u ON f.linked_user_id = u.id
        WHERE f.primary_user_id = ? AND u.is_active = 1
    ", [$user_id]);
}

/**
 * Sprawdza czy użytkownik jest w rodzinie
 */
function isFamilyMember($user_id, $linked_user_id) {
    return db()->count("SELECT COUNT(*) FROM family_links WHERE primary_user_id = ? AND linked_user_id = ?", [$user_id, $linked_user_id]) > 0;
}

// ====================
// BAZA DANYCH HELPERS
// ====================

/**
 * Pobiera dane użytkownika - POPRAWIONA WERSJA!
 */
function getUserData($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? 0;
    }
    
    if (!$user_id) {
        return null;
    }
    
    try {
        // POPRAWKA: Używamy selectOne() zamiast prepare()
        return db()->selectOne("SELECT * FROM users WHERE id = ?", [$user_id]);
    } catch (Exception $e) {
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("getUserData error: " . $e->getMessage());
        }
        return null;
    }
}

// ====================
// FLASH MESSAGES
// ====================
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = ['text' => $message, 'type' => $type];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

// ====================
// INICJALIZACJA
// ====================
$required_folders = [__DIR__ . '/../uploads', __DIR__ . '/../logs'];
foreach ($required_folders as $folder) {
    if (!is_dir($folder)) @mkdir($folder, 0755, true);
}

generateCsrfToken();