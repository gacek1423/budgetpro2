<?php
// api/planner_action.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();
$action = $_GET['action'] ?? '';

try {
    // --- DODAWANIE DO PLANERA ---
    if ($action === 'add') {
        $title = sanitizeInput($_POST['title']);
        $amount = (float)$_POST['amount'];
        $due_date = sanitizeInput($_POST['due_date']);
        $type = $_POST['type']; // income/expense
        
        // Tutaj pobieramy ID kategorii z formularza
        $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;

        $stmt = $db->prepare("INSERT INTO planned_payments (user_id, category_id, title, amount, due_date, type, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $category_id, $title, $amount, $due_date, $type]);
        
        echo json_encode(['success' => true]);
    } 
    
    // --- REALIZACJA PŁATNOŚCI (KLUCZOWE!) ---
    elseif ($action === 'pay') {
        $id = (int)$_GET['id'];
        
        // 1. Pobierz plan
        $stmt = $db->prepare("SELECT * FROM planned_payments WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) throw new Exception("Nie znaleziono.");
        if ($plan['status'] === 'paid') throw new Exception("Już opłacone.");

        $db->beginTransaction();

        try {
            // A. Oznacz jako opłacone
            $db->prepare("UPDATE planned_payments SET status = 'paid' WHERE id = ?")->execute([$id]);

            // B. Utwórz prawdziwą transakcję (Z KATEGORIĄ!)
            $insert = $db->prepare("INSERT INTO personal_transactions (user_id, category_id, amount, type, description, transaction_date, created_at) VALUES (?, ?, ?, ?, ?, CURDATE(), NOW())");
            
            $desc = "Z planera: " . $plan['title'];
            // Przekazujemy $plan['category_id'] do transakcji
            $insert->execute([$user_id, $plan['category_id'], $plan['amount'], $plan['type'], $desc]);

            $db->commit();
            echo json_encode(['success' => true]);

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // --- USUWANIE ---
    elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM planned_payments WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>