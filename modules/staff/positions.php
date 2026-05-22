<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: ../../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = mysqli_real_escape_string($conn, trim($_POST['position_name']));
        if (empty($name)) {
            $_SESSION['error'] = 'Position name is required.';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $check = mysqli_query($conn, "SELECT id FROM staff_positions WHERE position_name='$name' AND " . active_villa_where_raw());
            if (mysqli_num_rows($check) > 0) {
                $_SESSION['error'] = 'Position already exists.';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                $villa_id = active_villa_id();
                mysqli_query($conn, "INSERT INTO staff_positions (villa_id, position_name) VALUES ($villa_id, '$name')");
                $position_id = mysqli_insert_id($conn);
                $_SESSION['success'] = 'Position added successfully!';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = mysqli_real_escape_string($conn, trim($_POST['position_name']));
        if (!empty($name)) {
            $old_position = mysqli_fetch_assoc(mysqli_query($conn, "SELECT position_name FROM staff_positions WHERE id=$id AND " . active_villa_where_raw()));
            mysqli_query($conn, "UPDATE staff_positions SET position_name='$name' WHERE id=$id AND " . active_villa_where_raw());
            $_SESSION['success'] = 'Position updated!';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $deleted_position = mysqli_fetch_assoc(mysqli_query($conn, "SELECT position_name FROM staff_positions WHERE id=$id AND " . active_villa_where_raw()));
        mysqli_query($conn, "DELETE FROM staff_positions WHERE id=$id AND " . active_villa_where_raw());
        $_SESSION['success'] = 'Position deleted!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

$positions = mysqli_query($conn, "SELECT * FROM staff_positions WHERE " . active_villa_where_raw() . " ORDER BY position_name");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-tags"></i> Staff Positions</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage staff position types</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Staff</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-plus"></i> Add New Position</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Position Name</label>
                            <input type="text" name="position_name" class="form-control" placeholder="e.g. Manager" required autofocus>
                        </div>
                        <button type="submit" name="add" class="btn btn-primary"><i class="fas fa-check"></i> Add Position</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header"><h5><i class="fas fa-list"></i> Existing Positions</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>#</th><th>Position</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php while ($p = mysqli_fetch_assoc($positions)): ?>
                                <tr>
                                    <td><strong>#<?= $p['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($p['position_name']) ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <button class="btn btn-sm btn-outline-warning" onclick="editPosition(<?= $p['id'] ?>, '<?= htmlspecialchars($p['position_name']) ?>')"><i class="fas fa-pencil-alt"></i></button>
                                            <form method="POST" style="display:inline" onsubmit="return confirm('Delete this position?')">
                                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                                <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
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
            <div class="modal-header"><h5><i class="fas fa-pencil-alt me-2"></i>Edit Position</h5></div>
            <div class="modal-body">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-3">
                    <label class="form-label">Position Name</label>
                    <input type="text" name="position_name" id="edit_name" class="form-control" required>
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
function editPosition(id, name) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include '../../includes/footer.php'; ?>