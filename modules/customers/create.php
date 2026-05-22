<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    $villa_id = active_villa_id();
    $query = "INSERT INTO customers (full_name, nic_passport, phone, email, address, villa_id) 
              VALUES ('$full_name', '$nic_passport', '$phone', '$email', '$address', $villa_id)";
    if (mysqli_query($conn, $query)) {
        $customer_id = mysqli_insert_id($conn);
        logAudit('CREATE', 'customers', $customer_id, "Customer $full_name added");
        $_SESSION['success'] = 'Customer added successfully!';
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
    <h2>Add Customer</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">NIC / Passport</label>
                    <input type="text" name="nic_passport" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" maxlength="10" oninput="this.value = this.value.replace(/\D/g, '')" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Customer</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
