<?php
// api/debts_action.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();
$action = $_GET['action'] ?? 'get';

try {
    // POBIERANIE LISTY
    if ($action === 'get') {
        $stmt = $db->prepare("SELECT * FROM debts WHERE user_id = ? ORDER BY balance DESC");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // DODAWANIE DŁUGU
    elseif ($action === 'add') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $name = sanitizeInput($input['name']);
        $balance = (float)$input['balance'];
        $rate = (float)$input['interest_rate'];
        $min_pay = (float)$input['min_payment'];

        if($balance <= 0 || $min_pay <= 0) throw new Exception("Kwoty muszą być dodatnie.");

        $stmt = $db->prepare("INSERT INTO debts (user_id, name, balance, interest_rate, min_payment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $balance, $rate, $min_pay]);
        
        echo json_encode(['success' => true]);
    }

    // USUWANIE
    elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM debts WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }

    // AKTUALIZACJA SALDA (np. po spłacie)
    elseif ($action === 'update_balance') {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)$input['id'];
        $new_balance = (float)$input['balance'];
        
        $stmt = $db->prepare("UPDATE debts SET balance = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_balance, $id, $user_id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>