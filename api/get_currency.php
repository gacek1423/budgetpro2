<?php
// api/get_currency.php
header('Content-Type: application/json');

$code = $_GET['code'] ?? 'EUR';
if ($code === 'PLN') {
    echo json_encode(['rate' => 1.0]);
    exit;
}

// Pobieramy dane z NBP (format JSON)
$url = "http://api.nbp.pl/api/exchangerates/rates/a/$code/?format=json";
$json = @file_get_contents($url);

if ($json) {
    $data = json_decode($json, true);
    $rate = $data['rates'][0]['mid']; // Średni kurs
    echo json_encode(['rate' => $rate]);
} else {
    echo json_encode(['error' => 'Nie znaleziono kursu']);
}
?>