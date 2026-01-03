<?php
/**
 * BudgetPro Enterprise - Funkcje Pomocnicze (Updated)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php'; // Zakładam, że tu jest session_start()

// --- 1. FUNKCJE SESJI I UŻYTKOWNIKA ---

/**
 * Zwraca ID zalogowanego użytkownika
 */
function getCurrentUserId() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Wymusza logowanie - chroni podstrony
 */
function checkLogin() {
    if (!getCurrentUserId()) {
        redirect('../login.php');
    }
}

/**
 * Pobiera preferencje użytkownika (Cache w sesji dla wydajności)
 */
function getUserPreferences() {
    // Sprawdź cache w sesji
    if (isset($_SESSION['user_prefs'])) {
        return $_SESSION['user_prefs'];
    }
    
    $userId = getCurrentUserId();
    if ($userId) {
        try {
            $db = db(); 
            // Pobieramy kolumny ustawień
            $stmt = $db->prepare("SELECT currency_format, date_format, language, theme_color, start_page FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $prefs = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($prefs) {
                $_SESSION['user_prefs'] = $prefs;
                return $prefs;
            }
        } catch (Exception $e) {
            // Ignoruj błędy bazy (fallback do domyślnych)
        }
    }
    
    // Domyślne wartości (jeśli brak usera lub błąd)
    return [
        'currency_format' => 'pl',
        'date_format' => 'Y-m-d',
        'language' => 'pl',
        'theme_color' => 'blue',
        'start_page' => 'dashboard.php'
    ];
}

// --- 2. FORMATOWANIE I HELPERY ---

/**
 * Inteligentne formatowanie waluty wg ustawień
 */
function formatCurrency($amount) {
    $prefs = getUserPreferences();
    $amount = (float)$amount;
    
    if ($prefs['currency_format'] === 'en') {
        // Format: 1,234.56 PLN
        return number_format($amount, 2, '.', ',') . ' PLN';
    } else {
        // Format: 1 234,56 zł
        return number_format($amount, 2, ',', ' ') . ' zł';
    }
}

/**
 * Formatowanie daty wg ustawień
 */
function formatDate($dateString) {
    if (!$dateString) return '-';
    $prefs = getUserPreferences();
    $timestamp = strtotime($dateString);
    if (!$timestamp) return $dateString;
    
    return date($prefs['date_format'], $timestamp);
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function dd($data) {
    echo '<pre style="background: #111; color: #0f0; padding: 10px; z-index: 9999; position: relative;">';
    print_r($data);
    echo '</pre>';
    die();
}

function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        echo "<noscript><meta http-equiv='refresh' content='0;url=$url'></noscript>";
        exit();
    }
}

function sanitizeInput($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitizeInput($value);
        }
        return $data;
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// --- 3. BAZA DANYCH HELPERS ---

function getUserData($user_id = null) {
    if ($user_id === null) $user_id = getCurrentUserId();
    if (!$user_id) return null;
    try {
        $db = db();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { return null; }
}

function getSum($sql, $params = []) {
    try {
        $db = db();
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchColumn();
        return $result ? (float)$result : 0.00;
    } catch (PDOException $e) { return 0.00; }
}

// --- 4. BEZPIECZEŃSTWO ---

function checkBruteForce($ip) {
    $db = db();
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    return $stmt->fetchColumn() >= 5;
}

function recordFailedLogin($ip) {
    $db = db();
    $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)")->execute([$ip]);
}

function clearLoginAttempts($ip) {
    $db = db();
    $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// --- 5. POWIADOMIENIA (FLASH) ---

function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = [
        'text' => $message,
        'type' => $type
    ];
}

// --- 6. INICJALIZACJA ---

$required_folders = [__DIR__ . '/../uploads', __DIR__ . '/../logs'];
foreach ($required_folders as $folder) { if (!is_dir($folder)) @mkdir($folder, 0755, true); }

if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
    ini_set('display_errors', 1); error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0); error_reporting(0);
}
date_default_timezone_set('Europe/Warsaw');
// --- 7. TŁUMACZENIA (i18n) ---

// Zmienna globalna przechowująca załadowane słowa
$langData = [];

function loadLanguage() {
    global $langData;
    
    // Pobieramy język z preferencji użytkownika (funkcja, którą już mamy)
    $prefs = getUserPreferences();
    $langCode = $prefs['language'] ?? 'pl'; // Domyślnie PL
    
    // Ścieżka do pliku
    $file = __DIR__ . "/../lang/$langCode.php";
    
    // Ładowanie
    if (file_exists($file)) {
        $langData = require $file;
    } else {
        // Fallback do angielskiego jeśli plik PL nie istnieje (bezpiecznik)
        $fallback = __DIR__ . "/../lang/en.php";
        if(file_exists($fallback)) $langData = require $fallback;
    }
}

/**
 * Funkcja tłumacząca
 * Użycie: echo __('menu_dashboard');
 */
function __($key) {
    global $langData;
    // Jeśli tablica pusta, spróbuj załadować
    if (empty($langData)) {
        loadLanguage();
    }
    
    // Zwróć tłumaczenie lub sam klucz (jeśli brak tłumaczenia)
    return $langData[$key] ?? $key;
}

// Załaduj język przy starcie
loadLanguage();
?>
