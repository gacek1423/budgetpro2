<?php
// api/networth_action.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$user_id = getCurrentUserId();
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Brak autoryzacji']);
    exit;
}

$action = $_GET['action'] ?? '';
$db = db();

try {
    // 1. ZAPISYWANIE STANU (Aktualizacja na dzień dzisiejszy)
    if ($action === 'add') {
        $assets = (float)($_POST['assets'] ?? 0);
        $liabilities = (float)($_POST['liabilities'] ?? 0);
        $net_worth = $assets - $liabilities;
        $date = date('Y-m-d'); // Dzisiejsza data

        // Upsert (Wstaw lub zaktualizuj jeśli wpis z dzisiaj już istnieje)
        $sql = "INSERT INTO net_worth_history (user_id, record_date, assets, liabilities, net_worth) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE assets = VALUES(assets), liabilities = VALUES(liabilities), net_worth = VALUES(net_worth)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$user_id, $date, $assets, $liabilities, $net_worth]);
        
        echo json_encode(['success' => true]);
    }

    // 2. POBIERANIE HISTORII (Do wykresu)
    elseif ($action === 'get_history') {
        // Pobierz ostatnie 30 wpisów (lub więcej)
        $stmt = $db->prepare("SELECT * FROM net_worth_history WHERE user_id = ? ORDER BY record_date ASC");
        $stmt->execute([$user_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Formatowanie daty dla JS
        foreach($data as &$row) {
            $row['date_formatted'] = date('d.m.Y', strtotime($row['record_date']));
        }

        echo json_encode(['success' => true, 'data' => $data]);
    }

    else {
        throw new Exception("Nieznana akcja");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>