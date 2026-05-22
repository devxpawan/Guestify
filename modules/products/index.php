<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!has_role(['Admin', 'Manager'])) {
        $_SESSION['error'] = 'Permission denied.';
        header("Location: index.php");
        exit();
    }
    $id = (int)$_POST['id'];
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM products WHERE id=$id AND " . active_villa_where_raw()));
    if ($item) {
        $new_status = $item['is_active'] ? 0 : 1;
        mysqli_query($conn, "UPDATE products SET is_active=$new_status WHERE id=$id AND " . active_villa_where_raw());
        $_SESSION['success'] = 'Product status updated successfully!';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$is_active_filter = isset($_GET['is_active']) ? mysqli_real_escape_string($conn, $_GET['is_active']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

$where = [active_villa_where_raw()];
if ($search !== '') {
    $where[] = "product_name LIKE '%$search%'";
}
if ($category !== '') {
    $where[] = "category = '$category'";
}
if ($is_active_filter !== '') {
    $where[] = "is_active = " . (int)$is_active_filter;
}
if ($date_from !== '') {
    $where[] = "DATE(created_at) >= '$date_from'";
}
if ($date_to !== '') {
    $where[] = "DATE(created_at) <= '$date_to'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$products = mysqli_query($conn, "SELECT * FROM products $where_clause ORDER BY id DESC LIMIT $offset, $per_page");
$categories_res = mysqli_query($conn, "SELECT id, category_name AS category FROM product_categories WHERE " . active_villa_where_raw() . " ORDER BY category_name");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-box-seam"></i> Product Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage inventory and product catalog</p>
            </div>
            <div class="d-flex gap-2">
                <a href="categories.php" class="btn btn-outline-secondary"><i class="fas fa-tags"></i> Manage Categories</a>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Product</a>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <label class="form-label small text-muted">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 form-control-sm" placeholder="Search Product Name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Category</label>
                    <select name="category" class="form-select form-select-sm">
                        <option value="">All Categories</option>
                        <?php while($c = mysqli_fetch_assoc($categories_res)): ?>
                        <option value="<?= htmlspecialchars($c['category']) ?>" <?= $category == $c['category'] ? 'selected' : '' ?>><?= htmlspecialchars($c['category']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">All States</option>
                        <option value="1" <?= $is_active_filter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $is_active_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $category || $is_active_filter !== '' || $date_from || $date_to): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th><th>Product Name</th><th>Category</th><th>Quantity</th><th>Price</th><th>Status</th><th>Actions</th>
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
                        <td><strong><?= htmlspecialchars($global_currency) . number_format($p['price'], 2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $p['is_active'] ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $p['is_active'] ? 'check-circle' : 'x-circle' ?>"></i> <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $p['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>" style="width: 36px;">
                                        <i class="fas fa-<?= $p['is_active'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit" style="width: 36px;"><i class="fas fa-pencil-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> products</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>