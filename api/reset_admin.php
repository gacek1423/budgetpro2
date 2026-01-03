<?php
// api/reset_admin.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../includes/functions.php';

echo "<h2>🔧 Naprawa Konta Administratora</h2>";

try {
    $db = db();
    echo "<p>✅ Połączono z bazą danych.</p>";

    // 1. DANE DO UTWORZENIA
    $email = 'admin@demo.com';
    $password_jawne = '1234'; // To będzie Twoje hasło
    $username = 'Admin Naprawiony';

    // 2. GENEROWANIE HASHU (To kluczowy moment - generujemy go Twoim serwerem)
    $hash = password_hash($password_jawne, PASSWORD_DEFAULT);
    echo "<p>🔑 Wygenerowano nowy hash dla hasła '1234': <br><code>$hash</code></p>";

    // 3. USUWANIE STAREGO KONTA (Żeby nie było konfliktów)
    $del = $db->prepare("DELETE FROM users WHERE email = ?");
    $del->execute([$email]);
    echo "<p>🗑️ Usunięto starego użytkownika ($email), jeśli istniał.</p>";

    // 4. TWORZENIE NOWEGO KONTA
    // Ustawiamy is_2fa_enabled na 0, żebyś mógł wejść bez kodu!
    $sql = "INSERT INTO users (username, email, password, is_2fa_enabled, created_at) VALUES (?, ?, ?, 0, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$username, $email, $hash]);
    
    $newId = $db->lastInsertId();
    echo "<p>✨ <b>Sukces!</b> Utworzono nowego użytkownika.</p>";
    echo "<ul>
            <li>ID: $newId</li>
            <li>Email: <b>$email</b></li>
            <li>Hasło: <b>$password_jawne</b></li>
            <li>2FA: Wyłączone</li>
          </ul>";

    // 5. TEST WERYFIKACJI (Sprawdźmy to OD RAZU)
    echo "<hr><h3>🔍 Autotest Weryfikacji:</h3>";
    $check = $db->prepare("SELECT password FROM users WHERE email = ?");
    $check->execute([$email]);
    $storedHash = $check->fetchColumn();

    if (password_verify('1234', $storedHash)) {
        echo "<h2 style='color: green'>TEST ZALICZONY: Hasło działa poprawnie!</h2>";
        echo "<p><a href='../login.php' style='font-size: 20px; font-weight: bold;'>👉 Kliknij tutaj, aby się zalogować</a></p>";
    } else {
        echo "<h2 style='color: red'>BŁĄD KRYTYCZNY: Hasło nie pasuje nawet po resecie!</h2>";
        echo "<p>Problem może leżeć w konfiguracji serwera PHP (moduł password).</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color: red'>BŁĄD SQL:</h2>";
    echo $e->getMessage();
}
?>