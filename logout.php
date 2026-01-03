<?php
// logout.php
require_once 'includes/session.php'; // Musi mieć dostęp do funkcji logout()

logout(); // Funkcja z session.php
header("Location: login.php");
exit();
?>