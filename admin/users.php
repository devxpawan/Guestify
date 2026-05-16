<?php
require_once '../includes/session.php';
require_once '../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role_id = (int)$_POST['role_id'];

        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Username already exists!';
        } else {
            mysqli_query($conn, "INSERT INTO users (username, password, role_id) VALUES ('$username', '$password', $role_id)");
            $success = 'User created successfully!';
        }
    } elseif (isset($_POST['toggle_status'])) {
        $uid = (int)$_POST['user_id'];
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM users WHERE id=$uid"));
        $new_status = $user['status'] ? 0 : 1;
        mysqli_query($conn, "UPDATE users SET status=$new_status WHERE id=$uid");
        $success = 'User status updated!';
    } elseif (isset($_POST['reset_password'])) {
        $uid = (int)$_POST['user_id'];
        $np = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$np' WHERE id=$uid");
        $success = 'Password reset successfully!';
    }
}

$users = mysqli_query($conn, "SELECT u.*, r.role_name FROM users u JOIN user_roles r ON u.role_id = r.id ORDER BY u.id");
$roles = mysqli_query($conn, "SELECT * FROM user_roles");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <h2><i class="bi bi-gear"></i> User Management</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage system users and access controls</p>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

    <div class="card mb-4">
        <div class="card-header"><h5><i class="bi bi-person-plus"></i> Create New User</h5></div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        <?php while ($r = mysqli_fetch_assoc($roles)): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['role_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" name="add_user" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Username</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><strong>#<?= $u['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <?= htmlspecialchars($u['username']) ?>
                            </div>
                        </td>
                        <td><span class="badge badge-purple"><?= $u['role_name'] ?></span></td>
                        <td>
                            <span class="badge badge-<?= $u['status'] ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $u['status'] ? 'check-circle' : 'x-circle' ?>"></i> <?= $u['status'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $u['status'] ? 'outline-warning' : 'outline-success' ?>">
                                        <i class="bi bi-<?= $u['status'] ? 'pause' : 'play' ?>"></i> <?= $u['status'] ? 'Deactivate' : 'Activate' ?>
                                    </button>
                                </form>
                                <button class="btn btn-sm btn-outline-info" onclick="showReset(<?= $u['id'] ?>)"><i class="bi bi-key"></i> Reset Pwd</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5><i class="bi bi-key me-2"></i>Reset Password</h5></div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="reset_password" class="btn btn-primary"><i class="bi bi-check-lg"></i> Reset</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showReset(id) {
    document.getElementById('reset_user_id').value = id;
    new bootstrap.Modal(document.getElementById('resetModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>
