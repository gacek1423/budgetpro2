<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/session.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    exit(json_encode(['error' => 'Unauthorized']));
}

$db = db();
$user_id = $_SESSION['user_id'];
$id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'personal'; // personal lub business

if (!$id) {
    echo json_encode(['error' => 'Brak ID']);
    exit();
}

try {
    $table = ($type === 'business') ? 'business_transactions' : 'personal_transactions';
    
    $sql = "DELETE FROM $table WHERE id = ? AND user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Nie znaleziono transakcji']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>