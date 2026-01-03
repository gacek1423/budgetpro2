<?php
// budgetpro2/api/login_action.php
// ZASTĄP CAŁĄ ZAWARTOŚĆ TYM KODEM:

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/TwoFactorService.php';
require_once __DIR__ . '/../includes/RateLimiter.php';

// Inicjalizacja rate limiter dla loginu
$rateLimiter = new RateLimiter('login', 5, 900); // 5 prób w 15 minut
$ip = $_SERVER['REMOTE_ADDR'];

try {
    // Sprawdź rate limit PRZED jakąkolwiek logiką
    if (!$rateLimiter->check($ip)) {
        $remaining = $rateLimiter->getRemainingTime($ip);
        throw new Exception("Zbyt wiele prób logowania. Spróbuj ponownie za " . ceil($remaining / 60) . " minut.");
    }

    // Odbierz dane
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $email = filter_var($data['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $data['password'] ?? '';
    $code = $data['code'] ?? '';

    // Walidacja
    if (empty($email) || empty($password)) {
        throw new Exception("Podaj email i hasło.");
    }

    // Sprawdź użytkownika w bazie
    $stmt = db()->prepare("
        SELECT id, username, password_hash, is_2fa_enabled, two_factor_secret 
        FROM users 
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Sleep dla bezpieczeństwa (ukrywa fakt istnienia użytkownika)
        usleep(500000); // 500ms
        throw new Exception("Nieprawidłowe dane logowania.");
    }

    // Weryfikacja hasła
    if (!password_verify($password, $user['password_hash'])) {
        // Zarejestruj nieudaną próbę
        $rateLimiter->hit($ip);
        throw new Exception("Nieprawidłowe dane logowania.");
    }

    // Reset limitu po udanym haśle
    $rateLimiter->reset($ip);

    // Sprawdź 2FA
    $is2fa = (int)$user['is_2fa_enabled'];
    
    if ($is2fa === 1) {
        if (empty($code)) {
            echo json_encode(['status' => '2fa_required']);
            exit;
        }

        $tfa = new TwoFactorService();
        $cleanCode = preg_replace('/\s+/', '', $code); // Usuń spacje
        
        if (!$tfa->verifyCode($user['two_factor_secret'], $cleanCode)) {
            $rateLimiter->hit($ip); // Blokuj też za złe kody 2FA
            throw new Exception("Nieprawidłowy kod 2FA.");
        }
    }

    // Logowanie udane!
    doLogin($user);

} catch (Exception $e) {
    http_response_code(200); // 200 OK, ale błąd w JSON
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'retry_after' => $rateLimiter->getRemainingTime($ip)
    ]);
}

function doLogin($user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();

    // Zaktualizuj last_login
    db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
       ->execute([(int)$user['id']]);

    echo json_encode(['status' => 'success']);
    exit;
}