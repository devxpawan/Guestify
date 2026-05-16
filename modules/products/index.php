<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Product Management</h2>
        <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Product Name</th><th>Category</th><th>Quantity</th><th>Price</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['product_name']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td><?= $p['quantity'] ?></td>
                        <td>$<?= number_format($p['price'], 2) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
