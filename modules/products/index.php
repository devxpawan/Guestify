<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-box-seam"></i> Product Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage inventory and product catalog</p>
            </div>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th><th>Product Name</th><th>Category</th><th>Quantity</th><th>Price</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($p = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><strong>#<?= $p['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-box"></i>
                                </div>
                                <?= htmlspecialchars($p['product_name']) ?>
                            </div>
                        </td>
                        <td><span class="badge badge-info"><?= htmlspecialchars($p['category']) ?></span></td>
                        <td>
                            <span class="badge badge-<?= $p['quantity'] > 10 ? 'success' : ($p['quantity'] > 0 ? 'warning' : 'danger') ?>">
                                <?= $p['quantity'] ?> in stock
                            </span>
                        </td>
                        <td><strong>$<?= number_format($p['price'], 2) ?></strong></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete"><i class="bi bi-trash"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
