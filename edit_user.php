<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo "Yetkisiz erişim.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? '';
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!$userId) {
        echo "Kullanıcı seçilmedi.";
        exit;
    }

    if ($action === 'change_password') {
        if (!$password) {
            echo "Yeni şifre giriniz.";
            exit;
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        header('Location: dashboard.php');
        exit;
    } elseif ($action === 'delete_user') {
        // Prevent deleting admin
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && $user['role'] === 'admin') {
            echo "Admin kullanıcı silinemez.";
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        header('Location: dashboard.php');
        exit;
    } else {
        echo "Geçersiz işlem.";
    }
} else {
    echo "Geçersiz istek.";
}
