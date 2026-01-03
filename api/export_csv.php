<?php
// api/export_csv.php
require_once '../includes/functions.php';

if (!getCurrentUserId()) {
    redirect('../login.php');
}

$user_id = getCurrentUserId();
$db = db();

// Nagłówki HTTP wymuszające pobieranie pliku
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=transakcje_' . date('Y-m-d') . '.csv');

// Otwieramy strumień wyjściowy PHP
$output = fopen('php://output', 'w');

// BOM dla Excela (żeby polskie znaki działały)
fputs($output, "\xEF\xBB\xBF");

// Nagłówki kolumn w CSV
fputcsv($output, ['ID', 'Data', 'Typ', 'Kategoria', 'Kwota', 'Opis']);

// Pobieranie danych
$stmt = $db->prepare("SELECT id, transaction_date, type, category, amount, description FROM personal_transactions WHERE user_id = ? ORDER BY transaction_date DESC");
$stmt->execute([$user_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Tłumaczenie typu na polski
    $row['type'] = ($row['type'] == 'income') ? 'Przychód' : 'Wydatek';
    fputcsv($output, $row);
}

fclose($output);
exit();
?>