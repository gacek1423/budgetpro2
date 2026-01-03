<?php
// budgetpro2/api/get_transactions.php

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = min(100, (int)($_GET['limit'] ?? 50));
$offset = ($page - 1) * $limit;
$sort = $_GET['sort'] ?? 'date';
$order = $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
$search = trim($_GET['search'] ?? '');

try {
    $sql = "SELECT t.*, c.name as category_name, c.color as category_color 
            FROM personal_transactions t 
            LEFT JOIN categories c ON t.category_id = c.id 
            WHERE t.user_id = ?";
    
    $params = [$user_id];
    
    if ($search) {
        $sql .= " AND (t.description LIKE ? OR c.name LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $orderBy = match($sort) {
        'amount' => "t.amount $order",
        'category' => "c.name $order",
        default => "t.transaction_date $order, t.id DESC"
    };
    $sql .= " ORDER BY $orderBy LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $db = db();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $summary = $db->selectOne("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expense
        FROM personal_transactions
        WHERE user_id = ?
    ", [$user_id]);
    
    $total = $db->count("SELECT COUNT(*) FROM personal_transactions WHERE user_id = ?", [$user_id]);
    $total_pages = (int)ceil($total / $limit);
    
    echo json_encode([
        'data' => $transactions,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ],
        'summary' => [
            'total_transactions' => (int)$summary['total_transactions'],
            'total_income' => (float)$summary['total_income'],
            'total_expense' => (float)$summary['total_expense']
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Please try again later'
    ]);
}