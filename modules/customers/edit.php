<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

$id = (int)$_GET['id'];
$customer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM customers WHERE id=$id AND " . active_villa_where()));
if (!$customer) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $nic_passport = mysqli_real_escape_string($conn, $_POST['nic_passport']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    if (!empty($email) && !str_contains($email, '@')) {
        $_SESSION['error'] = 'Email must contain @.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if (!empty($phone) && (!ctype_digit($phone) || strlen($phone) !== 10)) {
        $_SESSION['error'] = 'Phone must be exactly 10 digits.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $query = "UPDATE customers SET full_name='$full_name', nic_passport='$nic_passport', phone='$phone', email='$email', address='$address' WHERE id=$id AND " . active_villa_where();
    if (mysqli_query($conn, $query)) {
        $old_customer_data = [
            'full_name' => $customer['full_name'],
            'nic_passport' => $customer['nic_passport'],
            'phone' => $customer['phone'],
            'email' => $customer['email'],
            'address' => $customer['address']
        ];
        $new_customer_data = [
            'full_name' => $full_name,
            'nic_passport' => $nic_passport,
            'phone' => $phone,
            'email' => $email,
            'address' => $address
        ];

        logAudit('UPDATE', 'customers', $id, "Customer $full_name updated", $old_customer_data, $new_customer_data);

        $_SESSION['success'] = 'Customer updated!';
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
    <h2>Edit Customer</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($customer['full_name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">NIC / Passport</label>
                    <input type="text" name="nic_passport" class="form-control" value="<?= htmlspecialchars($customer['nic_passport']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone']) ?>" maxlength="10" oninput="this.value = this.value.replace(/\D/g, '')" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($customer['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($customer['address']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
