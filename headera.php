<?php
// D:\laragon\www\budgetpro\includes\header.php
// PIERWSZA LINIA - DODAJ TO:

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Zapisz błąd do pliku
function logError($msg) {
    file_put_contents(__DIR__ . '/../logs/header_error.log', 
        date('Y-m-d H:i:s') . " - $msg\n", 
        FILE_APPEND);
}

// Użyj try-catch dla całego pliku
try {
    // CAŁA ZAWARTOŚĆ header.php tutaj...
    
} catch (Exception $e) {
    logError("Header error: " . $e->getMessage());
    die("Header Error: " . $e->getMessage());
}