<?php
// api/process_import.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) exit(json_encode(['error' => 'Unauthorized']));

$user_id = getCurrentUserId();
$db = db();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['transactions'])) {
    exit(json_encode(['error' => 'Brak danych']));
}

$added = 0;
$skipped = 0;

try {
    $db->beginTransaction();

    foreach ($input['transactions'] as $t) {
        // 1. CZYSZCZENIE DANYCH
        $rawDate = trim($t['date']);
        $rawAmount = trim($t['amount']);
        $desc = sanitizeInput($t['description']);

        // Formatowanie Daty (Banki dają różne: dd-mm-yyyy, yyyy-mm-dd, dd.mm.yyyy)
        // Próba ujednolicenia do Y-m-d
        $timestamp = strtotime(str_replace('.', '-', $rawDate));
        if (!$timestamp) continue; // Pomiń błędne daty
        $date = date('Y-m-d', $timestamp);

        // Formatowanie Kwoty (Banki dają: "1 200,50", "-50,00 PLN")
        // Usuwamy waluty i spacje, zamieniamy przecinek na kropkę
        $amountStr = str_replace(['PLN', 'EUR', ' ', ' '], '', $rawAmount); // ' ' to non-breaking space
        $amountStr = str_replace(',', '.', $amountStr);
        $amount = (float)$amountStr;

        if ($amount == 0) continue;

        // Określ typ
        $type = ($amount > 0) ? 'income' : 'expense';
        $amountAbs = abs($amount); // Do bazy wrzucamy wartość bezwzględną (chyba że wolisz inaczej)

        // 2. DEDUPLIKACJA (HASH)
        // Hash tworzymy z: Data + Kwota + Opis. To unikalny identyfikator.
        $hashString = $date . number_format($amount, 2, '.', '') . trim($desc);
        $importHash = hash('sha256', $hashString);

        // Sprawdź czy hash istnieje u tego użytkownika
        $check = $db->prepare("SELECT id FROM personal_transactions WHERE user_id = ? AND import_hash = ?");
        $check->execute([$user_id, $importHash]);

        if ($check->rowCount() > 0) {
            $skipped++;
            continue; // To duplikat, pomijamy
        }

        // 3. DODAWANIE
        $sql = "INSERT INTO personal_transactions 
                (user_id, amount, category, type, description, transaction_date, import_hash) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        // Domyślna kategoria dla importu to "Importowane" (użytkownik zmieni sobie później)
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $user_id, 
            $amountAbs, 
            'Importowane', // Kategoria tymczasowa
            $type, 
            $desc, 
            $date, 
            $importHash
        ]);

        $added++;
    }

    $db->commit();
    echo json_encode(['success' => true, 'added' => $added, 'skipped' => $skipped]);

} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>