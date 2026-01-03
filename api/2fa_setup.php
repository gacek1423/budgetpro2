<?php
// api/2fa_setup.php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../includes/TwoFactorService.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();
$tfa = new TwoFactorService();

$action = $_GET['action'] ?? '';

try {
    // 1. Generuj nowy sekret i pokaż QR
    if ($action === 'generate') {
        $secret = $tfa->createSecret();
        // Nie zapisujemy jeszcze do bazy na stałe, dopiero po weryfikacji kodu!
        // Ale musimy go wysłać do frontendu, żeby wygenerować QR.
        
        $qrUrl = $tfa->getQRCodeUrl('BudgetPro', $secret, 'BudgetPro Enterprise');
        
        echo json_encode([
            'success' => true,
            'secret' => $secret,
            'qr_url' => $qrUrl
        ]);
    }
    
    // 2. Włącz 2FA (weryfikacja kodu)
    elseif ($action === 'enable') {
        $input = json_decode(file_get_contents('php://input'), true);
        $secret = $input['secret'];
        $code = $input['code'];
        
        if ($tfa->verifyCode($secret, $code)) {
            // Kod poprawny -> Zapisujemy sekret i włączamy 2FA w bazie
            $stmt = $db->prepare("UPDATE users SET two_factor_secret = ?, is_2fa_enabled = 1 WHERE id = ?");
            $stmt->execute([$secret, $user_id]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Nieprawidłowy kod z aplikacji.']);
        }
    }

    // 3. Wyłącz 2FA
    elseif ($action === 'disable') {
        $stmt = $db->prepare("UPDATE users SET two_factor_secret = NULL, is_2fa_enabled = 0 WHERE id = ?");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>