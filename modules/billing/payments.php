<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$invoice = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM invoices WHERE id=$id"));
if (!$invoice) {
    header('Location: index.php');
    exit();
}

$paid_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=$id"))[0];
$remaining = $invoice['grand_total'] - $paid_total;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)$_POST['amount'];
    $method = mysqli_real_escape_string($conn, $_POST['payment_method']);

    if ($amount <= 0) {
        $error = 'Invalid amount.';
    } elseif ($amount > $remaining) {
        $error = 'Amount exceeds remaining balance ($' . number_format($remaining, 2) . ').';
    } else {
        mysqli_query($conn, "INSERT INTO payments (invoice_id, amount, payment_method) VALUES ($id, $amount, '$method')");

        $new_paid = $paid_total + $amount;
        if ($new_paid >= $invoice['grand_total']) {
            mysqli_query($conn, "UPDATE invoices SET payment_status='Paid' WHERE id=$id");
        } else {
            mysqli_query($conn, "UPDATE invoices SET payment_status='Partial' WHERE id=$id");
        }

        $success = 'Payment recorded!';
        $paid_total = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id=$id"))[0];
        $remaining = $invoice['grand_total'] - $paid_total;
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Payments - Invoice #<?= $id ?></h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-body">
                    <p><strong>Grand Total:</strong> <?= htmlspecialchars($global_currency) ?><?= number_format($invoice['grand_total'], 2) ?></p>
                    <p><strong>Total Paid:</strong> <?= htmlspecialchars($global_currency) ?><?= number_format($paid_total, 2) ?></p>
                    <p><strong>Remaining:</strong> <?= htmlspecialchars($global_currency) ?><?= number_format($remaining, 2) ?></p>
                </div>
            </div>
            <?php if ($remaining > 0): ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" max="<?= $remaining ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Cash">Cash</option>
                                <option value="Card">Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Record Payment</button>
                        <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Back</a>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
