<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager', 'Receptionist'])) {
    header('Location: ../../dashboard.php');
    exit();
}

$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : date('Y-m-d');
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : date('Y-m-d');

// Today's reservations
$daily = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(*) as total, COALESCE(SUM(i.grand_total),0) as revenue
    FROM reservations r
    LEFT JOIN invoices i ON r.id = i.reservation_id
    WHERE DATE(r.created_at) BETWEEN '$date_from' AND '$date_to'
    AND " . active_villa_where('r')));

// Reservations list for selected date
$today_reservations = mysqli_query($conn, "
    SELECT 
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
    WHERE DATE(r.created_at) BETWEEN '$date_from' AND '$date_to'
    AND " . active_villa_where('r') . "
    GROUP BY COALESCE(r.group_id, r.id)
    ORDER BY gid DESC");

// Room occupancy
$occupancy = mysqli_query($conn, "
    SELECT rm.room_number, t.type_name, rm.status
    FROM rooms rm
    JOIN room_types t ON rm.room_type_id = t.id
    WHERE " . active_villa_where('rm') . "
    ORDER BY rm.room_number");

// Income vs Expense for selected period
$finance = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COALESCE(SUM(CASE WHEN type='Income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END), 0) AS total_expense
    FROM transactions
    WHERE DATE(transaction_date) BETWEEN '$date_from' AND '$date_to'
    AND " . active_villa_where_raw()));

// Monthly overview (last 6 months)
$finance_monthly = mysqli_query($conn, "
    SELECT
        DATE_FORMAT(transaction_date, '%Y-%m') as month,
        COALESCE(SUM(CASE WHEN type='Income' THEN amount ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END), 0) AS expense
    FROM transactions
    WHERE " . active_villa_where_raw() . "
    GROUP BY month ORDER BY month DESC LIMIT 6");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <h2><i class="bi bi-graph-up"></i> Reports & Analytics</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Daily performance overview and insights</p>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-4">
                    <label class="form-label small text-muted">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Generate Report</button>
                    <a href="index.php" class="btn btn-outline-secondary" title="Today"><i class="bi bi-calendar-day"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Reservations</p>
                            <h4 class="mb-0 text-primary"><?= $daily['total'] ?></h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-calendar-check text-primary fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Revenue</p>
                            <h4 class="mb-0 text-success"><?= htmlspecialchars($global_currency) ?><?= number_format($daily['revenue'], 2) ?></h4>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-cash-stack text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Income</p>
                            <h4 class="mb-0 text-success"><?= htmlspecialchars($global_currency) ?><?= number_format($finance['total_income'], 2) ?></h4>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Expenses</p>
                            <h4 class="mb-0 text-danger"><?= htmlspecialchars($global_currency) ?><?= number_format($finance['total_expense'], 2) ?></h4>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-graph-down-arrow text-danger fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-list-check"></i> Reservations (<?= htmlspecialchars($date_from) ?><?= $date_from !== $date_to ? ' to ' . htmlspecialchars($date_to) : '' ?>)</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Room</th><th>Customer</th><th>Check-In</th><th>Check-Out</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if (mysqli_num_rows($today_reservations) > 0): ?>
                                <?php while ($r = mysqli_fetch_assoc($today_reservations)): ?>
                                <tr>
                                    <td>
                                        <?php if ($r['room_count'] > 1): ?>
                                            <span class="badge bg-secondary"><?= $r['room_count'] ?> Rooms</span><br>
                                        <?php endif; ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($r['room_numbers']) ?></span>
                                    </td>
                                    <td><?= htmlspecialchars($r['full_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($r['check_in'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($r['check_out'])) ?></td>
                                    <td><span class="badge badge-<?= $r['status'] == 'Confirmed' ? 'success' : ($r['status'] == 'Pending' ? 'warning' : ($r['status'] == 'Checked-In' ? 'info' : ($r['status'] == 'Checked-Out' ? 'secondary' : 'danger'))) ?>"><?= $r['status'] ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No reservations for this period.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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
                <div class="card-header"><h5><i class="bi bi-bar-chart-line"></i> Income & Expenses (<?= htmlspecialchars($date_from) ?><?= $date_from !== $date_to ? ' to ' . htmlspecialchars($date_to) : '' ?>)</h5></div>
                <div class="card-body">
                    <div class="row text-center g-3 mb-3">
                        <div class="col-6">
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Net Profit</p>
                            <h4 class="<?= ($finance['total_income'] - $finance['total_expense']) >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= htmlspecialchars($global_currency) ?><?= number_format($finance['total_income'] - $finance['total_expense'], 2) ?>
                            </h4>
                        </div>
                        <div class="col-6">
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Profit Margin</p>
                            <h4 class="text-info"><?= $finance['total_income'] > 0 ? number_format(($finance['total_income'] - $finance['total_expense']) / $finance['total_income'] * 100, 1) : 0 ?>%</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5><i class="bi bi-clock-history"></i> Monthly Overview</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Month</th><th>Income</th><th>Expenses</th><th>Net</th></tr></thead>
                            <tbody>
                                <?php if (mysqli_num_rows($finance_monthly) > 0): ?>
                                <?php while ($fm = mysqli_fetch_assoc($finance_monthly)): ?>
                                <?php $net = $fm['income'] - $fm['expense']; ?>
                                <tr>
                                    <td><strong><?= $fm['month'] ?></strong></td>
                                    <td class="text-success">+<?= htmlspecialchars($global_currency) ?><?= number_format($fm['income'], 2) ?></td>
                                    <td class="text-danger">-<?= htmlspecialchars($global_currency) ?><?= number_format($fm['expense'], 2) ?></td>
                                    <td><strong class="<?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= htmlspecialchars($global_currency) ?><?= number_format($net, 2) ?></strong></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No data available.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
