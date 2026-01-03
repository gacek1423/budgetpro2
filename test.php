<?php
// test.php - TEST POPRAWNOŚCI ŚCIEŻEK
echo "<h1>Test Systemu</h1>";

// Sprawdź czy config.php istnieje
$configPath = __DIR__ . '/config.php';
echo "Config path: $configPath<br>";
echo "Config exists: " . (file_exists($configPath) ? '✅ TAK' : '❌ NIE') . "<br><br>";

// Spróbuj załadować
if (file_exists($configPath)) {
    require_once $configPath;
    echo "✅ Config załadowany!<br>";
    echo "DB_HOST: " . DB_HOST . "<br>";
    echo "DEBUG_MODE: " . (DEBUG_MODE ? 'ON' : 'OFF');
} else {
    echo "❌ BŁĄD: config.php nie istnieje w root!<br>";
    echo "Utwórz go w: " . __DIR__;
}