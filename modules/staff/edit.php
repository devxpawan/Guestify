<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE id=$id"));
$positions_query = mysqli_query($conn, "SELECT * FROM staff_positions ORDER BY position_name");
if (!$staff) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $nic = mysqli_real_escape_string($conn, $_POST['nic']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $salary = !empty($_POST['salary']) ? (float)$_POST['salary'] : 0;

    $query = "UPDATE staff SET name='$name', position='$position', phone='$phone', nic='$nic', email='$email', salary=$salary WHERE id=$id";
    if (mysqli_query($conn, $query)) {
        $old_staff_data = [
            'name' => $staff['name'],
            'position' => $staff['position'],
            'phone' => $staff['phone'],
            'nic' => $staff['nic'],
            'email' => $staff['email'],
            'salary' => $staff['salary']
        ];
        $new_staff_data = [
            'name' => $name,
            'position' => $position,
            'phone' => $phone,
            'nic' => $nic,
            'email' => $email,
            'salary' => $salary
        ];

        $_SESSION['success'] = 'Staff updated!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    } else {
        $error = 'Failed: ' . mysqli_error($conn);
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Edit Staff</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Position</label>
                    <select name="position" class="form-select" required>
                        <option value="">Select Position</option>
                        <?php while ($pos = mysqli_fetch_assoc($positions_query)): ?>
                        <option value="<?= htmlspecialchars($pos['position_name']) ?>" <?= $staff['position'] == $pos['position_name'] ? 'selected' : '' ?>><?= htmlspecialchars($pos['position_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($staff['phone']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">NIC Number</label>
                    <input type="text" name="nic" class="form-control" value="<?= htmlspecialchars($staff['nic']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Salary</label>
                    <input type="number" step="0.01" name="salary" class="form-control" value="<?= $staff['salary'] ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
