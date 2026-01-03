<?php
// api/get_transactions.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $db = db();
    $user_id = getCurrentUserId();
    $type = $_GET['type'] ?? 'personal';
    
    $table = ($type === 'business') ? 'business_transactions' : 'personal_transactions';
    
    // Walidacja nazwy tabeli dla bezpieczeństwa
    if (!in_array($table, ['personal_transactions', 'business_transactions'])) {
        throw new Exception("Nieprawidłowy typ");
    }

    $sql = "SELECT * FROM $table WHERE user_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 50";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data);

} catch (Exception $e) {
    logger($e->getMessage(), 'API Get Error');
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>