<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

$reservations = mysqli_query($conn, "SELECT r.id, r.check_in, r.check_out, c.full_name, rm.room_number, rm.price 
                                     FROM reservations r 
                                     JOIN customers c ON r.customer_id = c.id 
                                     JOIN rooms rm ON r.room_id = rm.id 
                                     WHERE r.status IN ('Checked-In','Checked-Out') 
                                     AND r.id NOT IN (SELECT reservation_id FROM invoices)
                                     ORDER BY r.id DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_POST['reservation_id'];
    $discount = (float)$_POST['discount'];

    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT r.*, rm.price, rm.price_day, rm.price_night, rm.price_short FROM reservations r JOIN rooms rm ON r.room_id = rm.id WHERE r.id=$reservation_id"));
    if (!$res) {
        $error = 'Invalid reservation.';
    } else {
        $price = $res['price_night'];
        if ($res['booking_type'] === 'Day Time') {
            $price = $res['price_day'];
        } elseif ($res['booking_type'] === 'Short Time') {
            $price = $res['price_short'];
        }

        $days = max(1, ceil((strtotime($res['check_out']) - strtotime($res['check_in'])) / 86400));
        $room_charges = $days * $price;
        $product_charges = 0;

        if (isset($_POST['products'])) {
            foreach ($_POST['products'] as $pid => $qty) {
                if ($qty > 0) {
                    $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$pid"));
                    if ($prod && $qty <= $prod['quantity']) {
                        $product_charges += $qty * $prod['price'];
                    }
                }
            }
        }

        // Add Room Service orders
        $service_orders = mysqli_query($conn, "SELECT * FROM service_orders WHERE reservation_id = $reservation_id AND status != 'Cancelled'");
        while ($so = mysqli_fetch_assoc($service_orders)) {
            $product_charges += $so['quantity'] * $so['price'];
        }

        $grand_total = $room_charges + $product_charges - $discount;

        $query = "INSERT INTO invoices (reservation_id, room_charges, product_charges, discount, grand_total, payment_status) 
                  VALUES ($reservation_id, $room_charges, $product_charges, $discount, $grand_total, 'Unpaid')";
        if (mysqli_query($conn, $query)) {
            $invoice_id = mysqli_insert_id($conn);

            mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total) VALUES ($invoice_id, 'Room', 'Room Charges ({$res['check_in']} to {$res['check_out']})', $days, $price, $room_charges)");

            if (isset($_POST['products'])) {
                foreach ($_POST['products'] as $pid => $qty) {
                    if ($qty > 0) {
                        $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$pid"));
                        $total = $qty * $prod['price'];
                        mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total) VALUES ($invoice_id, 'Product', '{$prod['product_name']}', $qty, {$prod['price']}, $total)");
                        mysqli_query($conn, "UPDATE products SET quantity = quantity - $qty WHERE id=$pid");
                    }
                }
            }

            // Transfer Service Orders to Invoice Items
            $service_orders = mysqli_query($conn, "SELECT so.*, p.product_name FROM service_orders so JOIN products p ON so.product_id = p.id WHERE so.reservation_id = $reservation_id AND so.status != 'Cancelled'");
            while ($so = mysqli_fetch_assoc($service_orders)) {
                $total = $so['quantity'] * $so['price'];
                mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total) VALUES ($invoice_id, 'Product', 'RS: {$so['product_name']}', {$so['quantity']}, {$so['price']}, $total)");
            }

            $_SESSION['success'] = 'Invoice created successfully!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Failed: ' . mysqli_error($conn);
        }
    }
}

$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 AND is_active = 1 ORDER BY product_name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="fas fa-file-invoice"></i> Create Invoice</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Generate invoice for checked-in/checked-out reservations</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Invoices</a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header"><h5><i class="fas fa-calendar-check"></i> Invoice Details</h5></div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Reservation (Checked-In/Out)</label>
                    <select name="reservation_id" class="form-select" required>
                        <option value="">Select Reservation</option>
                        <?php while ($r = mysqli_fetch_assoc($reservations)): ?>
                        <option value="<?= $r['id'] ?>">#<?= $r['id'] ?> - <?= htmlspecialchars($r['full_name']) ?> (Rm <?= $r['room_number'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Products</label>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Product</th><th>Price</th><th>Qty</th></tr></thead>
                            <tbody>
                                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($p['product_name']) ?></td>
                                    <td><?= htmlspecialchars($global_currency) ?><?= number_format($p['price'], 2) ?></td>
                                    <td><input type="number" name="products[<?= $p['id'] ?>]" class="form-control form-control-sm" style="width:80px" value="0" min="0" max="<?= $p['quantity'] ?>"></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Discount (<?= htmlspecialchars($global_currency) ?>)</label>
                        <input type="number" step="0.01" name="discount" class="form-control" value="0">
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Generate Invoice</button>
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>