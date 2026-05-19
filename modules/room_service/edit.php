<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist', 'Manager'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$order = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT so.*, p.product_name, p.quantity AS stock, c.full_name, rm.room_number
    FROM service_orders so
    JOIN reservations r ON so.reservation_id = r.id
    JOIN customers c ON r.customer_id = c.id
    JOIN rooms rm ON r.room_id = rm.id
    JOIN products p ON so.product_id = p.id
    WHERE so.id = $id
"));

if (!$order) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'cancel') {
        // Restore stock
        mysqli_query($conn, "UPDATE products SET quantity = quantity + {$order['quantity']} WHERE id = {$order['product_id']}");
        mysqli_query($conn, "UPDATE service_orders SET status = 'Cancelled' WHERE id = $id");
        $_SESSION['success'] = 'Order cancelled. Stock restored.';
        header('Location: index.php');
        exit();
    }

    if ($action === 'update') {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        if ($quantity <= 0) {
            $error = 'Quantity must be at least 1.';
        } else {
            $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id"));
            if (!$prod) {
                $error = 'Invalid product.';
            } else {
                // Calculate stock difference
                $old_qty = $order['quantity'];
                $diff = $quantity - $old_qty;

                if ($diff > 0 && $prod['quantity'] < $diff) {
                    $error = 'Insufficient stock! Only ' . $prod['quantity'] . ' available.';
                } else {
                    // Adjust stock
                    if ($diff != 0) {
                        mysqli_query($conn, "UPDATE products SET quantity = quantity - $diff WHERE id = $product_id");
                    }

                    $price = $prod['price'];
                    mysqli_query($conn, "UPDATE service_orders SET product_id=$product_id, quantity=$quantity, price=$price, status='$status' WHERE id=$id");
                    $_SESSION['success'] = 'Order updated successfully!';
                    header('Location: index.php');
                    exit();
                }
            }
        }
    }
}

$products = mysqli_query($conn, "SELECT * FROM products WHERE is_active = 1 ORDER BY product_name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Room Service</a></li>
            <li class="breadcrumb-item active">Edit Order #<?= $order['id'] ?></li>
        </ol>
    </nav>

    <h2>Edit Order #<?= $order['id'] ?> — Room <?= $order['room_number'] ?></h2>
    <p class="text-muted">Guest: <?= htmlspecialchars($order['full_name']) ?> | Current Status: <span class="badge badge-<?= $order['status'] == 'Pending' ? 'warning' : ($order['status'] == 'Served' ? 'success' : 'secondary') ?>"><?= $order['status'] ?></span></p>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <select name="product_id" class="form-control" required>
                                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                <option value="<?= $p['id'] ?>" <?= $order['product_id'] == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['product_name']) ?> - <?= htmlspecialchars($global_currency) ?><?= number_format($p['price'], 2) ?> (Stock: <?= $p['quantity'] ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="<?= $order['quantity'] ?>" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="Pending" <?= $order['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="Served" <?= $order['status'] == 'Served' ? 'selected' : '' ?>>Served</option>
                                <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="d-flex gap-2">
                            <input type="hidden" name="action" value="update">
                            <button type="submit" class="btn btn-primary">Update Order</button>
                            <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>

                    <?php if ($order['status'] !== 'Cancelled'): ?>
                    <hr>
                    <form method="POST" onsubmit="return confirm('Cancel this order? Stock will be restored.');">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-outline-danger"><i class="fas fa-ban"></i> Cancel Entire Order</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
