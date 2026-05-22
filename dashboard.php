<?php
require_once 'includes/session.php';
require_once 'config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$room_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM rooms WHERE " . active_villa_where_raw()))[0];
$reservation_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reservations WHERE " . active_villa_where_raw()))[0];
$customer_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM customers WHERE " . active_villa_where_raw()))[0];
$revenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(grand_total),0) FROM invoices WHERE " . active_villa_where_raw()))[0];
$pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reservations WHERE status='Pending' AND " . active_villa_where_raw()))[0];
$checked_in = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reservations WHERE status='Checked-In' AND " . active_villa_where_raw()))[0];

$recent = mysqli_query($conn, "SELECT 
    COALESCE(r.group_id, r.id) AS gid,
    GROUP_CONCAT(DISTINCT rm.room_number ORDER BY rm.room_number SEPARATOR ', ') AS room_numbers,
    GROUP_CONCAT(r.id ORDER BY rm.room_number) AS reservation_ids,
    r.group_id,
    MIN(r.check_in) AS check_in,
    MAX(r.check_out) AS check_out,
    MIN(r.status) AS status,
    MAX(c.full_name) AS full_name,
    COUNT(*) AS room_count
FROM reservations r 
JOIN customers c ON r.customer_id = c.id 
JOIN rooms rm ON r.room_id = rm.id 
WHERE " . active_villa_where('r') . "
GROUP BY COALESCE(r.group_id, r.id)
ORDER BY gid DESC LIMIT 5");

$today = date('Y-m-d');
$arrivals = mysqli_query($conn, "SELECT 
    COALESCE(r.group_id, r.id) AS gid,
    GROUP_CONCAT(DISTINCT rm.room_number ORDER BY rm.room_number SEPARATOR ', ') AS room_numbers,
    GROUP_CONCAT(r.id ORDER BY rm.room_number) AS reservation_ids,
    MIN(r.check_in) AS check_in,
    MAX(r.check_out) AS check_out,
    MIN(r.status) AS status,
    MAX(c.full_name) AS full_name,
    COUNT(*) AS room_count
FROM reservations r 
JOIN customers c ON r.customer_id = c.id 
JOIN rooms rm ON r.room_id = rm.id 
WHERE DATE(r.check_in) = '$today' AND r.status IN ('Pending', 'Confirmed') AND " . active_villa_where('r') . "
GROUP BY COALESCE(r.group_id, r.id)
ORDER BY check_in ASC");

$departures = mysqli_query($conn, "SELECT 
    COALESCE(r.group_id, r.id) AS gid,
    GROUP_CONCAT(DISTINCT rm.room_number ORDER BY rm.room_number SEPARATOR ', ') AS room_numbers,
    GROUP_CONCAT(r.id ORDER BY rm.room_number) AS reservation_ids,
    MIN(r.check_in) AS check_in,
    MAX(r.check_out) AS check_out,
    MIN(r.status) AS status,
    MAX(c.full_name) AS full_name,
    COUNT(*) AS room_count
FROM reservations r 
JOIN customers c ON r.customer_id = c.id 
JOIN rooms rm ON r.room_id = rm.id 
WHERE DATE(r.check_out) = '$today' AND r.status = 'Checked-In' AND " . active_villa_where('r') . "
GROUP BY COALESCE(r.group_id, r.id)
ORDER BY check_out ASC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 12px; border: 1px solid #e2e8f0;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="fw-semibold text-muted" style="font-size: 0.85rem;"><i class="bi bi-lightning-charge-fill text-warning me-1"></i> QUICK ACTIONS:</span>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if (has_role(['Admin', 'Receptionist'])): ?>
                            <a href="modules/reservations/create.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-circle"></i> New Reservation</a>
                            <?php endif; ?>
                            
                            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
                            <a href="modules/room_service/index.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-cart-plus"></i> Room Service</a>
                            <?php endif; ?>


                            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
                            <a href="modules/reports/index.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-graph-up"></i> Reports</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-building"></i></div>
                <div class="stat-value"><?= $room_count ?></div>
                <div class="stat-label">Total Rooms</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card success">
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                <div class="stat-value"><?= $reservation_count ?></div>
                <div class="stat-label">Reservations</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card info">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <div class="stat-value"><?= $customer_count ?></div>
                <div class="stat-label">Customers</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card warning">
                <div class="stat-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stat-value"><?= htmlspecialchars($global_currency) ?><?= number_format($revenue, 2) ?></div>
                <div class="stat-label">Revenue</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="stat-card danger">
                <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
                <div class="stat-value"><?= $pending ?></div>
                <div class="stat-label">Pending Reservations</div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="stat-value"><?= $checked_in ?></div>
                <div class="stat-label">Checked-In Guests</div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-graph-up"></i> Revenue Trend (Last 6 Months)</h5></div>
                <div class="card-body">
                    <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <!-- Today's Activity Card -->
        <div class="col-xl-7 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-calendar-event"></i> Today's Activity</h5>
                    <span class="badge bg-light text-dark fw-normal"><?= date('F d, Y') ?></span>
                </div>
                <div class="card-body p-0">
                    <ul class="nav nav-tabs border-bottom-0 px-3 pt-2" id="activityTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="arrivals-tab" data-bs-toggle="tab" data-bs-target="#arrivals-panel" type="button" role="tab" aria-selected="true" style="font-size: 0.85rem; font-weight: 600;">
                                Arrivals (Check-In) <span class="badge bg-warning bg-opacity-25 text-warning ms-1"><?= mysqli_num_rows($arrivals) ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="departures-tab" data-bs-toggle="tab" data-bs-target="#departures-panel" type="button" role="tab" aria-selected="false" style="font-size: 0.85rem; font-weight: 600;">
                                Departures (Check-Out) <span class="badge bg-secondary bg-opacity-25 text-secondary ms-1"><?= mysqli_num_rows($departures) ?></span>
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border-top">
                        <!-- Arrivals Tab Panel -->
                        <div class="tab-pane fade show active" id="arrivals-panel" role="tabpanel" aria-labelledby="arrivals-tab">
                            <?php if (mysqli_num_rows($arrivals) > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Guest / Room</th><th>Arrival Time</th><th>Status</th><th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($arr = mysqli_fetch_assoc($arrivals)):
                                            $first_id = (int)explode(',', $arr['reservation_ids'])[0];
                                        ?>
                                        <tr>
                                            <td>
                                                <div><strong><?= htmlspecialchars($arr['full_name']) ?></strong></div>
                                                <small class="text-muted"><span class="badge bg-light text-dark">Rm <?= htmlspecialchars($arr['room_numbers']) ?></span></small>
                                            </td>
                                            <td>
                                                <i class="bi bi-clock me-1 text-muted"></i> <?= date('h:i A', strtotime($arr['check_in'])) ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $arr['status'] == 'Confirmed' ? 'success' : 'warning' ?>"><?= $arr['status'] ?></span>
                                            </td>
                                            <td>
                                                <?php if (has_role(['Admin', 'Receptionist'])): ?>
                                                    <?php if ($arr['status'] == 'Pending'): ?>
                                                    <form method="POST" action="modules/reservations/status.php" class="d-inline" onsubmit="return confirm('Confirm reservation?');">
                                                        <input type="hidden" name="gid" value="<?= $arr['gid'] ?>">
                                                        <input type="hidden" name="status" value="Confirmed">
                                                        <button type="submit" class="btn btn-sm btn-success py-1 px-2" title="Confirm Booking" style="font-size: 0.75rem;"><i class="bi bi-check-circle"></i> Confirm</button>
                                                    </form>
                                                    <?php else: ?>
                                                    <form method="POST" action="modules/reservations/status.php" class="d-inline" onsubmit="return confirm('Check-In guest?');">
                                                        <input type="hidden" name="gid" value="<?= $arr['gid'] ?>">
                                                        <input type="hidden" name="status" value="Checked-In">
                                                        <button type="submit" class="btn btn-sm btn-info py-1 px-2 text-white" title="Check-In Guest" style="font-size: 0.75rem;"><i class="bi bi-box-arrow-in-right"></i> Check-In</button>
                                                    </form>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-check text-muted fs-2"></i>
                                <p class="text-muted mt-2 mb-0">No guest arrivals scheduled for today.</p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Departures Tab Panel -->
                        <div class="tab-pane fade" id="departures-panel" role="tabpanel" aria-labelledby="departures-tab">
                            <?php if (mysqli_num_rows($departures) > 0): ?>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Guest / Room</th><th>Departure Time</th><th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($dep = mysqli_fetch_assoc($departures)):
                                            $first_id = (int)explode(',', $dep['reservation_ids'])[0];
                                        ?>
                                        <tr>
                                            <td>
                                                <div><strong><?= htmlspecialchars($dep['full_name']) ?></strong></div>
                                                <small class="text-muted"><span class="badge bg-light text-dark">Rm <?= htmlspecialchars($dep['room_numbers']) ?></span></small>
                                            </td>
                                            <td>
                                                <i class="bi bi-clock me-1 text-muted"></i> <?= date('h:i A', strtotime($dep['check_out'])) ?>
                                            </td>
                                            <td>
                                                <?php if (has_role(['Admin', 'Receptionist'])): ?>
                                                <form method="POST" action="modules/reservations/status.php" class="d-inline" onsubmit="return confirm('Check-Out guest?');">
                                                    <input type="hidden" name="gid" value="<?= $dep['gid'] ?>">
                                                    <input type="hidden" name="status" value="Checked-Out">
                                                    <button type="submit" class="btn btn-sm btn-secondary py-1 px-2" title="Check-Out Guest" style="font-size: 0.75rem;"><i class="bi bi-box-arrow-right"></i> Check-Out</button>
                                                </form>
                                                <?php else: ?>
                                                -
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-x text-muted fs-2"></i>
                                <p class="text-muted mt-2 mb-0">No guest departures scheduled for today.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Card -->
        <div class="col-xl-5 col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Bookings</h5>
                    <a href="modules/reservations/index.php" class="btn btn-sm btn-link p-0 text-decoration-none" style="font-size: 0.8rem;">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Guest</th><th>Room</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($recent, 0);
                                while ($r = mysqli_fetch_assoc($recent)): 
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($r['full_name']) ?></div>
                                        <small class="text-muted" style="font-size: 0.75rem;">ID #<?= $r['gid'] ?></small>
                                    </td>
                                    <td>
                                        <?php if ($r['room_count'] > 1): ?>
                                            <span class="badge bg-light text-dark"><?= $r['room_count'] ?> Rms</span><br>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-dark">Rm <?= htmlspecialchars($r['room_numbers']) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $r['status'] == 'Confirmed' ? 'success' : ($r['status'] == 'Pending' ? 'warning' : ($r['status'] == 'Checked-In' ? 'info' : ($r['status'] == 'Checked-Out' ? 'secondary' : 'danger'))) ?>" style="font-size: 0.7rem;"><?= $r['status'] ?></span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$chart_query = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(grand_total) as total FROM invoices WHERE " . active_villa_where_raw() . " GROUP BY month ORDER BY created_at ASC LIMIT 6");
$months = [];
$totals = [];
while($row = mysqli_fetch_assoc($chart_query)) {
    $months[] = $row['month'];
    $totals[] = $row['total'];
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{
            label: 'Revenue (<?= htmlspecialchars($global_currency) ?>)',
            data: <?= json_encode($totals) ?>,
            borderColor: '#6366f1',
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            pointBackgroundColor: '#6366f1',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { 
                beginAtZero: true,
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>
<?php include 'includes/footer.php'; ?>
