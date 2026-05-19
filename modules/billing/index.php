<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

if (!has_role(['Admin', 'Cashier'])) {
    header('Location: ../../dashboard.php');
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = [];
if ($search !== '') {
    $where[] = "(c.full_name LIKE '%$search%' OR i.id = '" . (int)$search . "')";
}
if ($status !== '') {
    $where[] = "i.payment_status = '$status'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM invoices i JOIN reservations r ON i.reservation_id = r.id JOIN customers c ON r.customer_id = c.id $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$invoices = mysqli_query($conn, "SELECT i.*, r.check_in, r.check_out, c.full_name 
                                 FROM invoices i 
                                 JOIN reservations r ON i.reservation_id = r.id 
                                 JOIN customers c ON r.customer_id = c.id 
                                 $where_clause
                                 ORDER BY i.id DESC LIMIT $offset, $per_page");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-receipt"></i> Billing & Invoices</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage invoices and payment tracking</p>
            </div>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Invoice</a>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by Invoice # or Customer Name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Paid" <?= $status == 'Paid' ? 'selected' : '' ?>>Paid</option>
                        <option value="Partial" <?= $status == 'Partial' ? 'selected' : '' ?>>Partial</option>
                        <option value="Unpaid" <?= $status == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $status): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice #</th><th>Customer</th><th>Room Charges</th><th>Product Charges</th><th>Tax</th><th>Discount</th><th>Total</th><th>Payment</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($inv = mysqli_fetch_assoc($invoices)): ?>
                    <tr>
                        <td><strong>#<?= $inv['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <?= htmlspecialchars($inv['full_name']) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($global_currency) ?><?= number_format($inv['room_charges'], 2) ?></td>
                        <td><?= htmlspecialchars($global_currency) ?><?= number_format($inv['product_charges'], 2) ?></td>
                        <td><?= htmlspecialchars($global_currency) ?><?= number_format($inv['tax'], 2) ?></td>
                        <td class="text-success">-<?= htmlspecialchars($global_currency) ?><?= number_format($inv['discount'], 2) ?></td>
                        <td><strong class="text-primary"><?= htmlspecialchars($global_currency) ?><?= number_format($inv['grand_total'], 2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $inv['payment_status'] == 'Paid' ? 'success' : ($inv['payment_status'] == 'Partial' ? 'warning' : 'danger') ?>">
                                <i class="bi bi-<?= $inv['payment_status'] == 'Paid' ? 'check-circle' : ($inv['payment_status'] == 'Partial' ? 'clock' : 'x-circle') ?>"></i> <?= $inv['payment_status'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="view.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                <a href="payments.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-success" title="Pay"><i class="bi bi-credit-card"></i></a>
                                <a href="print.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Print"><i class="bi bi-printer"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> invoices</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>