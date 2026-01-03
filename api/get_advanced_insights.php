<?php
// api/get_advanced_insights.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Auth required']));
$user_id = getCurrentUserId();
$db = db();

$insights = [];

try {
    // 1. PORÓWNANIE KATEGORII (Ten miesiąc vs Zeszły)
    // Pobieramy wydatki z tego i zeszłego miesiąca pogrupowane po ID kategorii
    $sql = "
        SELECT 
            c.name,
            SUM(CASE WHEN MONTH(t.transaction_date) = MONTH(CURRENT_DATE()) THEN t.amount ELSE 0 END) as current_month,
            SUM(CASE WHEN MONTH(t.transaction_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) THEN t.amount ELSE 0 END) as last_month
        FROM personal_transactions t
        JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? AND t.type = 'expense'
        AND t.transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) -- Optymalizacja zakresu
        GROUP BY c.id
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id]);
    $cats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cats as $cat) {
        if ($cat['last_month'] > 50) { // Analizuj tylko istotne kategorie (>50zł w zeszłym msc)
            $diff = $cat['current_month'] - $cat['last_month'];
            $percent = round(($diff / $cat['last_month']) * 100);
            
            if ($percent < -10) {
                // Sukces: Wydano mniej
                $insights[] = [
                    'type' => 'success',
                    'icon' => 'fa-arrow-trend-down',
                    'message' => "Świetnie! W tym miesiącu wydałeś o <b>" . abs($percent) . "% mniej</b> na <b>{$cat['name']}</b> niż w zeszłym."
                ];
            } elseif ($percent > 15) {
                // Ostrzeżenie: Wydano więcej
                $insights[] = [
                    'type' => 'warning',
                    'icon' => 'fa-arrow-trend-up',
                    'message' => "Uwaga: Wydałeś już o <b>{$percent}% więcej</b> na <b>{$cat['name']}</b> niż w całym poprzednim miesiącu."
                ];
            }
        }
    }

    // 2. PROGNOZA BUDŻETOWA (Forecasting)
    // Pobieramy budżety i aktualne wydatki
    $stmt = $db->prepare("
        SELECT b.amount_limit, c.name, 
               COALESCE(SUM(t.amount),0) as spent
        FROM budgets b
        JOIN categories c ON b.category_id = c.id
        LEFT JOIN personal_transactions t ON t.category_id = c.id 
            AND MONTH(t.transaction_date) = MONTH(CURRENT_DATE())
            AND YEAR(t.transaction_date) = YEAR(CURRENT_DATE())
        WHERE b.user_id = ?
        GROUP BY b.id
    ");
    $stmt->execute([$user_id]);
    $budgets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $daysInMonth = (int)date('t');
    $currentDay = (int)date('j');

    foreach ($budgets as $b) {
        if ($b['spent'] > 0 && $currentDay > 1) {
            $dailyAvg = $b['spent'] / $currentDay;
            $projected = $dailyAvg * $daysInMonth;
            
            // Jeśli prognoza przekracza limit i jeszcze go nie przekroczyliśmy
            if ($projected > $b['amount_limit'] && $b['spent'] < $b['amount_limit']) {
                // Oblicz dzień przekroczenia
                $remainingBudget = $b['amount_limit'] - $b['spent'];
                $daysToFail = $remainingBudget / $dailyAvg;
                $failDay = $currentDay + ceil($daysToFail);
                
                if ($failDay <= $daysInMonth) {
                    $insights[] = [
                        'type' => 'danger',
                        'icon' => 'fa-triangle-exclamation',
                        'message' => "Alarm budżetowy: W tym tempie przekroczysz limit na <b>{$b['name']}</b> około <b>{$failDay}-go dnia miesiąca</b>."
                    ];
                }
            }
        }
    }
    
    // Jeśli brak insightów, dodaj jeden domyślny
    if (empty($insights)) {
        $insights[] = [
            'type' => 'info',
            'icon' => 'fa-check-circle',
            'message' => "Wszystko wygląda stabilnie. Twoje wydatki są w normie."
        ];
    }

    echo json_encode(['success' => true, 'insights' => $insights]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>