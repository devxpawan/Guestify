<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Cashier'])) {
    header('Location: ../../dashboard.php');
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$invoices = mysqli_query($conn, "SELECT i.*, r.check_in, r.check_out, c.full_name 
                                 FROM invoices i 
                                 JOIN reservations r ON i.reservation_id = r.id 
                                 JOIN customers c ON r.customer_id = c.id 
                                 ORDER BY i.id DESC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Billing & Invoices</h2>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Invoice</a>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Invoice #</th><th>Customer</th><th>Room Charges</th><th>Product Charges</th><th>Tax</th><th>Discount</th><th>Total</th><th>Payment</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($inv = mysqli_fetch_assoc($invoices)): ?>
                    <tr>
                        <td><?= $inv['id'] ?></td>
                        <td><?= htmlspecialchars($inv['full_name']) ?></td>
                        <td>$<?= number_format($inv['room_charges'], 2) ?></td>
                        <td>$<?= number_format($inv['product_charges'], 2) ?></td>
                        <td>$<?= number_format($inv['tax'], 2) ?></td>
                        <td>$<?= number_format($inv['discount'], 2) ?></td>
                        <td><strong>$<?= number_format($inv['grand_total'], 2) ?></strong></td>
                        <td>
                            <span class="badge bg-<?= $inv['payment_status'] == 'Paid' ? 'success' : ($inv['payment_status'] == 'Partial' ? 'warning' : 'danger') ?>">
                                <?= $inv['payment_status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="view.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-info">View</a>
                            <a href="payments.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-success">Pay</a>
                            <a href="print.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-secondary" target="_blank">Print</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
