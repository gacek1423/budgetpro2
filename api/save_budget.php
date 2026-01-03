<?php
// api/save_budget.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();

// Obsługa metod (POST dla dodawania/edycji, GET/POST dla usuwania)
$action = $_GET['action'] ?? 'save';

try {
    if ($action === 'save') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $cat_id = (int)$input['category_id'];
        $limit = (float)$input['amount_limit'];

        if($limit <= 0) throw new Exception("Limit musi być większy od zera.");

        // Używamy INSERT ON DUPLICATE KEY UPDATE
        // Dzięki temu, jeśli budżet dla tej kategorii już istnieje, po prostu zaktualizujemy kwotę.
        $sql = "INSERT INTO budgets (user_id, category_id, amount_limit) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE amount_limit = VALUES(amount_limit)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $cat_id, $limit]);
        
        echo json_encode(['success' => true]);
    } 
    elseif ($action === 'delete') {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("DELETE FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }
    
    // Pobranie danych pojedynczego budżetu (do edycji w modalu)
    elseif ($action === 'get') {
        $id = (int)$_GET['id'];
        $stmt = $db->prepare("SELECT * FROM budgets WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>