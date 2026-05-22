<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

$id = (int)$_GET['id'];
$product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));
$categories_query = mysqli_query($conn, "SELECT * FROM product_categories ORDER BY category_name");
if (!$product) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = (int)$_POST['quantity'];
    $price = (float)$_POST['price'];

    $query = "UPDATE products SET product_name='$product_name', category='$category', quantity=$quantity, price=$price WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        $old_product_data = [
            'product_name' => $product['product_name'],
            'category' => $product['category'],
            'quantity' => $product['quantity'],
            'price' => $product['price']
        ];
        $new_product_data = [
            'product_name' => $product_name,
            'category' => $category,
            'quantity' => $quantity,
            'price' => $price
        ];

        logAudit('UPDATE', 'products', $id, "Product $product_name updated", $old_product_data, $new_product_data);

        $_SESSION['success'] = 'Product updated!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
        $product = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$id"));
    } else {
        $error = 'Failed: ' . mysqli_error($conn);
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Edit Product</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="product_name" class="form-control" value="<?= htmlspecialchars($product['product_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php while ($cat = mysqli_fetch_assoc($categories_query)): ?>
                        <option value="<?= htmlspecialchars($cat['category_name']) ?>" <?= $product['category'] == $cat['category_name'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" class="form-control" value="<?= $product['quantity'] ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Price</label>
                    <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
