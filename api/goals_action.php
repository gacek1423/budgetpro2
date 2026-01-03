<?php
// api/goals_action.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();
$action = $_GET['action'] ?? '';

try {
    // 1. POBIERZ CELE
    if ($action === 'get') {
        $stmt = $db->prepare("SELECT * FROM savings_goals WHERE user_id = ? ORDER BY status ASC, deadline ASC");
        $stmt->execute([$user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    // 2. DODAJ NOWY CEL
    elseif ($action === 'add') {
        $input = json_decode(file_get_contents('php://input'), true);
        $stmt = $db->prepare("INSERT INTO savings_goals (user_id, name, target_amount, current_amount, deadline, icon, color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id,
            sanitizeInput($input['name']),
            (float)$input['target_amount'],
            (float)($input['current_amount'] ?? 0),
            $input['deadline'] ?: null,
            $input['icon'] ?? 'fa-star',
            $input['color'] ?? '#3b82f6'
        ]);
        echo json_encode(['success' => true]);
    }

    // 3. DOPŁAĆ DO CELU (Deposit)
    elseif ($action === 'deposit') {
        $id = (int)$_GET['id'];
        $amount = (float)$_GET['amount']; // Kwota do dodania
        
        if ($amount <= 0) throw new Exception("Kwota musi być dodatnia.");

        // Sprawdź czy cel istnieje
        $stmt = $db->prepare("SELECT * FROM savings_goals WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $goal = $stmt->fetch();
        if(!$goal) throw new Exception("Nie znaleziono celu.");

        // Oblicz nową kwotę
        $newAmount = $goal['current_amount'] + $amount;
        $status = ($newAmount >= $goal['target_amount']) ? 'completed' : 'active';

        // Aktualizuj cel
        $upd = $db->prepare("UPDATE savings_goals SET current_amount = ?, status = ? WHERE id = ?");
        $upd->execute([$newAmount, $status, $id]);

        // OPCJONALNIE: Tutaj można by dodać transakcję "Wypłata na cel" w historii transakcji, 
        // ale na razie zróbmy to jako "Wirtualną Skarbonkę".

        echo json_encode(['success' => true, 'completed' => ($status === 'completed')]);
    }

    // 4. USUŃ CEL
    elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        $db->prepare("DELETE FROM savings_goals WHERE id = ? AND user_id = ?")->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>