<?php
// D:\laragon\www\budgetpro\debug.php
// URUCHOM TEN PLIK W PRZEGLĄDARCE!

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 BUDGETPRO DEBUGGER</h1>";
echo "<pre>";

echo "\n1. Sprawdzam config.php:\n";
if (file_exists('config.php')) {
    require_once 'config.php';
    echo "✅ Załadowano config. DB_HOST: " . DB_HOST . "\n";
} else {
    die("❌ Brak config.php w root!");
}

echo "\n2. Sprawdzam includes/functions.php:\n";
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
    echo "✅ Załadowano functions\n";
} else {
    die("❌ Brak functions.php!");
}

echo "\n3. Sprawdzam includes/db.php:\n";
if (file_exists('includes/db.php')) {
    require_once 'includes/db.php';
    echo "✅ Załadowano db.php\n";
    
    // Test połączenia
    try {
        $pdo = db()->getConnection();
        echo "✅ Połączenie z bazą OK!\n";
    } catch (Exception $e) {
        echo "❌ BŁĄD BAZY: " . $e->getMessage() . "\n";
    }
}

echo "\n4. Sprawdzam includes/session.php:\n";
if (file_exists('includes/session.php')) {
    require_once 'includes/session.php';
    echo "✅ Załadowano session.php\n";
} else {
    die("❌ Brak session.php!");
}

echo "\n5. Sprawdzam includes/header.php:\n";
if (file_exists('includes/header.php')) {
    require_once 'includes/header.php';
    echo "✅ Załadowano header.php\n";
} else {
    die("❌ Brak header.php!");
}

echo "\n🎉 WSZYSTKO DZIAŁA! Teraz sprawdź dashboard.php:\n";
echo "</pre>";