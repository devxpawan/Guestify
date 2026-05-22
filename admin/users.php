<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/pagination.php';
require_once '../includes/audit.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_status'])) {
        $uid = (int)$_POST['user_id'];
        if ($uid == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot deactivate your own account.';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM users WHERE id=$uid"));
        $new_status = $user['status'] ? 0 : 1;
        mysqli_query($conn, "UPDATE users SET status=$new_status WHERE id=$uid");
        $action_text = $new_status ? 'activated' : 'deactivated';
        logAudit('UPDATE', 'users', $uid, "User status $action_text");
        
        $_SESSION['success'] = 'User status updated!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } elseif (isset($_POST['update_user'])) {
        $uid = (int)$_POST['user_id'];
        
        $old_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username, role_id FROM users WHERE id=$uid"));
        
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $role_id = (int)($_POST['role_id'] ?? 0);
        
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' AND id != $uid");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['error'] = 'Username is already taken.';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
        
        if ($uid == $_SESSION['user_id']) {
            $sql = "UPDATE users SET username='$username'";
            $_SESSION['username'] = $username;
        } else {
            $sql = "UPDATE users SET username='$username', role_id=$role_id";
        }
        
        if (!empty($_POST['new_password'])) {
            $np = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $sql .= ", password='$np'";
        }
        
        mysqli_query($conn, $sql . " WHERE id=$uid");
        
        // Sync villa assignments
        $submitted_villas = isset($_POST['villas']) ? array_map('intval', $_POST['villas']) : [];
        if (count($submitted_villas) > 0) {
            mysqli_query($conn, "DELETE FROM user_villas WHERE user_id = $uid");
            $first = true;
            foreach ($submitted_villas as $v_id) {
                $default_val = $first ? 1 : 0;
                mysqli_query($conn, "INSERT INTO user_villas (user_id, villa_id, is_default) VALUES ($uid, $v_id, $default_val)");
                $first = false;
            }
        }

        $new_user_data = ['username' => $username, 'role_id' => $role_id];
        logAudit('UPDATE', 'users', $uid, "User $username updated", $old_user, $new_user_data);
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

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users u $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$users = mysqli_query($conn, "SELECT u.*, r.role_name,
    (SELECT GROUP_CONCAT(v.name SEPARATOR ', ') FROM user_villas uv JOIN villas v ON uv.villa_id = v.id WHERE uv.user_id = u.id) AS villa_names
    FROM users u JOIN user_roles r ON u.role_id = r.id $where_clause ORDER BY u.id LIMIT $offset, $per_page");
$roles = mysqli_query($conn, "SELECT * FROM user_roles");
$all_villas = mysqli_query($conn, "SELECT * FROM villas WHERE status='Active' ORDER BY name");

// Pre-fetch user villa assignments
$user_villa_map = [];
$uvq = mysqli_query($conn, "SELECT user_id, villa_id FROM user_villas");
while ($uv = mysqli_fetch_assoc($uvq)) {
    $user_villa_map[$uv['user_id']][] = $uv['villa_id'];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-gear"></i> User Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage system users and access controls</p>
            </div>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
        </div>
    </div>

    <?php
    $current_villa_id = active_villa_id();
    $villa_users = mysqli_query($conn, "
        SELECT u.id, u.username, u.status
        FROM users u
        JOIN user_villas uv ON u.id = uv.user_id
        WHERE uv.villa_id = $current_villa_id
        ORDER BY u.username
    ");
    ?>
    <div class="card mb-3 shadow-sm border-primary">
        <div class="card-body py-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <i class="bi bi-building text-primary"></i>
                <strong class="text-primary me-2">Current Villa Users:</strong>
                <?php $villa_user_list = []; while ($vu = mysqli_fetch_assoc($villa_users)) { $villa_user_list[] = '<span class="badge ' . ($vu['status'] ? 'bg-primary' : 'bg-secondary') . '">' . htmlspecialchars($vu['username']) . '</span>'; } ?>
                <?= $villa_user_list ? implode(' ', $villa_user_list) : '<span class="text-muted small">No users assigned</span>' ?>
            </div>
        </div>
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

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Username</th><th>Role</th><th>Villas</th><th>Status</th><th>Created</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php while ($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><strong>#<?= $u['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?= htmlspecialchars($u['username']) ?>
                            </div>
                        </td>
                        <td><span class="badge badge-purple"><?= $u['role_name'] ?></span></td>
                        <td><small class="text-muted"><?= htmlspecialchars($u['villa_names'] ?? 'None') ?></small></td>
                        <td>
                            <span class="badge badge-<?= $u['status'] ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $u['status'] ? 'check-circle' : 'x-circle' ?>"></i> <?= $u['status'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><small class="text-muted"><?= date('M d, Y', strtotime($u['created_at'])) ?></small></td>
                        <td>
                            <div class="action-btns">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-sm btn-<?= $u['status'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $u['status'] ? 'Deactivate' : 'Activate' ?>" style="width: 36px;" onclick="showToggleConfirm(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', <?= $u['status'] ?>)">
                                    <i class="fas fa-<?= $u['status'] ? 'ban' : 'check' ?>"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-primary" onclick="showEdit(<?= $u['id'] ?>, '<?= htmlspecialchars($u['username']) ?>', <?= $u['role_id'] ?>, [<?= implode(',', $user_villa_map[$u['id']] ?? []) ?>])" title="Edit" style="width: 36px;"><i class="fas fa-pencil-alt"></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> users</small>
        <?php render_pagination($page, $total_pages); ?>
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
                <div class="mb-3">
                    <label class="form-label">Assign Villas</label>
                    <div class="row" id="edit_villas_container">
                        <?php mysqli_data_seek($all_villas, 0); while ($v = mysqli_fetch_assoc($all_villas)): ?>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="villas[]" value="<?= $v['id'] ?>" class="form-check-input edit-villa-checkbox" id="edit_villa_<?= $v['id'] ?>">
                                <label class="form-check-label" for="edit_villa_<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></label>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" name="update_user" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i> Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="toggleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-body text-center py-4">
                <input type="hidden" name="user_id" id="toggle_user_id">
                <input type="hidden" name="toggle_status" value="1">
                <div class="mb-3">
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                </div>
                <h5 id="toggle_modal_title">Confirm</h5>
                <p class="text-muted mb-0" id="toggle_modal_message"></p>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0">
                <button type="submit" class="btn btn-warning" id="toggle_confirm_btn"><i class="fas fa-check"></i> Yes</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fas fa-times"></i> No</button>
            </div>
        </form>
    </div>
</div>

<script>
function showToggleConfirm(id, username, status) {
    document.getElementById('toggle_user_id').value = id;
    document.getElementById('toggle_modal_title').textContent = status ? 'Deactivate User?' : 'Activate User?';
    document.getElementById('toggle_modal_message').textContent = 'Are you sure you want to ' + (status ? 'deactivate' : 'activate') + ' "' + username + '"?';
    var btn = document.getElementById('toggle_confirm_btn');
    btn.className = 'btn btn-' + (status ? 'warning' : 'success');
    btn.innerHTML = '<i class="fas fa-check"></i> Yes, ' + (status ? 'Deactivate' : 'Activate');
    new bootstrap.Modal(document.getElementById('toggleModal')).show();
}

function showEdit(id, username, roleId, villaIds) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_username').value = username;
    
    var roleSelect = document.getElementById('edit_role_id');
    roleSelect.value = roleId;
    roleSelect.disabled = (id == <?= $_SESSION['user_id'] ?>);
    
    // Check assigned villa boxes
    document.querySelectorAll('.edit-villa-checkbox').forEach(function(cb) {
        cb.checked = villaIds.includes(parseInt(cb.value));
    });
    
    new bootstrap.Modal(document.getElementById('editModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>