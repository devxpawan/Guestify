<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

$branding = get_villa_branding();
$global_currency = $branding['currency_symbol'] ?? '$';


if (!has_role(['Admin', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];



// --- POST HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT i.*, r.check_in, r.check_out, c.full_name, rm.room_number 
                                                   FROM invoices i 
                                                   JOIN reservations r ON i.reservation_id = r.id 
                                                   JOIN customers c ON r.customer_id = c.id 
                                                   JOIN rooms rm ON r.room_id = rm.id
                                                   WHERE i.id=$id AND " . active_villa_where('i')));
    if (!$invoice) {
        header('Location: index.php');
        exit();
    }

    $paid_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=$id AND " . active_villa_where()))[0];
    $remaining = $invoice['grand_total'] - $paid_total;

    $amount = (float)$_POST['amount'];
    $method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    if ($amount <= 0) {
        $_SESSION['error'] = 'Invalid amount.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } elseif ($amount > $remaining) {
        $_SESSION['error'] = 'Amount exceeds remaining balance (' . htmlspecialchars($global_currency) . number_format($remaining, 2) . ').';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $villa_id = active_villa_id();
        mysqli_query($conn, "INSERT INTO payments (invoice_id, amount, payment_method, villa_id) VALUES ($id, $amount, '$method', $villa_id)");
        $payment_id = mysqli_insert_id($conn);

        $new_paid = $paid_total + $amount;

        if ($new_paid >= $invoice['grand_total']) {
            mysqli_query($conn, "UPDATE invoices SET payment_status='Paid' WHERE id=$id");
        } else {
            mysqli_query($conn, "UPDATE invoices SET payment_status='Partial' WHERE id=$id");
        }

        $_SESSION['success'] = 'Payment recorded!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// --- DISPLAY DATA ---
$invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT i.*, r.check_in, r.check_out, c.full_name, rm.room_number 
                                                   FROM invoices i 
                                                   JOIN reservations r ON i.reservation_id = r.id 
                                                   JOIN customers c ON r.customer_id = c.id 
                                                   JOIN rooms rm ON r.room_id = rm.id
                                                   WHERE i.id=$id AND " . active_villa_where('i')));
if (!$invoice) {
    header('Location: index.php');
    exit();
}

$paid_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=$id AND " . active_villa_where()))[0];
$remaining = $invoice['grand_total'] - $paid_total;

$payments = mysqli_query($conn, "SELECT * FROM payments WHERE invoice_id=$id AND " . active_villa_where() . " ORDER BY payment_date DESC");

$items = mysqli_query($conn, "SELECT * FROM invoice_items WHERE invoice_id = $id ORDER BY id ASC");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-credit-card"></i> Payments for Invoice #<?= $id ?></h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Customer: <strong><?= htmlspecialchars($invoice['full_name']) ?></strong> &mdash; Room: <strong><?= htmlspecialchars($invoice['room_number']) ?></strong> &mdash; Check-in: <?= date('M d, Y', strtotime($invoice['check_in'])) ?> &mdash; Check-out: <?= date('M d, Y', strtotime($invoice['check_out'])) ?></p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left: Summary + Payment Form -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h5><i class="bi bi-receipt"></i> Invoice Summary</h5></div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Grand Total</td>
                            <td class="text-end"><strong><?= htmlspecialchars($global_currency) ?><?= number_format($invoice['grand_total'], 2) ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total Paid</td>
                            <td class="text-end text-success"><strong><?= htmlspecialchars($global_currency) ?><?= number_format($paid_total, 2) ?></strong></td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold">Remaining</td>
                            <td class="text-end"><span class="badge badge-<?= $remaining > 0 ? 'warning' : 'success' ?>" style="font-size: 1rem;"><?= htmlspecialchars($global_currency) ?><?= number_format($remaining, 2) ?></span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <?php if ($remaining > 0): ?>
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="bi bi-plus-circle"></i> Record New Payment</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= htmlspecialchars($global_currency) ?></span>
                                <input type="number" step="0.01" name="amount" class="form-control" max="<?= $remaining ?>" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-check"></i> Record Payment</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: Room & Services + Payment History -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h5><i class="bi bi-door-open"></i> Room & Services</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Room</th>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $has_items = false; while ($item = mysqli_fetch_assoc($items)): $has_items = true; ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($invoice['room_number']) ?></span></td>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td class="text-end"><?= $item['quantity'] ?></td>
                                    <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($item['price'], 2) ?></td>
                                    <td class="text-end"><?= htmlspecialchars($global_currency) ?><?= number_format($item['total'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if (!$has_items): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No items found.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="bi bi-clock-history"></i> Payment History</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Date & Time</th>
                                    <th>Method</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($payments) > 0): ?>
                                <?php $idx = 1; while ($p = mysqli_fetch_assoc($payments)): ?>
                                <tr>
                                    <td><?= $idx++ ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($p['payment_date'])) ?></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($p['payment_method']) ?></span></td>
                                    <td class="text-end text-success fw-bold"><?= htmlspecialchars($global_currency) ?><?= number_format($p['amount'], 2) ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr class="table-active fw-bold">
                                    <td colspan="3" class="text-end">Total Paid</td>
                                    <td class="text-end text-success"><?= htmlspecialchars($global_currency) ?><?= number_format($paid_total, 2) ?></td>
                                </tr>
                                <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4"><i class="bi bi-inbox"></i> No payments recorded yet.</td>
                                </tr>
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