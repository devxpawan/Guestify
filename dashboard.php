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
    <h2 class="mb-4">Dashboard</h2>
    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= $room_count ?></h5>
                    <p>Total Rooms</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= $reservation_count ?></h5>
                    <p>Total Reservations</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= $customer_count ?></h5>
                    <p>Total Customers</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">$<?= number_format($revenue, 2) ?></h5>
                    <p>Total Revenue</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-2">
        <div class="col-md-6 mb-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= $pending ?></h5>
                    <p>Pending Reservations</p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card bg-secondary text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= $checked_in ?></h5>
                    <p>Checked-In Guests</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h5>Revenue Trend (Last 6 Months)</h5></div>
                <div class="card-body">
                    <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header"><h5>Recent Reservations</h5></div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>#</th><th>Customer</th><th>Room</th><th>Check-In</th><th>Check-Out</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($recent)): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= $r['room_number'] ?></td>
                        <td><?= $r['check_in'] ?></td>
                        <td><?= $r['check_out'] ?></td>
                        <td><span class="badge bg-<?= $r['status'] == 'Confirmed' ? 'success' : ($r['status'] == 'Pending' ? 'warning' : ($r['status'] == 'Checked-In' ? 'info' : 'secondary')) ?>"><?= $r['status'] ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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
            label: 'Revenue ($)',
            data: <?= json_encode($totals) ?>,
            borderColor: '#0d6efd',
            tension: 0.1,
            fill: true,
            backgroundColor: 'rgba(13, 110, 253, 0.1)'
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
<?php include 'includes/footer.php'; ?>
