<?php
// api/login_action.php
// Wymuszamy raportowanie błędów tylko do logów, nie na ekran (żeby nie psuć JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once '../includes/functions.php';
require_once '../includes/TwoFactorService.php';

try {
    // 1. Odbiór danych (z obsługą błędów JSON)
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Błąd danych JSON: " . json_last_error_msg());
    }

    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $code = isset($input['code']) ? trim($input['code']) : '';

    if (empty($email) || empty($password)) {
        throw new Exception("Podaj email i hasło.");
    }

    $db = db();

    // 2. Pobierz użytkownika
    // Pobieramy jawnie kolumny, aby uniknąć problemów z 'select *'
    $stmt = $db->prepare("SELECT id, username, password, is_2fa_enabled, two_factor_secret FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Sleep dla bezpieczeństwa (przeciw Brute Force)
        usleep(200000); 
        throw new Exception("Nieprawidłowe dane logowania.");
    }

    // 3. Weryfikacja hasła
    if (!password_verify($password, $user['password'])) {
        throw new Exception("Nieprawidłowe dane logowania.");
    }

    // --- LOGOWANIE UDANE (Hasło OK) ---

    // Konwersja na integer dla pewności (baza czasem zwraca string "0")
    $is2fa = (int)$user['is_2fa_enabled'];

    // 4. Sprawdzanie 2FA
    if ($is2fa === 1) {
        // Jeśli 2FA jest włączone
        if (empty($code)) {
            echo json_encode(['status' => '2fa_required']);
            exit;
        }

        // Weryfikacja kodu
        $tfa = new TwoFactorService();
        // Usuń spacje z kodu jeśli user wpisał "123 456"
        $cleanCode = str_replace(' ', '', $code);
        
        if ($tfa->verifyCode($user['two_factor_secret'], $cleanCode)) {
            doLogin($user);
        } else {
            throw new Exception("Nieprawidłowy kod 2FA.");
        }
    } else {
        // 5. Brak 2FA - logujemy od razu
        doLogin($user);
    }

} catch (Exception $e) {
    // Zwracamy czysty JSON z błędem
    http_response_code(200); // 200 OK, ale status: error w JSON
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

// Funkcja pomocnicza - ustawia sesję
function doLogin($user) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['logged_in'] = true;
    
    echo json_encode(['status' => 'success']);
    exit;
}
?>