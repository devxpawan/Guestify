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
    <div class="d-flex justify-content-between mb-3">
        <h2>Booking Calendar</h2>
        <div>
            <a href="?m=<?= $prev_month ?>&y=<?= $prev_year ?>" class="btn btn-outline-secondary btn-sm">&lt; Prev</a>
            <span class="mx-3 fw-bold"><?= date('F Y', $first_day) ?></span>
            <a href="?m=<?= $next_month ?>&y=<?= $next_year ?>" class="btn btn-outline-secondary btn-sm">Next &gt;</a>
        </div>
        <a href="index.php" class="btn btn-primary">Back to List</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered mb-0 calendar-table">
                <thead>
                    <tr class="bg-light text-center">
                        <th style="width: 14.28%">Sun</th>
                        <th style="width: 14.28%">Mon</th>
                        <th style="width: 14.28%">Tue</th>
                        <th style="width: 14.28%">Wed</th>
                        <th style="width: 14.28%">Thu</th>
                        <th style="width: 14.28%">Fri</th>
                        <th style="width: 14.28%">Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                    <?php
                    for ($i = 0; $i < $day_of_week; $i++) {
                        echo "<td></td>";
                    }

                    for ($day = 1; $day <= $days_in_month; $day++) {
                        if (($i + $day - 1) % 7 == 0 && $day > 1) {
                            echo "</tr><tr>";
                        }
                        
                        $current_date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $is_today = ($current_date == date('Y-m-d')) ? 'bg-info bg-opacity-10' : '';
                        
                        echo "<td class='$is_today' style='height: 120px; vertical-align: top;'>";
                        echo "<div class='fw-bold mb-1'>$day</div>";
                        
                        foreach ($calendar_events as $event) {
                            if ($current_date >= $event['check_in'] && $current_date < $event['check_out']) {
                                $status_class = 'bg-primary';
                                if ($event['status'] == 'Pending') $status_class = 'bg-warning';
                                if ($event['status'] == 'Checked-In') $status_class = 'bg-info';
                                if ($event['status'] == 'Cancelled') $status_class = 'bg-danger';
                                
                                echo "<div class='badge $status_class d-block text-start mb-1 overflow-hidden' style='font-size: 10px; cursor: pointer;' onclick='location.href=\"edit.php?id={$event['id']}\"'>";
                                echo "Rm {$event['room_number']}: " . htmlspecialchars($event['full_name']);
                                echo "</div>";
                            }
                        }
                        
                        echo "</td>";
                    }

                    while (($i + $day - 1) % 7 != 0) {
                        echo "<td></td>";
                        $i++;
                    }
                    ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .calendar-table td { border: 1px solid #dee2e6; }
    .calendar-table thead th { padding: 10px; }
</style>

<?php include "../../includes/footer.php"; ?>
