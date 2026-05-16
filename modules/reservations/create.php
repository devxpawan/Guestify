<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

$customers = mysqli_query($conn, "SELECT * FROM customers ORDER BY full_name");
$rooms = mysqli_query($conn, "SELECT r.*, t.type_name FROM rooms r JOIN room_types t ON r.room_type_id = t.id WHERE r.status='Available' ORDER BY r.room_number");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_new_customer = isset($_POST['is_new_customer']);
    $customer_id = 0;

    if ($is_new_customer) {
        $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
        $nic_passport = mysqli_real_escape_string($conn, $_POST['nic_passport']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        
        if (empty($full_name) || empty($nic_passport) || empty($phone)) {
            $error = 'Please fill all customer details!';
        } else {
            $c_query = "INSERT INTO customers (full_name, nic_passport, phone, email) VALUES ('$full_name', '$nic_passport', '$phone', '$email')";
            if (mysqli_query($conn, $c_query)) {
                $customer_id = mysqli_insert_id($conn);
            } else {
                $error = "Failed to create customer: " . mysqli_error($conn);
            }
        }
    } else {
        $customer_id = (int)$_POST['customer_id'];
        if ($customer_id <= 0) $error = 'Please select a customer!';
    }

    if (!$error) {
        $room_id = (int)$_POST['room_id'];
        $check_in = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_in']));
        $check_out = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_out']));
        $adults = (int)$_POST['adults'];
        $children = (int)$_POST['children'];

    if (strtotime($check_out) <= strtotime($check_in)) {
        $error = 'Check-out must be after check-in!';
    } else {
        // Precise datetime overlap check
        $overlap_query = "SELECT id FROM reservations 
                          WHERE room_id = $room_id 
                          AND status NOT IN ('Cancelled','Checked-Out') 
                          AND (
                              (check_in < '$check_out' AND check_out > '$check_in')
                          )";
        $overlap = mysqli_query($conn, $overlap_query);
        
        if (mysqli_num_rows($overlap) > 0) {
            $error = 'Room is not available for the selected time slot!';
        } else {
            $query = "INSERT INTO reservations (customer_id, room_id, check_in, check_out, adults, children, status) 
                      VALUES ($customer_id, $room_id, '$check_in', '$check_out', $adults, $children, 'Pending')";
            if (mysqli_query($conn, $query)) {
                $success = 'Reservation created successfully!';
            } else {
                $error = 'Failed: ' . mysqli_error($conn);
            }
        }
    }
}
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>New Reservation</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-4">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="isNewCustomer" name="is_new_customer">
                        <label class="form-check-label fw-bold" for="isNewCustomer">New Customer?</label>
                    </div>

                    <div id="existingCustomerDiv">
                        <label class="form-label text-muted">Select Existing Customer</label>
                        <select name="customer_id" id="customerId" class="form-control">
                            <option value="">Choose...</option>
                            <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['nic_passport']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="newCustomerDiv" style="display:none;" class="p-3 bg-light rounded border">
                        <h6 class="mb-3">Enter Customer Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Full Name *</label>
                                <input type="text" name="full_name" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">NIC / Passport *</label>
                                <input type="text" name="nic_passport" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Phone *</label>
                                <input type="text" name="phone" class="form-control form-control-sm">
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room</label>
                    <select name="room_id" class="form-control" required>
                        <option value="">Select Room</option>
                        <?php while ($r = mysqli_fetch_assoc($rooms)): ?>
                        <option value="<?= $r['id'] ?>"><?= $r['room_number'] ?> - <?= $r['type_name'] ?> ($<?= number_format($r['price'], 2) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-In Date & Time</label>
                        <input type="datetime-local" name="check_in" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-Out Date & Time</label>
                        <input type="datetime-local" name="check_out" class="form-control" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control" value="1" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control" value="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Create Reservation</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<script>
document.getElementById('isNewCustomer').addEventListener('change', function() {
    const isNew = this.checked;
    document.getElementById('existingCustomerDiv').style.display = isNew ? 'none' : 'block';
    document.getElementById('newCustomerDiv').style.display = isNew ? 'block' : 'none';
    
    // Toggle required attributes
    document.getElementById('customerId').required = !isNew;
    const newInputs = document.querySelectorAll('#newCustomerDiv input:not([name="email"])');
    newInputs.forEach(input => input.required = isNew);
});
</script>
<?php include '../../includes/footer.php'; ?>
