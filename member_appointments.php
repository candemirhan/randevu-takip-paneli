<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo "Yetkisiz erişim.";
    exit;
}

$user = $_SESSION['user'];
$viewerId = $user['id'];
$viewerRole = $user['role'];

$memberId = $_GET['user_id'] ?? '';
if (!$memberId) {
    echo "Kullanıcı seçilmedi.";
    exit;
}

// Get member details
$stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    echo "Kullanıcı bulunamadı.";
    exit;
}

// Check permissions
$canViewAppointments = false;
if ($viewerRole === 'admin') {
    $canViewAppointments = true;
} elseif ($viewerRole === 'broker' && $member['role'] === 'consultant') {
    $canViewAppointments = true;
} elseif ($viewerId == $memberId) {
    $canViewAppointments = true;
}

if (!$canViewAppointments) {
    echo "Bu kullanıcının randevularını görüntüleme yetkiniz yok.";
    exit;
}

// Get current month and year or from query params
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Fetch appointments
$stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date, time");
$stmt->execute([$memberId, $month, $year]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

function turkishMonthName($month) {
    $months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    return $months[$month - 1] ?? '';
}

?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($member['username']); ?> - Randevular</h2>
        <p class="text-gray-400">Rol: <?php echo htmlspecialchars($member['role']); ?></p>
    </div>

    <?php if ($viewerRole === 'broker' && $member['role'] === 'consultant'): ?>
    <div class="mb-6">
        <button onclick="showRequestForm()" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold transition">
            Randevu Talebi Oluştur
        </button>
    </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-4">
        <button onclick="changeMonth(-1)" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">< Önceki</button>
        <h3 class="text-xl font-semibold"><?php echo turkishMonthName($month) . " $year"; ?></h3>
        <button onclick="changeMonth(1)" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Sonraki ></button>
    </div>

    <div class="space-y-4">
        <?php if (empty($appointments)): ?>
            <p>Bu ay için randevu bulunmuyor.</p>
        <?php else: ?>
            <?php 
            $currentDate = '';
            foreach ($appointments as $appt):
                if ($currentDate !== $appt['date']):
                    if ($currentDate !== '') echo '</div>';
                    $currentDate = $appt['date'];
                    echo '<div class="border-t border-gray-700 pt-4 mt-4">';
                    echo '<h4 class="font-semibold mb-2">' . date('d', strtotime($currentDate)) . ' ' . turkishMonthName(date('n', strtotime($currentDate))) . '</h4>';
                endif;
            ?>
                <div class="p-3 rounded border border-gray-700 bg-gray-900">
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="font-semibold" style="color: <?php echo htmlspecialchars($appt['label_color']); ?>">
                                <?php echo htmlspecialchars($appt['title']); ?>
                            </div>
                            <div class="text-sm text-gray-400">
                                Saat: <?php echo htmlspecialchars(substr($appt['time'], 0, 5)); ?>
                            </div>
                            <?php if ($appt['details']): ?>
                                <div class="text-sm mt-2"><?php echo nl2br(htmlspecialchars($appt['details'])); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($viewerRole === 'broker' && $member['role'] === 'consultant'): ?>
<!-- Appointment Request Modal -->
<div id="requestModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4">
    <div class="bg-gray-900 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl font-bold mb-4">Randevu Talebi Oluştur</h3>
        <form action="appointment_request.php" method="GET" class="space-y-4">
            <input type="hidden" name="consultant_id" value="<?php echo $memberId; ?>" />
            <div>
                <label class="block mb-1">Tarih</label>
                <input type="date" name="date" required class="w-full p-2 rounded bg-gray-800 border border-gray-700 focus:outline-none focus:border-red-600" />
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="hideRequestForm()" class="px-4 py-2 rounded border border-gray-700 hover:bg-gray-800">İptal</button>
                <button type="submit" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded font-semibold">Devam</button>
            </div>
        </form>
    </div>
</div>

<script>
function showRequestForm() {
    document.getElementById('requestModal').classList.remove('hidden');
}

function hideRequestForm() {
    document.getElementById('requestModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('requestModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideRequestForm();
    }
});
</script>
<?php endif; ?>

<script>
function changeMonth(delta) {
    let month = <?php echo $month; ?> + delta;
    let year = <?php echo $year; ?>;
    
    if (month < 1) {
        month = 12;
        year--;
    } else if (month > 12) {
        month = 1;
        year++;
    }
    
    window.location.href = `member_appointments.php?user_id=<?php echo $memberId; ?>&month=${month}&year=${year}`;
}
</script>
