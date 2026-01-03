<?php
// api/get_stats.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();

try {
    // 1. DANE MIESIĘCZNE (do wykresów) - Ostatnie 12 miesięcy
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        FROM personal_transactions 
        WHERE user_id = ? 
          AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute([$user_id]);
    $trendData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. PORÓWNANIE MIESIĘCZNE (KPI)
    $currentMonthExpenses = getSum("
        SELECT COALESCE(SUM(amount), 0) FROM personal_transactions 
        WHERE user_id = ? AND type = 'expense' 
        AND MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
    ", [$user_id]);

    $lastMonthExpenses = getSum("
        SELECT COALESCE(SUM(amount), 0) FROM personal_transactions 
        WHERE user_id = ? AND type = 'expense' 
        AND MONTH(transaction_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) 
        AND YEAR(transaction_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
    ", [$user_id]);

    $percentageChange = 0;
    if ($lastMonthExpenses > 0) {
        $percentageChange = (($currentMonthExpenses - $lastMonthExpenses) / $lastMonthExpenses) * 100;
    }

    // 3. STRUKTURA KATEGORII (Doughnut) - Tylko ten miesiąc
    $stmt = $db->prepare("
        SELECT c.name, c.color, SUM(t.amount) as total 
        FROM personal_transactions t 
        JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ? AND t.type = 'expense' 
          AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
        GROUP BY c.id 
        ORDER BY total DESC
    ");
    $stmt->execute([$user_id]);
    $catData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. TOP 5 NAJWIĘKSZYCH WYDATKÓW (NOWOŚĆ)
    $stmt = $db->prepare("
        SELECT t.description, t.amount, t.transaction_date, c.name as cat_name, c.icon as cat_icon, c.color as cat_color
        FROM personal_transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'expense'
        AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE()) -- Tylko ten miesiąc
        ORDER BY t.amount DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $topExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. WSKAŹNIK OSZCZĘDNOŚCI (Savings Rate)
    $incomeThisMonth = getSum("SELECT COALESCE(SUM(amount),0) FROM personal_transactions WHERE user_id=? AND type='income' AND MONTH(transaction_date)=MONTH(CURRENT_DATE())", [$user_id]);
    $expenseThisMonth = $currentMonthExpenses;
    $savingsRate = 0;
    if($incomeThisMonth > 0) {
        $savingsRate = (($incomeThisMonth - $expenseThisMonth) / $incomeThisMonth) * 100;
    }

    echo json_encode([
        'trend' => $trendData,
        'comparison' => [
            'current' => $currentMonthExpenses,
            'last' => $lastMonthExpenses,
            'percent' => round($percentageChange, 1)
        ],
        'categories' => $catData,
        'top_expenses' => $topExpenses,
        'savings_rate' => round($savingsRate, 1)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>