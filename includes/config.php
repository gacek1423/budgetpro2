<?php
/**
 * BudgetPro Enterprise - Plik Konfiguracyjny
 * 
 * Wszystkie ustawienia aplikacji w jednym miejscu.
 * Zmień wartości według potrzeb swojego środowiska.
 * 
 * @version 2.0
 * @author BudgetPro Team
 */

// ============================================================================
// 🔐 BEZPIECZEŃSTWO I OGÓLNE USTAWIENIA
// ============================================================================

// Tryb debugowania (true = pokazuje błędy, false = produkcja)
define('DEBUG_MODE', true);

// Klucz szyfrowania dla sesji i tokenów (ZMIEŃ NA LOSOWY CIĄG!)
define('APP_SECRET_KEY', 'budgetpro_enterprise_secret_key_2026_change_me_in_production');

// Nazwa aplikacji
define('APP_NAME', 'BudgetPro Enterprise');
define('APP_VERSION', '2.0.0');
define('APP_ENVIRONMENT', 'development'); // development | staging | production

// Adres URL aplikacji (bez końcowego /)
// Zmień na właściwy adres w produkcji
define('APP_URL', 'http://localhost/budgetpro');

// ============================================================================
// 🗄️ BAZA DANYCH MYSQL
// ============================================================================

// Połączenie z bazą danych
define('DB_HOST', 'localhost');     // Adres serwera MySQL
define('DB_PORT', '3306');          // Port MySQL
define('DB_NAME', 'budgetpro');     // Nazwa bazy danych
define('DB_CHARSET', 'utf8mb4');    // Kodowanie znaków

// Uwierzytelnianie bazy danych
define('DB_USERNAME', 'root');      // Login MySQL
define('DB_PASSWORD', '');          // Hasło MySQL (domyślnie puste w XAMPP)

// Prefix tabel (opcjonalnie, jeśli chcesz mieć kilka instalacji)
define('DB_PREFIX', 'bp_');         // np. bp_users, bp_transactions

// ============================================================================
// 📁 ŚCIEŻKI PLIKÓW
// ============================================================================

// Główny katalog aplikacji
define('ROOT_PATH', dirname(__DIR__));

// Ścieżki do kluczowych folderów
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('PAGES_PATH', ROOT_PATH . '/pages');
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('API_PATH', ROOT_PATH . '/api');
define('LOGS_PATH', ROOT_PATH . '/logs');

// URL do folderów (dla linków w HTML)
define('ASSETS_URL', APP_URL . '/assets');
define('UPLOADS_URL', APP_URL . '/uploads');

// ============================================================================
// 🔑 USTAWIENIA SESJI I COOKIES
// ============================================================================

// Nazwa sesji
define('SESSION_NAME', 'budgetpro_session');

// Czas życia sesji (w sekundach)
define('SESSION_LIFETIME', 3600 * 24); // 24 godziny

// Ustawienia cookies
define('COOKIE_LIFETIME', 3600 * 24 * 30); // 30 dni
define('COOKIE_PATH', '/');
define('COOKIE_DOMAIN', ''); // Puste = aktualna domena
define('COOKIE_SECURE', false); // true tylko dla HTTPS
define('COOKIE_HTTPONLY', true); // Ochrona przed XSS

// ============================================================================
// 🌐 USTAWIENIA REGIONALNE
// ============================================================================

// Domyślna waluta
define('DEFAULT_CURRENCY', 'PLN'); // PLN, EUR, USD, GBP
define('CURRENCY_SYMBOL', 'zł');   // Symbol waluty

// Domyślny język
define('DEFAULT_LANGUAGE', 'pl');   // pl, en, de

// Domyślny motyw
define('DEFAULT_THEME', 'light');   // light, dark

// Format daty
define('DATE_FORMAT', 'Y-m-d');     // SQL format
define('DISPLAY_DATE_FORMAT', 'd.m.Y'); // Format wyświetlania

// Strefa czasowa
date_default_timezone_set('Europe/Warsaw');

// ============================================================================
// 📊 LIMITY I PAGINACJA
// ============================================================================

// Maksymalna liczba transakcji na stronę
define('TRANSACTIONS_PER_PAGE', 50);

// Maksymalna liczba projektów na stronę
define('PROJECTS_PER_PAGE', 20);

// Maksymalna liczba celów na stronę
define('GOALS_PER_PAGE', 10);

// ============================================================================
// 📤 USTAWIENIA EKSPORTU
// ============================================================================

// Domyślny format eksportu
define('DEFAULT_EXPORT_FORMAT', 'csv'); // csv, json, pdf

// Maksymalny rozmiar pliku eksportu (w KB)
define('MAX_EXPORT_SIZE', 10 * 1024); // 10MB

// Dozwolone formaty eksportu
define('ALLOWED_EXPORT_FORMATS', ['csv', 'json', 'pdf']);

// ============================================================================
// 🔒 ZABEZPIECZENIA
// ============================================================================

// Maksymalna liczba prób logowania
define('MAX_LOGIN_ATTEMPTS', 5);

// Czas blokady po przekroczeniu prób (w sekundach)
define('LOGIN_BLOCK_TIME', 900); // 15 minut

// Wymagana siła hasła
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);

// Token CSRF (włącz/wyłącz)
define('CSRF_PROTECTION_ENABLED', true);

// ============================================================================
// 📧 USTAWIENIA POWIADOMIEŃ
// ============================================================================

// Włącz/wyłącz powiadomienia email
define('EMAIL_NOTIFICATIONS_ENABLED', false); // Wymaga SMTP

// Włącz/wyłącz powiadomienia push
define('PUSH_NOTIFICATIONS_ENABLED', false);

