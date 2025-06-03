<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$user = $_SESSION['user'];
$senderId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = $_POST['message'] ?? '';
    $receiverId = $_POST['receiver_id'] ?? null;

    if (!$message) {
        echo json_encode(['success' => false, 'error' => 'Mesaj boş olamaz']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$senderId, $receiverId, $message]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Mesaj gönderilemedi']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Geçersiz istek metodu']);
}
