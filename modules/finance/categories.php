<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: index.php');
    exit();
}



// Handle add/edit/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        if ($name === '') {
            $_SESSION['error'] = 'Category name is required.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $check = mysqli_query($conn, "SELECT id FROM finance_categories WHERE name='$name' AND type='$type' AND " . active_villa_where_raw());
            if (mysqli_num_rows($check) > 0) {
                $_SESSION['error'] = 'Category already exists.';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $villa_id = (int)active_villa_id();
                mysqli_query($conn, "INSERT INTO finance_categories (name, type, villa_id) VALUES ('$name', '$type', $villa_id)");
                $_SESSION['success'] = 'Category added successfully!';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        }
    } elseif (isset($_POST['edit_category'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['name']));
        $type = mysqli_real_escape_string($conn, $_POST['type']);
        if ($name !== '') {
            mysqli_query($conn, "UPDATE finance_categories SET name='$name', type='$type' WHERE id=$id AND " . active_villa_where_raw());
            $_SESSION['success'] = 'Category updated successfully!';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } elseif (isset($_POST['delete_category'])) {
        $id = (int)$_POST['id'];
        $check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM transactions WHERE category_id=$id AND " . active_villa_where_raw());
        $used = mysqli_fetch_assoc($check)['cnt'];
        if ($used > 0) {
            $_SESSION['error'] = 'Cannot delete: category has ' . $used . ' transaction(s) linked.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            mysqli_query($conn, "DELETE FROM finance_categories WHERE id=$id AND " . active_villa_where_raw());
            $_SESSION['success'] = 'Category deleted successfully!';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM finance_categories WHERE " . active_villa_where_raw() . " ORDER BY type, name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-tags"></i> Finance Categories</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage income and expense categories</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Add Category</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select name="type" class="form-select" required>
                                <option value="Income">Income</option>
                                <option value="Expense">Expense</option>
                            </select>
                        </div>
                        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Existing Categories</h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Name</th><th>Type</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($cat['name']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $cat['type'] === 'Income' ? 'success' : 'danger' ?>">
                                            <?= $cat['type'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>', '<?= $cat['type'] ?>')">
                                                <i class="fas fa-pencil-alt"></i>
                                            </button>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                                                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                                <button type="submit" name="delete_category" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editId">
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select name="type" id="editType" class="form-select" required>
                        <option value="Income">Income</option>
                        <option value="Expense">Expense</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="edit_category" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name, type) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editType').value = type;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>
