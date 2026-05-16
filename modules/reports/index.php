<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager', 'Receptionist'])) {
    header('Location: ../../dashboard.php');
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$daily = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total, COALESCE(SUM(i.grand_total),0) as revenue FROM reservations r LEFT JOIN invoices i ON r.id = i.reservation_id WHERE r.created_at >= CURDATE()"));
$monthly_res = mysqli_query($conn, "SELECT DATE_FORMAT(r.created_at, '%Y-%m') as month, COUNT(*) as total, COALESCE(SUM(i.grand_total),0) as revenue FROM reservations r LEFT JOIN invoices i ON r.id = i.reservation_id GROUP BY month ORDER BY month DESC LIMIT 6");
$occupancy = mysqli_query($conn, "SELECT rm.room_number, t.type_name, rm.status FROM rooms rm JOIN room_types t ON rm.room_type_id = t.id");
$top_customers = mysqli_query($conn, "SELECT c.full_name, COUNT(r.id) as bookings, COALESCE(SUM(i.grand_total),0) as spent FROM customers c LEFT JOIN reservations r ON c.id = r.customer_id LEFT JOIN invoices i ON r.id = i.reservation_id GROUP BY c.id ORDER BY spent DESC LIMIT 5");
$staff_report = mysqli_query($conn, "SELECT position, COUNT(*) as total, SUM(salary) as total_salary FROM staff GROUP BY position");
$product_sales = mysqli_query($conn, "SELECT item_name, SUM(quantity) as qty, SUM(total) as total FROM invoice_items WHERE item_type='Product' GROUP BY item_name ORDER BY total DESC LIMIT 5");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2 class="mb-4">Reports</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Today's Summary</h5></div>
                <div class="card-body">
                    <p><strong>Reservations Today:</strong> <?= $daily['total'] ?></p>
                    <p><strong>Revenue Today:</strong> $<?= number_format($daily['revenue'], 2) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Room Occupancy</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Room</th><th>Type</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php while ($o = mysqli_fetch_assoc($occupancy)): ?>
                            <tr>
                                <td><?= $o['room_number'] ?></td>
                                <td><?= $o['type_name'] ?></td>
                                <td><span class="badge bg-<?= $o['status'] == 'Available' ? 'success' : ($o['status'] == 'Occupied' ? 'danger' : 'warning') ?>"><?= $o['status'] ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Monthly Revenue</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Month</th><th>Reservations</th><th>Revenue</th></tr></thead>
                        <tbody>
                            <?php while ($m = mysqli_fetch_assoc($monthly_res)): ?>
                            <tr>
                                <td><?= $m['month'] ?></td>
                                <td><?= $m['total'] ?></td>
                                <td>$<?= number_format($m['revenue'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Top Customers</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Customer</th><th>Bookings</th><th>Total Spent</th></tr></thead>
                        <tbody>
                            <?php while ($tc = mysqli_fetch_assoc($top_customers)): ?>
                            <tr>
                                <td><?= htmlspecialchars($tc['full_name']) ?></td>
                                <td><?= $tc['bookings'] ?></td>
                                <td>$<?= number_format($tc['spent'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Staff Summary</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Position</th><th>Count</th><th>Total Salary</th></tr></thead>
                        <tbody>
                            <?php while ($sr = mysqli_fetch_assoc($staff_report)): ?>
                            <tr>
                                <td><?= htmlspecialchars($sr['position']) ?></td>
                                <td><?= $sr['total'] ?></td>
                                <td>$<?= number_format($sr['total_salary'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Top Selling Products</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead><tr><th>Product</th><th>Qty Sold</th><th>Total</th></tr></thead>
                        <tbody>
                            <?php while ($ps = mysqli_fetch_assoc($product_sales)): ?>
                            <tr>
                                <td><?= htmlspecialchars($ps['item_name']) ?></td>
                                <td><?= $ps['qty'] ?></td>
                                <td>$<?= number_format($ps['total'], 2) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
