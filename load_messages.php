<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim']);
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

try {
    // Get messages for this user (sent to all or specifically to this user)
    $stmt = $pdo->prepare("
        SELECT 
            m.*, 
            u.username as sender_name,
            r.username as receiver_name
        FROM chat_messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN users r ON m.receiver_id = r.id
        WHERE m.receiver_id IS NULL OR m.receiver_id = ? OR m.sender_id = ?
        ORDER BY m.created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$userId, $userId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all users for the recipient dropdown
    $stmtUsers = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? ORDER BY username");
    $stmtUsers->execute([$userId]);
    $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'messages' => array_map(function($msg) {
            return [
                'id' => $msg['id'],
                'sender' => $msg['sender_name'],
                'receiver' => $msg['receiver_name'],
                'message' => $msg['message'],
                'time' => date('H:i', strtotime($msg['created_at'])),
                'date' => date('Y-m-d', strtotime($msg['created_at']))
            ];
        }, array_reverse($messages)),
        'users' => $users
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Mesajlar yüklenemedi']);
}
