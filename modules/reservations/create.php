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
    $customer_id = (int)$_POST['customer_id'];
    $room_id = (int)$_POST['room_id'];
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];

    if (strtotime($check_out) <= strtotime($check_in)) {
        $error = 'Check-out must be after check-in!';
    } else {
        $overlap = mysqli_query($conn, "SELECT id FROM reservations WHERE room_id=$room_id AND status NOT IN ('Cancelled','Checked-Out') AND ((check_in <= '$check_out' AND check_out >= '$check_in'))");
        if (mysqli_num_rows($overlap) > 0) {
            $error = 'Room is not available for the selected dates!';
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
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">Select Customer</option>
                        <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['nic_passport']) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                    <small><a href="../customers/create.php" target="_blank">+ Add New Customer</a></small>
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
                        <label class="form-label">Check-In Date</label>
                        <input type="date" name="check_in" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-Out Date</label>
                        <input type="date" name="check_out" class="form-control" required>
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
<?php include '../../includes/footer.php'; ?>
