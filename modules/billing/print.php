<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

$branding = get_villa_branding();
$global_currency = $branding['currency_symbol'] ?? '$';
$villa_name = $branding['villa_name'] ?? 'Villa Reservation';
$villa_logo = $branding['logo'] ?? '';
$villa_currency = $branding['currency'] ?? 'USD';

$id = (int)$_GET['id'];
$invoice_query = "SELECT i.*, r.booking_type, r.check_in, r.check_out, r.adults, r.children, c.full_name, c.nic_passport, c.phone, c.email, c.address, rm.room_number, rm.price, rm.price_day, rm.price_night, rm.price_short 
                  FROM invoices i 
                  JOIN reservations r ON i.reservation_id = r.id 
                  JOIN customers c ON r.customer_id = c.id 
                  JOIN rooms rm ON r.room_id = rm.id 
                  WHERE i.id=$id AND " . active_villa_where('i');
$invoice = mysqli_fetch_assoc(mysqli_query($conn, $invoice_query));
if (!$invoice) {
    header('Location: index.php');
    exit();
}

// Check if this is a group invoice
$group_rooms = null;
if ($invoice['group_id']) {
    $gid = (int)$invoice['group_id'];
    $group_rooms = mysqli_query($conn, "SELECT rm.room_number
                                         FROM reservations r JOIN rooms rm ON r.room_id = rm.id
                                         WHERE (r.id = $gid OR r.group_id = $gid)
                                         ORDER BY rm.room_number");
}

$items = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id=$id AND " . active_villa_where());
?>
<!DOCTYPE html>
<html>
<head>
    <title>Invoice #<?= $invoice['id'] ?> - <?= htmlspecialchars($villa_name) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #dee2e6; border-radius: 8px; }
        .invoice-header { border-bottom: 2px solid #6366f1; padding-bottom: 20px; margin-bottom: 20px; }
        table { width: 100%; }
        .text-end { text-align: right; }
        .brand-logo { max-height: 60px; width: auto; }
        @media print { .no-print { display: none; } body { padding: 0; } .invoice-box { border: none; } }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="invoice-header">
            <div class="row align-items-center">
                <div class="col-6">
                    <?php if (!empty($villa_logo)): ?>
                        <img src="<?= $base_url ?>uploads/<?= htmlspecialchars($villa_logo) ?>" alt="<?= htmlspecialchars($villa_name) ?>" class="brand-logo">
                    <?php else: ?>
                        <h3 class="mb-0 text-primary"><?= htmlspecialchars($villa_name) ?></h3>
                    <?php endif; ?>
                </div>
                <div class="col-6 text-end">
                    <h4 class="mb-1">INVOICE</h4>
                    <p class="mb-0 text-muted">#<?= $invoice['id'] ?></p>
                    <small class="text-muted">Date: <?= date('M d, Y H:i', strtotime($invoice['created_at'])) ?></small>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <strong class="text-muted" style="font-size: 0.8rem;">BILL TO</strong>
                <p class="mb-0"><strong><?= htmlspecialchars($invoice['full_name']) ?></strong></p>
                <small>
                    NIC: <?= htmlspecialchars($invoice['nic_passport']) ?><br>
                    Phone: <?= htmlspecialchars($invoice['phone']) ?><br>
                    <?php if (!empty($invoice['email'])): ?>Email: <?= htmlspecialchars($invoice['email']) ?><br><?php endif; ?>
                    <?php if (!empty($invoice['address'])): ?><?= htmlspecialchars($invoice['address']) ?><?php endif; ?>
                </small>
            </div>
            <div class="col-6 text-end">
                <strong class="text-muted" style="font-size: 0.8rem;">BOOKING DETAILS</strong>
                <p class="mb-0">
                    <?php if ($group_rooms && mysqli_num_rows($group_rooms) > 0): ?>
                        <?php $room_list = []; mysqli_data_seek($group_rooms, 0); while ($gr = mysqli_fetch_assoc($group_rooms)) { $room_list[] = 'Room ' . $gr['room_number']; } ?>
                        <strong>Rooms:</strong> <?= implode(', ', $room_list) ?><br>
                    <?php else: ?>
                        <strong>Room:</strong> <?= htmlspecialchars($invoice['room_number']) ?> (<?= htmlspecialchars($invoice['booking_type']) ?>)<br>
                    <?php endif; ?>
                    <strong>Check-In:</strong> <?= date('M d, Y H:i', strtotime($invoice['check_in'])) ?><br>
                    <strong>Check-Out:</strong> <?= date('M d, Y H:i', strtotime($invoice['check_out'])) ?>
                </p>
            </div>
        </div>

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th style="width:50%">Item</th>
                    <th class="text-end" style="width:15%">Qty</th>
                    <th class="text-end" style="width:20%">Price</th>
                    <th class="text-end" style="width:15%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = mysqli_fetch_assoc($items)): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td class="text-end"><?= $item['quantity'] ?></td>
                    <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($item['price'], 2) ?></td>
                    <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($item['total'], 2) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="row">
            <div class="col-6 offset-6">
                <table class="table table-sm">
                    <tr>
                        <td>Room Charges</td>
                        <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['room_charges'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Product Charges</td>
                        <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['product_charges'], 2) ?></td>
                    </tr>
                    <?php if ($invoice['discount'] > 0): ?>
                    <tr>
                        <td>Discount</td>
                        <td class="text-end text-success">-<?= htmlspecialchars($global_currency) ?><?= number_format($invoice['discount'], 2) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="fw-bold border-top">
                        <td>Grand Total</td>
                        <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['grand_total'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>Payment Status</td>
                        <td class="text-end">
                            <span class="badge bg-<?= $invoice['payment_status'] == 'Paid' ? 'success' : ($invoice['payment_status'] == 'Partial' ? 'warning' : 'danger') ?>">
                                <?= $invoice['payment_status'] ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <hr class="mt-4">
        <p class="text-center text-muted mb-0" style="font-size: 0.85rem;">
            Thank you for choosing <strong><?= htmlspecialchars($villa_name) ?></strong>!<br>
            <small>Currency: <?= htmlspecialchars($villa_currency) ?> &mdash; Invoice #<?= $invoice['id'] ?></small>
        </p>
        <div class="text-center mt-3">
            <button class="btn btn-primary no-print" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
            <a href="view.php?id=<?= $id ?>" class="btn btn-secondary no-print"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
</body>
</html>
