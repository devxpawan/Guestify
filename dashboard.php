<?php
require_once 'includes/session.php';
require_once 'config/database.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$room_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM rooms"))[0];
$reservation_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reservations"))[0];
$customer_count = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM customers"))[0];
$revenue = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(grand_total),0) FROM invoices"))[0];
$pending = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reservations WHERE status='Pending'"))[0];
$checked_in = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM reservations WHERE status='Checked-In'"))[0];

$recent = mysqli_query($conn, "SELECT r.id, c.full_name, rm.room_number, r.check_in, r.check_out, r.status 
                               FROM reservations r 
                               JOIN customers c ON r.customer_id = c.id 
                               JOIN rooms rm ON r.room_id = rm.id 
                               ORDER BY r.id DESC LIMIT 5");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="mb-0"><i class="bi bi-grid-1x2"></i> Overview</h4>
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

    <div class="card mt-4">
        <div class="card-header"><h5><i class="bi bi-clock"></i> Recent Reservations</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th><th>Customer</th><th>Room</th><th>Check-In</th><th>Check-Out</th><th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = mysqli_fetch_assoc($recent)): ?>
                        <tr>
                            <td><strong><?= $r['id'] ?></strong></td>
                            <td><?= htmlspecialchars($r['full_name']) ?></td>
                            <td><?= $r['room_number'] ?></td>
                            <td><?= $r['check_in'] ?></td>
                            <td><?= $r['check_out'] ?></td>
                            <td><span class="badge badge-<?= $r['status'] == 'Confirmed' ? 'success' : ($r['status'] == 'Pending' ? 'warning' : ($r['status'] == 'Checked-In' ? 'info' : 'secondary')) ?>"><?= $r['status'] ?></span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$chart_query = mysqli_query($conn, "SELECT DATE_FORMAT(created_at, '%b %Y') as month, SUM(grand_total) as total FROM invoices GROUP BY month ORDER BY created_at ASC LIMIT 6");
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
