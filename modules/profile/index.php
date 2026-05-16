<?php
require_once "../../includes/session.php";
require_once "../../config/database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_profile"])) {
    $new_username = mysqli_real_escape_string($conn, $_POST["username"]);
    $uid = $_SESSION["user_id"];
    
    $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$new_username' AND id!=$uid");
    if (mysqli_num_rows($check) > 0) {
        $error = "Username already exists!";
    } else {
        mysqli_query($conn, "UPDATE users SET username='$new_username' WHERE id=$uid");
        $_SESSION["username"] = $new_username;
        $success = "Profile updated successfully!";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["change_password"])) {
    $current = $_POST["current_password"];
    $new = $_POST["new_password"];
    $confirm = $_POST["confirm_password"];
    $uid = $_SESSION["user_id"];
    
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT password FROM users WHERE id=$uid"));
    if (!password_verify($current, $user["password"])) {
        $error = "Current password is incorrect!";
    } elseif ($new !== $confirm) {
        $error = "New passwords do not match!";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password='$hash' WHERE id=$uid");
        $success = "Password changed successfully!";
    }
}

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=" . $_SESSION["user_id"]));
$role = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_name FROM user_roles WHERE id=" . $user["role_id"]));

include "../../includes/header.php";
include "../../includes/sidebar.php";
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2 class="mb-4">Profile Settings</h2>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if (isset($success)): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header"><h5>Update Profile</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user["username"]) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?= $role["role_name"] ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <input type="text" class="form-control" value="<?= $user["status"] ? "Active" : "Inactive" ?>" readonly>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header"><h5>Change Password</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "../includes/footer.php"; ?>
