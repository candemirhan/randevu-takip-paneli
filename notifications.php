<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "Yetkisiz erişim.";
    exit;
}

$user = $_SESSION['user'];
$userId = $user['id'];

// Handle request actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = $_POST['request_id'] ?? 0;
    $action = $_POST['action'] ?? '';

    if ($requestId && ($action === 'approve' || $action === 'reject')) {
        // Verify request belongs to this consultant
        $stmt = $pdo->prepare("SELECT * FROM appointment_requests WHERE id = ? AND consultant_id = ? AND status = 'pending'");
        $stmt->execute([$requestId, $userId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($request) {
            if ($action === 'approve') {
                // Start transaction
                $pdo->beginTransaction();
                try {
                    // Update request status
                    $stmt = $pdo->prepare("UPDATE appointment_requests SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$requestId]);

                    // Create appointments for both broker and consultant
                    $stmt = $pdo->prepare("INSERT INTO appointments (user_id, date, time, label_color, title, details, created_by, is_shared) VALUES (?, ?, ?, ?, ?, ?, ?, true)");
                    
                    // Broker's appointment
                    $stmt->execute([
                        $request['broker_id'],
                        $request['date'],
                        $request['time'],
                        '#ef4444', // red-500
                        $request['title'],
                        $request['details'],
                        $userId
                    ]);

                    // Consultant's appointment
                    $stmt->execute([
                        $userId,
                        $request['date'],
                        $request['time'],
                        '#ef4444', // red-500
                        $request['title'],
                        $request['details'],
                        $userId
                    ]);

                    $pdo->commit();
                    $success = "Randevu talebi onaylandı.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Bir hata oluştu: " . $e->getMessage();
                }
            } else {
                // Reject request
                $stmt = $pdo->prepare("UPDATE appointment_requests SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$requestId]);
                $success = "Randevu talebi reddedildi.";
            }
        } else {
            $error = "Geçersiz randevu talebi.";
        }
    }
}

// Get pending requests for consultant
$stmt = $pdo->prepare("
    SELECT ar.*, u.username as broker_name 
    FROM appointment_requests ar 
    JOIN users u ON ar.broker_id = u.id 
    WHERE ar.consultant_id = ? AND ar.status = 'pending' 
    ORDER BY ar.created_at DESC
");
$stmt->execute([$userId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bildirimler - Randevu Talepleri</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-black text-white p-6 min-h-screen">
    <h1 class="text-2xl font-bold mb-4">Randevu Talepleri</h1>

    <?php if (isset($success)): ?>
        <div class="bg-green-700 p-2 mb-4 rounded"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="bg-red-700 p-2 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($requests)): ?>
        <p>Bekleyen randevu talebi yok.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($requests as $request): ?>
                <div class="p-4 rounded border border-gray-700 bg-gray-900">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="font-semibold"><?php echo htmlspecialchars($request['title']); ?></h3>
                            <p class="text-sm text-gray-400">
                                Broker: <?php echo htmlspecialchars($request['broker_name']); ?><br>
                                Tarih: <?php echo htmlspecialchars($request['date']); ?><br>
                                Saat: <?php echo htmlspecialchars(substr($request['time'], 0, 5)); ?>
                            </p>
                        </div>
                        <div class="flex space-x-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>" />
                                <button type="submit" name="action" value="approve" class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded">Onayla</button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>" />
                                <button type="submit" name="action" value="reject" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Reddet</button>
                            </form>
                        </div>
                    </div>
                    <?php if ($request['details']): ?>
                        <p class="text-sm mt-2"><?php echo nl2br(htmlspecialchars($request['details'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="mt-6">
        <a href="dashboard.php" class="text-red-600 hover:underline">Panele Dön</a>
    </div>
</body>
</html>
