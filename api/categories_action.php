<?php
// api/categories_action.php
header('Content-Type: application/json');

// Wyłącz wyświetlanie błędów HTML, żeby nie psuły JSON-a
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/db.php';
require_once '../includes/functions.php';

// Start sesji jeśli nie aktywna
if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = getCurrentUserId();

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Brak autoryzacji']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = db();

try {
    if ($action === 'get') {
        $stmt = $db->prepare("SELECT * FROM categories WHERE user_id = ? ORDER BY type, name");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $data]);
    }
    elseif ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'expense';
        $color = $_POST['color'] ?? '#3b82f6';
        $icon = $_POST['icon'] ?? 'fa-tag';
        
        $stmt = $db->prepare("INSERT INTO categories (user_id, name, type, color, icon) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $name, $type, $color, $icon]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'update') {
        $id = $_POST['id'];
        $name = trim($_POST['name']);
        $type = $_POST['type'];
        $color = $_POST['color'];
        $icon = $_POST['icon'];
        
        $stmt = $db->prepare("UPDATE categories SET name=?, type=?, color=?, icon=? WHERE id=? AND user_id=?");
        $stmt->execute([$name, $type, $color, $icon, $id, $user_id]);
        echo json_encode(['success' => true]);
    }
    elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $db->prepare("DELETE FROM categories WHERE id=? AND user_id=?");
        $stmt->execute([$id, $user_id]);
        echo json_encode(['success' => true]);
    }
    else {
        echo json_encode(['success' => false, 'error' => 'Nieznana akcja']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Błąd serwera: ' . $e->getMessage()]);
}
?>