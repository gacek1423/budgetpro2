<?php
// admin/impersonate.php
session_start();
require_once '../includes/db.php';

// 1. Zabezpieczenie: Tylko admin może to zrobić
if (!isset($_SESSION['user_id'])) die("Brak dostępu");

$db = db();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();

if ($admin['role'] !== 'admin') die("Nie jesteś adminem!");

// 2. Logika przełączania
if (isset($_GET['id'])) {
    $targetId = (int)$_GET['id'];
    
    // Pobierz dane celu
    $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();
    
    if ($targetUser) {
        // Zapisz sesję admina na później (opcjonalnie, tu upraszczamy - admin po prostu "staje się" userem)
        $_SESSION['original_admin_id'] = $_SESSION['user_id']; // Zapamiętujemy, że byliśmy adminem
        
        // Nadpisujemy sesję
        $_SESSION['user_id'] = $targetUser['id'];
        $_SESSION['username'] = $targetUser['username'];
        $_SESSION['logged_in'] = true;
        
        // Przekierowanie do dashboardu usera
        header("Location: ../pages/dashboard.php");
        exit;
    }
}
header("Location: users.php");