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

// Get current month and year or from query params
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Calculate first day of month and number of days
$firstDayOfMonth = strtotime("$year-$month-01");
$daysInMonth = date('t', $firstDayOfMonth);
$startDayOfWeek = date('N', $firstDayOfMonth); // 1 (Mon) to 7 (Sun)

// Fetch appointments based on role
if ($role === 'consultant') {
    // Consultant sees only own appointments
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id = ? AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$userId, $month, $year]);
} elseif ($role === 'broker') {
    // Broker sees own and consultants' appointments
    // Get consultant ids
    $stmtCons = $pdo->prepare("SELECT id FROM users WHERE role = 'consultant'");
    $stmtCons->execute();
    $consultants = $stmtCons->fetchAll(PDO::FETCH_COLUMN);
    $ids = array_merge([$userId], $consultants);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $params = array_merge($ids, [$month, $year]);
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE user_id IN ($placeholders) AND MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute($params);
} else {
    // Admin sees all appointments
    $stmt = $pdo->prepare("SELECT * FROM appointments WHERE MONTH(date) = ? AND YEAR(date) = ?");
    $stmt->execute([$month, $year]);
}

$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Helper function to get appointments by day
$appointmentsByDay = [];
foreach ($appointments as $appt) {
    $day = date('j', strtotime($appt['date']));
    if (!isset($appointmentsByDay[$day])) {
        $appointmentsByDay[$day] = [];
    }
    $appointmentsByDay[$day][] = $appt;
}

function turkishMonthName($month) {
    $months = ['Ocak','Şubat','Mart','Nisan','Mayıs','Haziran','Temmuz','Ağustos','Eylül','Ekim','Kasım','Aralık'];
    return $months[$month - 1] ?? '';
}

?>

<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-4">
        <button id="prevMonth" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">< Önceki</button>
        <h2 class="text-xl font-semibold"><?php echo turkishMonthName($month) . " $year"; ?></h2>
        <button id="nextMonth" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded">Sonraki ></button>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center text-sm">
        <div>Pzt</div><div>Sal</div><div>Çar</div><div>Per</div><div>Cum</div><div>Cmt</div><div>Paz</div>
    </div>
    <div class="grid grid-cols-7 gap-1 text-center">
        <?php
        // Empty cells before first day
        for ($i = 1; $i < $startDayOfWeek; $i++) {
            echo '<div class="p-4 border border-gray-700 bg-gray-900"></div>';
        }
        // Days
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $hasAppt = isset($appointmentsByDay[$day]);
            $apptCount = $hasAppt ? count($appointmentsByDay[$day]) : 0;
            echo '<div class="p-2 border border-gray-700 cursor-pointer bg-gray-800 hover:bg-red-700 rounded" data-day="' . $day . '">';
            echo '<div class="font-semibold">' . $day . '</div>';
            if ($hasAppt) {
                echo '<div class="text-xs mt-1">' . $apptCount . ' randevu</div>';
            }
            echo '</div>';
        }
        // Empty cells after last day
        $totalCells = $daysInMonth + $startDayOfWeek - 1;
        $emptyCells = 7 - ($totalCells % 7);
        if ($emptyCells < 7) {
            for ($i = 0; $i < $emptyCells; $i++) {
                echo '<div class="p-4 border border-gray-700 bg-gray-900"></div>';
            }
        }
        ?>
    </div>
</div>

<script>
    const currentMonth = <?php echo $month; ?>;
    const currentYear = <?php echo $year; ?>;

    document.getElementById('prevMonth').addEventListener('click', () => {
        let month = currentMonth - 1;
        let year = currentYear;
        if (month < 1) {
            month = 12;
            year--;
        }
        window.location.href = 'appointments.php?month=' + month + '&year=' + year;
    });

    document.getElementById('nextMonth').addEventListener('click', () => {
        let month = currentMonth + 1;
        let year = currentYear;
        if (month > 12) {
            month = 1;
            year++;
        }
        window.location.href = 'appointments.php?month=' + month + '&year=' + year;
    });

    document.querySelectorAll('[data-day]').forEach(cell => {
        cell.addEventListener('click', () => {
            const day = cell.getAttribute('data-day');
            const dateStr = currentYear + '-' + String(currentMonth).padStart(2, '0') + '-' + String(day).padStart(2, '0');
            // Open appointment modal or page for this date
            // Redirect to appointment detail page for the selected date
            window.location.href = 'appointment_detail.php?date=' + dateStr;
        });
    });
</script>
