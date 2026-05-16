<?php
require_once "../../includes/session.php";
require_once "../../config/database.php";

$month = isset($_GET['m']) ? (int)$_GET['m'] : date('m');
$year = isset($_GET['y']) ? (int)$_GET['y'] : date('Y');

$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$day_of_week = date('w', $first_day);

$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month == 0) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month == 13) {
    $next_month = 1;
    $next_year++;
}

$reservations = mysqli_query($conn, "SELECT r.*, c.full_name, rm.room_number 
                                     FROM reservations r 
                                     JOIN customers c ON r.customer_id = c.id 
                                     JOIN rooms rm ON r.room_id = rm.id 
                                     WHERE (MONTH(check_in) = $month AND YEAR(check_in) = $year)
                                     OR (MONTH(check_out) = $month AND YEAR(check_out) = $year)
                                     OR (check_in < '$year-$month-01' AND check_out > '$year-$month-$days_in_month')");

$calendar_events = [];
while ($r = mysqli_fetch_assoc($reservations)) {
    $calendar_events[] = $r;
}

include "../../includes/header.php";
include "../../includes/sidebar.php";
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-calendar3"></i> Booking Calendar</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Visual overview of reservations for <?= date('F Y', $first_day) ?></p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <a href="?m=<?= $prev_month ?>&y=<?= $prev_year ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i> Prev</a>
                <span class="mx-2 fw-bold" style="font-size: 1.1rem;"><?= date('F Y', $first_day) ?></span>
                <a href="?m=<?= $next_month ?>&y=<?= $next_year ?>" class="btn btn-outline-secondary btn-sm">Next <i class="bi bi-chevron-right"></i></a>
                <a href="index.php" class="btn btn-primary ms-2"><i class="bi bi-list-ul"></i> Back to List</a>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="table calendar-table mb-0">
            <thead>
                <tr class="text-center">
                    <th>Sun</th>
                    <th>Mon</th>
                    <th>Tue</th>
                    <th>Wed</th>
                    <th>Thu</th>
                    <th>Fri</th>
                    <th>Sat</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                <?php
                for ($i = 0; $i < $day_of_week; $i++) {
                    echo "<td class='bg-light'></td>";
                }

                for ($day = 1; $day <= $days_in_month; $day++) {
                    if (($i + $day - 1) % 7 == 0 && $day > 1) {
                        echo "</tr><tr>";
                    }
                    
                    $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $is_today = ($current_date == date('Y-m-d'));
                    
                    echo "<td class='" . ($is_today ? 'today' : '') . "'>";
                    echo "<div class='day-number'>" . ($is_today ? "<span>$day</span>" : $day) . "</div>";
                    
                    foreach ($calendar_events as $event) {
                        if ($current_date >= $event['check_in'] && $current_date < $event['check_out']) {
                            $status_class = 'confirmed';
                            if ($event['status'] == 'Pending') $status_class = 'pending';
                            if ($event['status'] == 'Checked-In') $status_class = 'checked-in';
                            if ($event['status'] == 'Cancelled') $status_class = 'cancelled';
                            
                            echo "<div class='calendar-event $status_class' onclick='location.href=\"edit.php?id={$event['id']}\"' title='" . htmlspecialchars($event['full_name']) . " - Room {$event['room_number']}'>";
                            echo "<i class='bi bi-door-closed me-1'></i>{$event['room_number']}: " . htmlspecialchars($event['full_name']);
                            echo "</div>";
                        }
                    }
                    
                    echo "</td>";
                }

                while (($i + $day - 1) % 7 != 0) {
                    echo "<td class='bg-light'></td>";
                    $i++;
                }
                ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php include "../../includes/footer.php"; ?>
