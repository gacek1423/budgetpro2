<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));

$id = $_GET['id'] ?? null;
$db = db();

try {
    $stmt = $db->prepare("DELETE FROM personal_goals WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, getCurrentUserId()]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>