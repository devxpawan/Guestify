<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/audit.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    verify_csrf_token();
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = (int)$_POST['role_id'];

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = 'Username already exists!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        mysqli_query($conn, "INSERT INTO users (username, password, role_id) VALUES ('$username', '$password', $role_id)");
        $new_user_id = mysqli_insert_id($conn);

        // Assign user to selected villas
        if (isset($_POST['villas']) && is_array($_POST['villas'])) {
            $is_default = true;
            foreach ($_POST['villas'] as $villa_id) {
                $villa_id = (int)$villa_id;
                $default_val = $is_default ? 1 : 0;
                mysqli_query($conn, "INSERT INTO user_villas (user_id, villa_id, is_default) VALUES ($new_user_id, $villa_id, $default_val)");
                $is_default = false;
            }
        }

        logAudit('CREATE', 'users', $new_user_id, "User $username created with role ID $role_id");
        $_SESSION['success'] = 'User created successfully!';
        header('Location: users.php');
        exit();
    }
}

$roles = mysqli_query($conn, "SELECT * FROM user_roles");
$villas_list = mysqli_query($conn, "SELECT * FROM villas WHERE status='Active' ORDER BY name");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="bi bi-person-plus"></i> Create New User</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Add a new system user with role assignment</p>
            </div>
            <a href="users.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Users</a>
        </div>
    </div>


    <div class="card shadow-sm">
        <div class="card-header"><h5><i class="fas fa-user-plus"></i> User Details</h5></div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required autofocus>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        <?php while ($r = mysqli_fetch_assoc($roles)): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['role_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Assign Villas</label>
                    <div class="row">
                        <?php while ($v = mysqli_fetch_assoc($villas_list)): ?>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input type="checkbox" name="villas[]" value="<?= $v['id'] ?>" class="form-check-input" id="villa_<?= $v['id'] ?>" <?= $v['id'] == active_villa_id() ? 'checked' : '' ?>>
                                <label class="form-check-label" for="villa_<?= $v['id'] ?>"><?= htmlspecialchars($v['name']) ?></label>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" name="add_user" class="btn btn-primary"><i class="fas fa-check"></i> Create User</button>
                    <a href="users.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>