// Włącz/wyłącz przypomnienia SMS
define('SMS_NOTIFICATIONS_ENABLED', false);

// Adres email administratora
define('ADMIN_EMAIL', 'admin@budgetpro.local');

// ============================================================================
// 🖼️ USTAWIENIA OBRAZÓW
// ============================================================================

// Maksymalny rozmiar uploadu (w KB)
define('MAX_UPLOAD_SIZE', 5 * 1024); // 5MB

// Dozwolone typy plików
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'svg']);

// Kompresja obrazów
define('IMAGE_COMPRESSION_QUALITY', 80); // 0-100

// ============================================================================
// 💾 USTAWIENIA CACHE
// ============================================================================

// Włącz/wyłącz cache
define('CACHE_ENABLED', false);

// Czas życia cache (w sekundach)
define('CACHE_LIFETIME', 3600); // 1 godzina

// Katalog cache
define('CACHE_PATH', ROOT_PATH . '/cache');

// ============================================================================
// 🐛 USTAWIENIA LOGOWANIA
// ============================================================================

// Poziom logowania
define('LOG_LEVEL', 'debug'); // debug, info, warning, error

// Plik logów
define('LOG_FILE', LOGS_PATH . '/app.log');

// Logowanie błędów SQL
define('LOG_SQL_ERRORS', true);

// Logowania prób logowania
define('LOG_LOGIN_ATTEMPTS', true);

// ============================================================================
// ⚡ USTAWIENIA WYDAJNOŚCI
// ============================================================================

// Maksymalny czas wykonania skryptu (sekundy)
set_time_limit(30);

// Limit pamięci
define('MEMORY_LIMIT', '256M');
ini_set('memory_limit', MEMORY_LIMIT);

// Włącz/wyłącz GZIP
define('GZIP_ENABLED', true);

// ============================================================================
// 🔄 USTAWIENIA AUTO-AKTUALIZACJI
// ============================================================================

// Włącz/wyłącz auto-aktualizacje kursów walut
define('AUTO_UPDATE_RATES', false);

// Interwał aktualizacji (w sekundach)
define('RATES_UPDATE_INTERVAL', 3600); // 1 godzina

// API do kursów walut
define('EXCHANGE_RATES_API', 'https://api.nbp.pl/api/exchangerates/tables/A/');

// ============================================================================
// 🏢 USTAWIENIA FIRMOWE
// ============================================================================

// Domyślny NIP firmy (pusty = użytkownik wprowadza)
define('DEFAULT_TAX_NUMBER', '');

// Domyślna nazwa firmy
define('DEFAULT_COMPANY_NAME', 'Moja Firma');

// Domyślny VAT (%)
define('DEFAULT_VAT_RATE', 23);

// ============================================================================
// 📱 USTAWIENIA PWA (Progressive Web App)
// ============================================================================

// Włącz/wyłącz tryb PWA
define('PWA_ENABLED', false);

// Nazwa aplikacji PWA
define('PWA_NAME', 'BudgetPro Enterprise');

// Kolor paska adresu (Chrome Mobile)
define('PWA_THEME_COLOR', '#1B4332');

// Kolor tła
define('PWA_BACKGROUND_COLOR', '#ffffff');

// ============================================================================
// 🔌 USTAWIENIA API ZEWNĘTRZNEGO
// ============================================================================

// Włącz/wyłącz API
define('API_ENABLED', false);

// Klucz API (zmień w produkcji!)
define('API_KEY', 'bp_api_key_2026_change_me');

// Wersja API
define('API_VERSION', 'v1');

// Rate limiting (liczba requestów na minutę)
define('API_RATE_LIMIT', 60);

// ============================================================================
// 🎨 USTAWIENIA UI
// ============================================================================

// Kolor akcentu aplikacji
define('DEFAULT_ACCENT_COLOR', '#1B4332');

// Pokaż/ukryj hinty w interfejsie
define('SHOW_UI_HINTS', true);

// Włącz/wyłącz smooth scroll
define('SMOOTH_SCROLL', true);

// ============================================================================
// 🗑️ USTAWIENIA CZYSZCZENIA
// ============================================================================

// Czas przechowywania starych logów (dni)
define('LOG_RETENTION_DAYS', 30);

// Czas przechowywania starych backupów (dni)
define('BACKUP_RETENTION_DAYS', 90);

// Czyść cache automatycznie
define('AUTO_CLEAN_CACHE', true);

// ============================================================================
// 📝 INNE USTAWIENIA
// ============================================================================

// Włącz/wyłącz demo mode
define('DEMO_MODE', false);

// ID użytkownika demo
define('DEMO_USER_ID', 999);

// Czy wymagać potwierdzenia emaila
define('EMAIL_VERIFICATION_REQUIRED', false);

// Czy wymagać akceptacji regulaminu
define('TERMS_ACCEPTANCE_REQUIRED', false);

/**
 * Funkcje pomocnicze dla konfiguracji
 */

// Helper: Sprawdź czy w trybie debugowania
function isDebugMode() {
    return defined('DEBUG_MODE') && DEBUG_MODE === true;
}

// Helper: Pobierz pełną ścieżkę tabeli z prefixem
function table($name) {
    return DB_PREFIX . $name;
}

// Helper: Wyświetl wartość konfiguracyjną
function config($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Helper: Sprawdź czy feature jest włączony
function featureEnabled($feature) {
    return defined($feature) && constant($feature) === true;
}

// Helper: Pobierz URL z trailing slash
function url($path = '') {
    return APP_URL . '/' . ltrim($path, '/');
}

// Helper: Pobierz ścieżkę pliku
function path($path = '') {
    return ROOT_PATH . '/' . ltrim($path, '/');
}