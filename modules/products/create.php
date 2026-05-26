<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

$error = '';
$success = '';

$categories_query = mysqli_query($conn, "SELECT * FROM product_categories WHERE " . active_villa_where_raw() . " ORDER BY category_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $villa_id = (int)active_villa_id();
    $query = "INSERT INTO products (product_name, category, quantity, price, villa_id) VALUES ('$product_name', '$category', $quantity, $price, $villa_id)";
    if (mysqli_query($conn, $query)) {
        $product_id = mysqli_insert_id($conn);
        logAudit('CREATE', 'products', $product_id, "Product $product_name added");
        $_SESSION['success'] = 'Product added successfully!';
        header("Location: index.php");
        exit();
    } else {
        $error = 'Failed: ' . mysqli_error($conn);
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Add Product</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_query)): ?>
                        <option value="<?= htmlspecialchars($cat['category_name']) ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" value="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
