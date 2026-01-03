<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/session.php';

$database = new Database();
$db = db();
$user_id = getCurrentUserId();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_user':
        $query = "SELECT id, username, email, account_type, currency, language, theme FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
        break;
        
    case 'logout':
        logout();
        echo json_encode(['success' => true]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>