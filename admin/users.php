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
            $_SESSION['success'] = 'User created successfully!';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    } elseif (isset($_POST['toggle_status'])) {
        $uid = (int)$_POST['user_id'];
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM users WHERE id=$uid"));
        $new_status = $user['status'] ? 0 : 1;
        mysqli_query($conn, "UPDATE users SET status=$new_status WHERE id=$uid");
        $_SESSION['success'] = 'User status updated!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } elseif (isset($_POST['update_user'])) {
        $uid = (int)$_POST['user_id'];
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        // Prevent self-role modification
        if ($uid == $_SESSION['user_id']) {
            $sql = "UPDATE users SET username='$username'"; // Do not update role_id
            $_SESSION['username'] = $username;
        } else {
            $sql = "UPDATE users SET username='$username', role_id=$role_id";
        }
        
        if (!empty($_POST['new_password'])) {
            $np = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $sql .= ", password='$np'";
        }
        
        mysqli_query($conn, $sql . " WHERE id=$uid");
        $_SESSION['success'] = 'User details updated successfully!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$role_id = isset($_GET['role_id']) ? (int)$_GET['role_id'] : 0;
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = [];
if ($search !== '') {
    $where[] = "u.username LIKE '%$search%'";
}
if ($role_id > 0) {
    $where[] = "u.role_id = $role_id";
}
if ($status !== '') {
    if ($status === '1') $where[] = "u.status = 1";
    if ($status === '0') $where[] = "u.status = 0";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

$users = mysqli_query($conn, "SELECT u.*, r.role_name FROM users u JOIN user_roles r ON u.role_id = r.id $where_clause ORDER BY u.id");
$roles = mysqli_query($conn, "SELECT * FROM user_roles");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <h2><i class="bi bi-gear"></i> User Management</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage system users and access controls</p>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search Username..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="role_id" class="form-select">
                        <option value="">All Roles</option>
                        <?php 
                        mysqli_data_seek($roles, 0);
                        while($r = mysqli_fetch_assoc($roles)): ?>
                        <option value="<?= $r['id'] ?>" <?= $role_id == $r['id'] ? 'selected' : '' ?>><?= $r['role_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $role_id || $status !== ''): ?>
                    <a href="users.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
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
                                <button class="btn btn-sm btn-outline-primary" onclick="showEdit(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', <?= $u['role_id'] ?>)"><i class="bi bi-pencil"></i> Edit</button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header"><h5><i class="bi bi-pencil me-2"></i>Edit User</h5></div>
            <div class="modal-body">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" id="edit_username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role_id" id="edit_role_id" class="form-select" required>
                        <?php
                        mysqli_data_seek($roles, 0);
                        while ($r = mysqli_fetch_assoc($roles)): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['role_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password <small class="text-muted">(Leave blank to keep current)</small></label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update_user" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEdit(id, username, roleId) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    
    var roleSelect = document.getElementById('edit_role_id');
    roleSelect.value = roleId;
    
    // Disable role select if editing self
    roleSelect.disabled = (id == <?= $_SESSION['user_id'] ?>);
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>