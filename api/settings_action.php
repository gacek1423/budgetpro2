<?php
// api/settings_action.php
header('Content-Type: application/json');

// Raportowanie błędów wyłączone dla wyjścia, żeby nie psuć JSON-a
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Ręczny start sesji jeśli nie jest aktywna
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Brak autoryzacji']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = db();

try {
    // 1. AKTUALIZACJA PROFILU
    if ($action === 'update_profile') {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);

        if (empty($username) || empty($email)) {
            throw new Exception("Pola nie mogą być puste");
        }

        // Sprawdź unikalność emaila
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception("Ten email jest już zajęty.");
        }

        $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        $stmt->execute([$username, $email, $user_id]);
        
        echo json_encode(['success' => true]);
    }

    // 2. ZMIANA HASŁA
    elseif ($action === 'change_password') {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        
        if (strlen($new) < 6) {
            throw new Exception("Nowe hasło musi mieć min. 6 znaków");
        }

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();

        if (!password_verify($current, $hash)) {
            throw new Exception("Obecne hasło jest nieprawidłowe");
        }

        $newHash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$newHash, $user_id]);

        echo json_encode(['success' => true]);
    }

    // 3. AKTUALIZACJA PREFERENCJI (W tym zmiana języka!)
    elseif ($action === 'update_preferences') {
        $currency = $_POST['currency_format'] ?? 'pl';
        $date_fmt = $_POST['date_format'] ?? 'Y-m-d';
        $lang = $_POST['language'] ?? 'pl';
        $color = $_POST['theme_color'] ?? 'blue';
        $start = $_POST['start_page'] ?? 'dashboard.php';

        // Aktualizacja bazy
        $stmt = $db->prepare("UPDATE users SET currency_format=?, date_format=?, language=?, theme_color=?, start_page=? WHERE id=?");
        $stmt->execute([$currency, $date_fmt, $lang, $color, $start, $user_id]);

        // WAŻNE: Czyścimy cache sesji, żeby zmiany (np. język) weszły od razu
        unset($_SESSION['user_prefs']);

        echo json_encode(['success' => true]);
    }

    // 4. RESET DANYCH
    elseif ($action === 'reset_data') {
        $tables = ['personal_transactions', 'planned_payments', 'savings_goals', 'budgets', 'debts', 'net_worth_history', 'categories'];
        foreach ($tables as $table) {
            $stmt = $db->prepare("DELETE FROM `$table` WHERE user_id = ?");
            $stmt->execute([$user_id]);
        }
        echo json_encode(['success' => true]);
    }

    // 5. USUNIĘCIE KONTA
    elseif ($action === 'delete_account') {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        session_destroy();
        echo json_encode(['success' => true]);
    }

    else {
        throw new Exception("Nieznana akcja: " . htmlspecialchars($action));
    }

} catch (Exception $e) {
    // Zwróć błąd jako JSON, a nie HTML
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>