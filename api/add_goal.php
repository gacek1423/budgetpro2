<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));

$input = json_decode(file_get_contents('php://input'), true);
$db = db();
$user_id = getCurrentUserId();

try {
    $sql = "INSERT INTO personal_goals (user_id, name, target_amount, current_amount, deadline, category) VALUES (?, ?, ?, ?, ?, 'General')";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $user_id,
        sanitizeInput($input['name']),
        (float)$input['target_amount'],
        (float)$input['current_amount'],
        sanitizeInput($input['deadline'])
    ]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>