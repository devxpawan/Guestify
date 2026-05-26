<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../../dashboard.php');
    exit();
}

// Migration: add is_active column if not exists
try {
    mysqli_query($conn, "ALTER TABLE product_categories ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER category_name");
} catch (mysqli_sql_exception $e) {
    // Column already exists, ignore
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    if (isset($_POST['add'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
        if (empty($name)) {
            $_SESSION['error'] = 'Category name is required.';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $check = mysqli_query($conn, "SELECT id FROM product_categories WHERE category_name='$name' AND " . active_villa_where_raw());
            if (mysqli_num_rows($check) > 0) {
                $_SESSION['error'] = 'Category already exists.';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                $villa_id = (int)active_villa_id();
                mysqli_query($conn, "INSERT INTO product_categories (category_name, villa_id) VALUES ('$name', $villa_id)");
                $_SESSION['success'] = 'Category added successfully!';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
        if (!empty($name)) {
            mysqli_query($conn, "UPDATE product_categories SET category_name='$name' WHERE id=$id AND " . active_villa_where_raw());
            $_SESSION['success'] = 'Category updated!';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } elseif (isset($_POST['toggle_status'])) {
        $id = (int)$_POST['id'];
        $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM product_categories WHERE id=$id AND " . active_villa_where_raw()));
        if ($item) {
            $new_status = $item['is_active'] ? 0 : 1;
            mysqli_query($conn, "UPDATE product_categories SET is_active=$new_status WHERE id=$id AND " . active_villa_where_raw());
            $_SESSION['success'] = 'Category status updated!';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM product_categories WHERE " . active_villa_where_raw() . " ORDER BY category_name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-tags"></i> Product Categories</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage product category types</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-plus"></i> Add New Category</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Beverages" required autofocus>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary"><i class="fas fa-check"></i> Add Category</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-list"></i> Existing Categories</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>#</th><th>Category</th><th>Status</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($c = mysqli_fetch_assoc($categories)): ?>
                                <tr>
                                    <td><strong>#<?= $c['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($c['category_name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $c['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-sm btn-outline-warning" onclick="editCategory(<?= $c['id'] ?>, '<?= htmlspecialchars($c['category_name']) ?>')"><i class="fas fa-pencil-alt"></i></button>
                                            <form method="POST" style="display:inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $c['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>" style="width: 36px;">
                                                    <i class="fas fa-<?= $c['is_active'] ? 'ban' : 'check' ?>"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header"><h5><i class="fas fa-pencil-alt me-2"></i>Edit Category</h5></div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">Category Name</label>
                    <input type="text" name="category_name" id="edit_name" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="edit" class="btn btn-primary"><i class="fas fa-check"></i> Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include '../../includes/footer.php'; ?>