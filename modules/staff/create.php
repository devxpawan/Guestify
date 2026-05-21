<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

$positions_query = mysqli_query($conn, "SELECT * FROM staff_positions ORDER BY position_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $nic = mysqli_real_escape_string($conn, $_POST['nic']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $salary = !empty($_POST['salary']) ? (float)$_POST['salary'] : 0;

    $query = "INSERT INTO staff (name, position, phone, nic, email, salary) VALUES ('$name', '$position', '$phone', '$nic', '$email', $salary)";
    if (mysqli_query($conn, $query)) {
        $staff_id = mysqli_insert_id($conn);
        $_SESSION['success'] = 'Staff added successfully!';
        header("Location: index.php");
        exit();
    } else {
        $error = 'Failed: ' . mysqli_error($conn);
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Add Staff</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Position</label>
                    <select name="position" class="form-select" required>
                        <option value="">Select Position</option>
                        <?php while ($pos = mysqli_fetch_assoc($positions_query)): ?>
                        <option value="<?= htmlspecialchars($pos['position_name']) ?>"><?= htmlspecialchars($pos['position_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">NIC Number</label>
                    <input type="text" name="nic" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Salary</label>
                    <input type="number" step="0.01" name="salary" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary">Save Staff</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
