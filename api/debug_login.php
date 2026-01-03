<?php
// api/debug_login.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/functions.php';

// USTAWIENIA DO TESTU
$email = 'admin@demo.com'; 
$haslo_do_testu = '1234'; // Wpisz tu to hasło, które ustawiłeś

echo "<h2>Diagnostyka Logowania</h2>";

try {
    $db = db();
    echo "<p>✅ Połączenie z bazą: OK</p>";
} catch (Exception $e) {
    die("<p>❌ Błąd bazy: " . $e->getMessage() . "</p>");
}

// 1. Sprawdź czy user istnieje
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("<p>❌ Użytkownik <b>$email</b> nie istnieje w bazie!</p>");
}
echo "<p>✅ Użytkownik znaleziony (ID: {$user['id']})</p>";

// 2. Sprawdź hasło
echo "<p>Sprawdzanie hasła: <b>$haslo_do_testu</b></p>";
echo "<p>Hash w bazie: <small>{$user['password']}</small></p>";

if (password_verify($haslo_do_testu, $user['password'])) {
    echo "<p style='color:green; font-weight:bold'>✅ Hasło POPRAWNE</p>";
} else {
    echo "<p style='color:red; font-weight:bold'>❌ Hasło NIEPOPRAWNE</p>";
    echo "<p>Wskazówka: Hash w bazie może nie pasować do algorytmu. Użyj fix_password.php ponownie.</p>";
}

// 3. Sprawdź status 2FA
echo "<hr>";
echo "<p>Status 2FA w bazie (is_2fa_enabled): <b>" . var_export($user['is_2fa_enabled'], true) . "</b></p>";
echo "<p>Sekret 2FA (two_factor_secret): <b>" . var_export($user['two_factor_secret'], true) . "</b></p>";

if ($user['is_2fa_enabled'] == 1) {
    echo "<p>ℹ️ System powinien poprosić o kod (status: 2fa_required).</p>";
} else {
    echo "<p>ℹ️ System powinien zalogować od razu.</p>";
}

// 4. Test Sesji
$_SESSION['test_var'] = 'Dziala';
echo "<hr>";
if (isset($_SESSION['test_var']) && $_SESSION['test_var'] == 'Dziala') {
    echo "<p>✅ Sesje PHP działają poprawnie.</p>";
} else {
    echo "<p>❌ PROBLEM Z SESJAMI PHP! (Katalog tmp nie zapisywalny?)</p>";
}
?>