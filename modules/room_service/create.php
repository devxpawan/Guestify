<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist', 'Manager'])) {
    header('Location: index.php');
    exit();
}

$reservation_id = isset($_GET['reservation_id']) ? (int)$_GET['reservation_id'] : 0;
$error = '';
$success = '';

// Verify reservation
$res_query = "SELECT r.id, c.full_name, rm.room_number 
              FROM reservations r 
              JOIN customers c ON r.customer_id = c.id 
              JOIN rooms rm ON r.room_id = rm.id 
              WHERE r.id = $reservation_id AND r.status = 'Checked-In'";
$res = mysqli_fetch_assoc(mysqli_query($conn, $res_query));

if (!$res) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($quantity <= 0) {
        $error = 'Quantity must be at least 1.';
    } else {
        $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id"));
        
        if (!$prod) {
            $error = 'Invalid product selected.';
        } elseif ($prod['quantity'] < $quantity) {
            $error = 'Insufficient stock! Only ' . $prod['quantity'] . ' left.';
        } else {
            $price = $prod['price'];
            $query = "INSERT INTO service_orders (reservation_id, product_id, quantity, price, status) 
                      VALUES ($reservation_id, $product_id, $quantity, $price, 'Pending')";
            
            if (mysqli_query($conn, $query)) {
                mysqli_query($conn, "UPDATE products SET quantity = quantity - $quantity WHERE id=$product_id");
                $_SESSION['success'] = 'Order placed successfully!';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                $error = 'Failed to place order: ' . mysqli_error($conn);
            }
        }
    }
}

$products = mysqli_query($conn, "SELECT * FROM products WHERE quantity > 0 ORDER BY product_name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Room Service</a></li>
            <li class="breadcrumb-item active">New Order</li>
        </ol>
    </nav>

    <h2>New Order: Room <?= $res['room_number'] ?></h2>
    <p class="text-muted">Guest: <?= htmlspecialchars($res['full_name']) ?></p>



    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Select Product / Food</label>
                            <select name="product_id" class="form-control" required>
                                <option value="">Choose item...</option>
                                <?php while ($p = mysqli_fetch_assoc($products)): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['product_name']) ?> - $<?= number_format($p['price'], 2) ?> (Stock: <?= $p['quantity'] ?>)</option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Place Order</button>
                            <a href="index.php" class="btn btn-outline-secondary">Back to List</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-info shadow-sm">
                <div class="card-body">
                    <h5 class="card-title text-info"><i class="bi bi-info-circle"></i> Quick Tip</h5>
                    <p class="card-text">Orders placed here will be automatically added to the guest's final bill at check-out. You don't need to create an invoice manually for each order.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
