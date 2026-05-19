<?php
require_once '../includes/session.php';
require_once '../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_id = (int)$_POST['role_id'];

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Username already exists!';
    } else {
        mysqli_query($conn, "INSERT INTO users (username, password, role_id) VALUES ('$username', '$password', $role_id)");
        $_SESSION['success'] = 'User created successfully!';
        header('Location: users.php');
        exit();
    }
}

$roles = mysqli_query($conn, "SELECT * FROM user_roles");

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

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header"><h5><i class="fas fa-user-plus"></i> User Details</h5></div>
        <div class="card-body">
            <form method="POST" class="row g-3">
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
                    <button type="submit" name="add_user" class="btn btn-primary"><i class="fas fa-check"></i> Create User</button>
                    <a href="users.php" class="btn btn-outline-secondary ms-2"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>