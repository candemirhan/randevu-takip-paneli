<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'broker') {
    http_response_code(403);
    echo "Yetkisiz erişim.";
    exit;
}

$user = $_SESSION['user'];
$brokerId = $user['id'];
$consultantId = $_GET['consultant_id'] ?? '';
$date = $_GET['date'] ?? '';

if (!$consultantId || !$date) {
    echo "Geçersiz istek.";
    exit;
}

// Verify consultant exists
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ? AND role = 'consultant'");
$stmt->execute([$consultantId]);
$consultant = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$consultant) {
    echo "Danışman bulunamadı.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $details = $_POST['details'] ?? '';
    $time = $_POST['time'] ?? '';

    if (!$title || !$time) {
        $error = "Lütfen başlık ve saat giriniz.";
    } else {
        // Create appointment request
        $stmt = $pdo->prepare("INSERT INTO appointment_requests (broker_id, consultant_id, date, time, title, details) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$brokerId, $consultantId, $date, $time, $title, $details]);
        header("Location: appointments.php");
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Randevu Talebi - <?php echo htmlspecialchars($consultant['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-black text-white p-6 min-h-screen">
    <h1 class="text-2xl font-bold mb-4">Randevu Talebi</h1>
    <p class="mb-4">
        Danışman: <?php echo htmlspecialchars($consultant['username']); ?><br>
        Tarih: <?php echo htmlspecialchars($date); ?>
    </p>

    <?php if (isset($error)): ?>
        <div class="bg-red-700 p-2 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 max-w-md">
        <div>
            <label for="title" class="block mb-1">Randevu Başlığı</label>
            <input type="text" id="title" name="title" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
        </div>
        <div>
            <label for="time" class="block mb-1">Saat (HH:MM)</label>
            <input type="time" id="time" name="time" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
        </div>
        <div>
            <label for="details" class="block mb-1">Randevu Detayları</label>
            <textarea id="details" name="details" rows="4" class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600"></textarea>
        </div>
        <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold">Talep Gönder</button>
    </form>

    <div class="mt-6">
        <a href="appointments.php" class="text-red-600 hover:underline">Takvime Dön</a>
    </div>
</body>
</html>
