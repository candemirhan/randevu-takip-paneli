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
$role = $user['role'];

$date = $_GET['date'] ?? '';
if (!$date) {
    echo "Geçersiz tarih.";
    exit;
}

// Handle form submission for creating or deleting appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $title = $_POST['title'] ?? '';
        $details = $_POST['details'] ?? '';
        $label_color = $_POST['label_color'] ?? '#ef4444'; // default red-500
        $time = $_POST['time'] ?? '';

        if (!$title || !$time) {
            $error = "Lütfen başlık ve saat giriniz.";
        } else {
            // Insert appointment
            $stmt = $pdo->prepare("INSERT INTO appointments (user_id, date, time, label_color, title, details, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $date, $time, $label_color, $title, $details, $userId]);
            header("Location: appointments.php");
            exit;
        }
    } elseif (isset($_POST['delete'])) {
        $apptId = $_POST['appointment_id'] ?? 0;
        if ($apptId) {
            // Check ownership or admin
            $stmt = $pdo->prepare("SELECT user_id FROM appointments WHERE id = ?");
            $stmt->execute([$apptId]);
            $appt = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($appt && ($appt['user_id'] == $userId || $role === 'admin')) {
                $stmtDel = $pdo->prepare("DELETE FROM appointments WHERE id = ?");
                $stmtDel->execute([$apptId]);
                header("Location: appointments.php");
                exit;
            } else {
                $error = "Randevu silme yetkiniz yok.";
            }
        }
    }
}

// Fetch appointments for the date and user
if ($role === 'consultant') {
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? AND date = ?");
    $stmt->execute([$userId, $date]);
} elseif ($role === 'broker') {
    // Broker sees own and consultants' appointments
    $stmtCons = $pdo->prepare("SELECT id FROM users WHERE role = 'consultant'");
    $stmtCons->execute();
    $consultants = $stmtCons->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_merge([$userId], $consultants);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, [$date]);
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id IN ($placeholders) AND date = ?");
    $stmt->execute($params);
} else {
    // Admin sees all
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE date = ?");
    $stmt->execute([$date]);
}

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Randevu Detayları - <?php echo htmlspecialchars($date); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.2/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-black text-white p-6 min-h-screen">
    <h1 class="text-2xl font-bold mb-4">Randevular - <?php echo htmlspecialchars($date); ?></h1>

    <?php if (isset($error)): ?>
        <div class="bg-red-700 p-2 mb-4 rounded"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="mb-6">
        <h2 class="text-xl font-semibold mb-2">Randevular</h2>
        <?php if (count($appointments) === 0): ?>
            <p>Bu tarihte randevu yok.</p>
        <?php else: ?>
            <ul class="space-y-2">
                <?php foreach ($appointments as $appt): ?>
                    <li class="p-3 rounded border border-gray-700 bg-gray-900 flex justify-between items-center">
                        <div>
                            <div class="font-semibold" style="color: <?php echo htmlspecialchars($appt['label_color']); ?>">
                                <?php echo htmlspecialchars($appt['title']); ?> (<?php echo htmlspecialchars(substr($appt['time'], 0, 5)); ?>)
                            </div>
                            <div class="text-sm"><?php echo nl2br(htmlspecialchars($appt['details'])); ?></div>
                        </div>
                        <?php if ($appt['user_id'] == $userId || $role === 'admin'): ?>
                            <form method="POST" onsubmit="return confirm('Randevuyu silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="appointment_id" value="<?php echo $appt['id']; ?>" />
                                <button type="submit" name="delete" class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded">Sil</button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div>
        <h2 class="text-xl font-semibold mb-2">Yeni Randevu Oluştur</h2>
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
                <label for="label_color" class="block mb-1">Etiket Rengi</label>
                <input type="color" id="label_color" name="label_color" value="#ef4444" class="w-16 h-8 p-0 border-0 rounded" />
            </div>
            <div>
                <label for="details" class="block mb-1">Randevu Detayları</label>
                <textarea id="details" name="details" rows="4" class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600"></textarea>
            </div>
            <button type="submit" name="create" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold">Oluştur</button>
        </form>
    </div>

    <div class="mt-6">
        <a href="appointments.php" class="text-red-600 hover:underline">Takvime Dön</a>
    </div>
</body>
</html>
