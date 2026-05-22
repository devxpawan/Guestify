<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT i.*, r.booking_type, r.check_in, r.check_out, r.adults, r.children, c.full_name, c.nic_passport, c.phone, c.email, c.address, rm.room_number, rm.price, rm.price_day, rm.price_night, rm.price_short 
                                                   FROM invoices i 
                                                   JOIN reservations r ON i.reservation_id = r.id 
                                                   JOIN customers c ON r.customer_id = c.id 
                                                   JOIN rooms rm ON r.room_id = rm.id 
                                                   WHERE i.id=$id AND " . active_villa_where('i')));
if (!$invoice) {
    header('Location: index.php');
    exit();
}

$items = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id=$id AND " . active_villa_where());
$payments = mysqli_query($conn, "SELECT * FROM payments WHERE invoice_id=$id AND " . active_villa_where());

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="fas fa-file-invoice"></i> Invoice #<?= $invoice['id'] ?></h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Customer: <strong><?= htmlspecialchars($invoice['full_name']) ?></strong></p>
            </div>
            <div class="d-flex gap-2">
                <a href="print.php?id=<?= $id ?>" class="btn btn-outline-secondary" target="_blank"><i class="fas fa-print"></i> Print</a>
                <a href="index.php" class="btn btn-outline-primary"><i class="fas fa-arrow-left"></i> Back</a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-user"></i> Customer Details</h5></div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?= htmlspecialchars($invoice['full_name']) ?></p>
                    <p><strong>NIC:</strong> <?= htmlspecialchars($invoice['nic_passport']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($invoice['phone']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($invoice['email']) ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($invoice['address']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-calendar-alt"></i> Reservation Details</h5></div>
                <div class="card-body">
                    <?php
                    $room_rate = $invoice['price_night'];
                    if ($invoice['booking_type'] === 'Day Time') {
                        $room_rate = $invoice['price_day'];
                    } elseif ($invoice['booking_type'] === 'Short Time') {
                        $room_rate = $invoice['price_short'];
                    }
                    ?>
                    <p><strong>Room:</strong> <?= $invoice['room_number'] ?> (<?= htmlspecialchars($global_currency) ?><?= number_format($room_rate, 2) ?> / <?= htmlspecialchars($invoice['booking_type']) ?>)</p>
                    <p><strong>Check-In:</strong> <?= $invoice['check_in'] ?></p>
                    <p><strong>Check-Out:</strong> <?= $invoice['check_out'] ?></p>
                    <p><strong>Adults:</strong> <?= $invoice['adults'] ?> | <strong>Children:</strong> <?= $invoice['children'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header"><h5><i class="fas fa-list"></i> Invoice Items</h5></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
                    <tbody>
                        <?php while ($item = mysqli_fetch_assoc($items)): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_name']) ?></td>
                            <td><?= $item['item_type'] ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td><?= htmlspecialchars($global_currency) ?><?= number_format($item['price'], 2) ?></td>
                            <td><?= htmlspecialchars($global_currency) ?><?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-credit-card"></i> Payments</h5></div>
                <div class="card-body">
                    <?php if (mysqli_num_rows($payments) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead><tr><th>Amount</th><th>Method</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php while ($pm = mysqli_fetch_assoc($payments)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($global_currency) ?><?= number_format($pm['amount'], 2) ?></td>
                                    <td><?= $pm['payment_method'] ?></td>
                                    <td><?= $pm['payment_date'] ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0"><i class="fas fa-inbox"></i> No payments recorded.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-calculator"></i> Summary</h5></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr><td>Room Charges</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['room_charges'], 2) ?></td></tr>
                        <tr><td>Product Charges</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['product_charges'], 2) ?></td></tr>
                        <tr><td>Discount</td><td class="text-end text-success">-<?= htmlspecialchars($global_currency) ?><?= number_format($invoice['discount'], 2) ?></td></tr>
                        <tr class="fw-bold"><td>Grand Total</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['grand_total'], 2) ?></td></tr>
                        <tr><td>Status</td><td class="text-end"><span class="badge badge-<?= $invoice['payment_status'] == 'Paid' ? 'success' : ($invoice['payment_status'] == 'Partial' ? 'warning' : 'danger') ?>"><?= $invoice['payment_status'] ?></span></td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>