<?php
// api/transactions.php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Sprawdź czy użytkownik jest zalogowany (funkcja z session.php/functions.php)
if (!getCurrentUserId()) {
    http_response_code(401);
    echo json_encode(['error' => 'Nie jesteś zalogowany']);
    exit();
}

$db = db(); // Helper z db.php
$user_id = getCurrentUserId();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Pobieranie transakcji
        $type = $_GET['type'] ?? 'personal'; // personal lub business
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        
        $table = ($type === 'business') ? 'business_transactions' : 'personal_transactions';
        
        // Zabezpieczenie przed SQL injection w nazwie tabeli (whitelist)
        if (!in_array($table, ['personal_transactions', 'business_transactions'])) {
            throw new Exception("Nieprawidłowy typ transakcji");
        }

        // Jeśli podano ID, pobierz jedną
        if (isset($_GET['id'])) {
            $sql = "SELECT * FROM $table WHERE id = ? AND user_id = ?";
            $data = $db->selectOne($sql, [$_GET['id'], $user_id]);
        } else {
            // Pobierz listę
            $sql = "SELECT * FROM $table WHERE user_id = ? ORDER BY transaction_date DESC, created_at DESC LIMIT $limit";
            $data = $db->select($sql, [$user_id]);
        }

        echo json_encode($data);

    } elseif ($method === 'POST') {
        // Dodawanie transakcji
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception("Brak danych wejściowych");
        }

        $type = $input['type'] ?? 'expense'; // income lub expense
        $category = sanitizeInput($input['category']);
        $amount = (float)$input['amount'];
        $description = sanitizeInput($input['description']);
        $date = sanitizeInput($input['transaction_date']);
        
        // Walidacja
        if ($amount <= 0) throw new Exception("Kwota musi być dodatnia");
        if (empty($description)) throw new Exception("Opis jest wymagany");
        
        // Decyzja do której tabeli zapisać
        // Tutaj proste założenie: formularz wysyła parametr context lub domyślnie personal
        // W twoim formularzu brakuje wyboru kontekstu (Firma/Osobowe), domyślnie leci do personal
        $table = 'personal_transactions'; 
        
        $sql = "INSERT INTO $table (user_id, amount, category, type, description, transaction_date) VALUES (?, ?, ?, ?, ?, ?)";
        
        $newId = $db->insert($sql, [$user_id, $amount, $category, $type, $description, $date]);
        
        echo json_encode(['success' => true, 'id' => $newId]);

    } elseif ($method === 'DELETE') {
        // Usuwanie
        $id = $_GET['id'] ?? null;
        $contextType = $_GET['type'] ?? 'personal'; // personal / business
        
        if (!$id) throw new Exception("Brak ID");
        
        $table = ($contextType === 'business') ? 'business_transactions' : 'personal_transactions';
        
        $sql = "DELETE FROM $table WHERE id = ? AND user_id = ?";
        $rows = $db->delete($sql, [$id, $user_id]);
        
        if ($rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Nie znaleziono transakcji lub brak uprawnień");
        }
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage(), 'success' => false]);
}
?>