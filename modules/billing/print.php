<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

$branding_query = mysqli_query($conn, "SELECT currency_symbol FROM settings LIMIT 1");
$branding = mysqli_fetch_assoc($branding_query);
$global_currency = $branding['currency_symbol'] ?? '$';


$id = (int)$_GET['id'];
$invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT i.*, r.booking_type, r.check_in, r.check_out, r.adults, r.children, c.full_name, c.nic_passport, c.phone, c.email, c.address, rm.room_number, rm.price, rm.price_day, rm.price_night, rm.price_short 
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?= $invoice['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        table { width: 100%; }
        .text-end { text-align: right; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="text-center mb-4">
            <h2>VILLA RESERVATION</h2>
            <p>Invoice #<?= $invoice['id'] ?></p>
        </div>
        <hr>
        <div class="row mb-3">
            <div class="col-6">
                <strong>Customer:</strong> <?= htmlspecialchars($invoice['full_name']) ?><br>
                <strong>NIC:</strong> <?= htmlspecialchars($invoice['nic_passport']) ?><br>
                <strong>Phone:</strong> <?= htmlspecialchars($invoice['phone']) ?>
            </div>
            <div class="col-6 text-end">
                <strong>Room:</strong> <?= $invoice['room_number'] ?> (<?= htmlspecialchars($invoice['booking_type']) ?>)<br>
                <strong>Check-In:</strong> <?= $invoice['check_in'] ?><br>
                <strong>Check-Out:</strong> <?= $invoice['check_out'] ?>
            </div>
        </div>
        <table class="table table-bordered">
            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
            <tbody>
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= htmlspecialchars($global_currency) ?><?= number_format($item['price'], 2) ?></td>
                    <td><?= htmlspecialchars($global_currency) ?><?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <div class="row">
            <div class="col-6 offset-6">
                <table class="table table-sm">
                    <tr><td>Room Charges</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['room_charges'], 2) ?></td></tr>
                    <tr><td>Product Charges</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['product_charges'], 2) ?></td></tr>
                    <tr><td>Tax</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['tax'], 2) ?></td></tr>
                    <tr><td>Discount</td><td class="text-end">-<?= htmlspecialchars($global_currency) ?><?= number_format($invoice['discount'], 2) ?></td></tr>
                    <tr class="fw-bold"><td>Grand Total</td><td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['grand_total'], 2) ?></td></tr>
                </table>
            </div>
        </div>
        <hr>
        <p class="text-center text-muted">Thank you for choosing Villa Reservation!</p>
        <button class="btn btn-primary no-print" onclick="window.print()">Print</button>
        <a href="view.php?id=<?= $id ?>" class="btn btn-secondary no-print">Back</a>
    </div>
</body>
</html>
