<?php
// api/budgets_action.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = getCurrentUserId();
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Brak autoryzacji']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = db();

try {
    // Ustawianie limitu (CREATE / UPDATE BUDGET)
    if ($action === 'set') {
        $cat_id = $_POST['category_id'] ?? null;
        $amount = $_POST['amount'] ?? 0;

        if (!$cat_id) throw new Exception("Brak kategorii");

        // Aktualizujemy pole budget_limit w tabeli categories
        $stmt = $db->prepare("UPDATE categories SET budget_limit = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$amount, $cat_id, $user_id]);
        
        echo json_encode(['success' => true]);
    }
    else {
        throw new Exception("Nieznana akcja");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>