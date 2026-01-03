<?php
// api/add_transaction.php
header('Content-Type: application/json');
require_once '../includes/functions.php';

if (!getCurrentUserId()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Brak autoryzacji']));
}

$user_id = getCurrentUserId();
$db = db(); 

try {
    // UWAGA: Przy przesyłaniu plików (FormData), dane są w $_POST, a nie w php://input
    if (empty($_POST)) {
        // Fallback dla czystego JSON (jeśli ktoś używa starego API)
        $_POST = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    $category_id = !empty($_POST['category']) ? (int)$_POST['category'] : null;
    $amount_input = $_POST['amount'] ?? 0;
    $type = $_POST['type'] ?? 'expense';
    $description = sanitizeInput($_POST['description'] ?? '');
    $date = sanitizeInput($_POST['transaction_date']);
    $currency = $_POST['currency'] ?? 'PLN';
    $exchange_rate = isset($_POST['exchange_rate']) ? (float)$_POST['exchange_rate'] : 1.0;

    if (!$category_id || !$amount_input) throw new Exception("Kwota i kategoria są wymagane.");

    // Przeliczanie walut
    if ($currency !== 'PLN') {
        $original_amount = (float)$amount_input;
        $amount = $original_amount * $exchange_rate;
    } else {
        $amount = (float)$amount_input;
        $original_amount = null;
        $exchange_rate = 1.0;
    }

    // --- OBSŁUGA PLIKU (PARAGON) ---
    $receipt_path = null;
    if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) throw new Exception("Format pliku niedozwolony (tylko JPG, PNG, PDF).");
        if ($_FILES['receipt']['size'] > 5 * 1024 * 1024) throw new Exception("Plik jest za duży (max 5MB).");

        $uploadDir = '../uploads/receipts/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        
        // Unikalna nazwa
        $fileName = 'receipt_' . $user_id . '_' . time() . '_' . rand(1000,9999) . '.' . $ext;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['receipt']['tmp_name'], $targetPath)) {
            $receipt_path = 'uploads/receipts/' . $fileName; // Ścieżka relatywna do zapisu w bazie
        }
    }

    $sql = "INSERT INTO personal_transactions 
            (user_id, category_id, amount, type, description, transaction_date, currency, exchange_rate, original_amount, receipt_path, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $user_id, $category_id, $amount, $type, $description, $date, 
        $currency, $exchange_rate, $original_amount, $receipt_path
    ]);

    echo json_encode(['success' => true, 'id' => $db->lastInsertId()]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>