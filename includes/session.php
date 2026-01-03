<?php
// includes/session.php

// 1. Bezpieczna konfiguracja i start sesji
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // ini_set('session.cookie_secure', 1); // Odkomentuj jeśli masz HTTPS
    
    session_start();
}

// 2. Obsługa wygasania sesji (30 minut)
$timeout_duration = 1800; 

if (isset($_SESSION['last_activity'])) {
    $duration = time() - $_SESSION['last_activity'];
    if ($duration > $timeout_duration) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_msg'] = "Sesja wygasła. Zaloguj się ponownie.";
        // Używamy ścieżki bezwzględnej lub relatywnej w zależności od konfiguracji
        header("Location: ../login.php"); 
        exit();
    }
}
$_SESSION['last_activity'] = time(); 

// 3. Token CSRF (Ochrona formularzy)
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}

// UWAGA: Funkcje checkLogin(), getCurrentUserId() zostały usunięte,
// ponieważ znajdują się teraz w pliku functions.php.
?>