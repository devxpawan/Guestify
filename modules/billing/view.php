<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT i.*, r.check_in, r.check_out, r.adults, r.children, c.full_name, c.nic_passport, c.phone, c.email, c.address, rm.room_number, rm.price 
                                                   FROM invoices i 
                                                   JOIN reservations r ON i.reservation_id = r.id 
                                                   JOIN customers c ON r.customer_id = c.id 
                                                   JOIN rooms rm ON r.room_id = rm.id 
                                                   WHERE i.id=$id"));
if (!$invoice) {
    header('Location: index.php');
    exit();
}

$items = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id=$id");
$payments = mysqli_query($conn, "SELECT * FROM payments WHERE invoice_id=$id");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Invoice #<?= $invoice['id'] ?></h2>
        <div>
            <a href="print.php?id=<?= $id ?>" class="btn btn-secondary" target="_blank">Print</a>
            <a href="index.php" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Customer Details</h5></div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($invoice['full_name']) ?></p>
                    <p><strong>NIC:</strong> <?= htmlspecialchars($invoice['nic_passport']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($invoice['phone']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($invoice['email']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($invoice['address']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header"><h5>Reservation Details</h5></div>
                <div class="card-body">
                    <p><strong>Room:</strong> <?= $invoice['room_number'] ?> ($<?= number_format($invoice['price'], 2) ?>/night)</p>
                    <p><strong>Check-In:</strong> <?= $invoice['check_in'] ?></p>
                    <p><strong>Check-Out:</strong> <?= $invoice['check_out'] ?></p>
                    <p><strong>Adults:</strong> <?= $invoice['adults'] ?> | <strong>Children:</strong> <?= $invoice['children'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><h5>Invoice Items</h5></div>
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                <tbody>
                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['item_name']) ?></td>
                        <td><?= $item['item_type'] ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>$<?= number_format($item['total'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Payments</h5></div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($payments) > 0): ?>
                    <table class="table table-sm">
                        <thead><tr><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php while ($pm = mysqli_fetch_assoc($payments)): ?>
                            <tr>
                                <td>$<?= number_format($pm['amount'], 2) ?></td>
                                <td><?= $pm['payment_method'] ?></td>
                                <td><?= $pm['payment_date'] ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p class="text-muted">No payments recorded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Summary</h5></div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><td>Room Charges</td><td class="text-end">$<?= number_format($invoice['room_charges'], 2) ?></td></tr>
                        <tr><td>Product Charges</td><td class="text-end">$<?= number_format($invoice['product_charges'], 2) ?></td></tr>
                        <tr><td>Tax</td><td class="text-end">$<?= number_format($invoice['tax'], 2) ?></td></tr>
                        <tr><td>Discount</td><td class="text-end">-$<?= number_format($invoice['discount'], 2) ?></td></tr>
                        <tr class="fw-bold"><td>Grand Total</td><td class="text-end">$<?= number_format($invoice['grand_total'], 2) ?></td></tr>
                        <tr><td>Status</td><td class="text-end"><span class="badge bg-<?= $invoice['payment_status'] == 'Paid' ? 'success' : ($invoice['payment_status'] == 'Partial' ? 'warning' : 'danger') ?>"><?= $invoice['payment_status'] ?></span></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
