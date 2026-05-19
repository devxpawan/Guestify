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
    <div class="page-header">
        <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Comprehensive business insights and performance metrics</p>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="stat-card primary">
                <div class="stat-icon"><i class="bi bi-calendar-day"></i></div>
                <div class="stat-value"><?= $daily['total'] ?></div>
                <div class="stat-label">Reservations Today</div>
                <hr>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted" style="font-size: 0.85rem;">Revenue Today</span>
                    <strong class="text-primary" style="font-size: 1.25rem;"><?= htmlspecialchars($global_currency) ?><?= number_format($daily['revenue'], 2) ?></strong>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-door-open"></i> Room Occupancy</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Room</th><th>Type</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php while ($o = mysqli_fetch_assoc($occupancy)): ?>
                                <tr>
                                    <td><strong><?= $o['room_number'] ?></strong></td>
                                    <td><?= $o['type_name'] ?></td>
                                    <td><span class="badge badge-<?= $o['status'] == 'Available' ? 'success' : ($o['status'] == 'Occupied' ? 'danger' : 'warning') ?>"><?= $o['status'] ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-cash-stack"></i> Monthly Revenue</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Month</th><th>Reservations</th><th>Revenue</th></tr></thead>
                            <tbody>
                                <?php while ($m = mysqli_fetch_assoc($monthly_res)): ?>
                                <tr>
                                    <td><strong><?= $m['month'] ?></strong></td>
                                    <td><?= $m['total'] ?></td>
                                    <td><strong class="text-success"><?= htmlspecialchars($global_currency) ?><?= number_format($m['revenue'], 2) ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-trophy"></i> Top Customers</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Customer</th><th>Bookings</th><th>Total Spent</th></tr></thead>
                            <tbody>
                                <?php while ($tc = mysqli_fetch_assoc($top_customers)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <?= htmlspecialchars($tc['full_name']) ?>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-secondary"><?= $tc['bookings'] ?></span></td>
                                    <td><strong class="text-primary"><?= htmlspecialchars($global_currency) ?><?= number_format($tc['spent'], 2) ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-people"></i> Staff Summary</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Position</th><th>Count</th><th>Total Salary</th></tr></thead>
                            <tbody>
                                <?php while ($sr = mysqli_fetch_assoc($staff_report)): ?>
                                <tr>
                                    <td><span class="badge badge-purple"><?= htmlspecialchars($sr['position']) ?></span></td>
                                    <td><strong><?= $sr['total'] ?></strong></td>
                                    <td><strong><?= htmlspecialchars($global_currency) . number_format($sr['total_salary'], 2) ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-box-seam"></i> Top Selling Products</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Product</th><th>Qty Sold</th><th>Total</th></tr></thead>
                            <tbody>
                                <?php while ($ps = mysqli_fetch_assoc($product_sales)): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                <i class="bi bi-box"></i>
                                            </div>
                                            <?= htmlspecialchars($ps['item_name']) ?>
                                        </div>
                                    </td>
                                    <td><span class="badge badge-info"><?= $ps['qty'] ?></span></td>
                                    <td><strong class="text-success"><?= htmlspecialchars($global_currency) ?><?= number_format($ps['total'], 2) ?></strong></td>
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
<?php include '../../includes/footer.php'; ?>
