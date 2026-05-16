<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id=$id"));
if (!$res) {
    header('Location: index.php');
    exit();
}

$customers = mysqli_query($conn, "SELECT * FROM customers ORDER BY full_name");
$rooms = mysqli_query($conn, "SELECT r.*, t.type_name FROM rooms r JOIN room_types t ON r.room_type_id = t.id ORDER BY r.room_number");
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $room_id = (int)$_POST['room_id'];
    $check_in = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_in']));
    $check_out = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_out']));
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $overlap = mysqli_query($conn, "SELECT id FROM reservations WHERE room_id=$room_id AND id != $id AND status NOT IN ('Cancelled','Checked-Out') AND (check_in < '$check_out' AND check_out > '$check_in')");
    if (mysqli_num_rows($overlap) > 0) {
        $error = 'Room is not available for the selected time slot!';
    } else {
        $query = "UPDATE reservations SET customer_id=$customer_id, room_id=$room_id, check_in='$check_in', 
                  check_out='$check_out', adults=$adults, children=$children, status='$status' WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $success = 'Reservation updated!';
            $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id=$id"));
        } else {
            $error = 'Failed: ' . mysqli_error($conn);
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Edit Reservation</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control" required>
                        <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $res['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['full_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room</label>
                    <select name="room_id" class="form-control" required>
                        <?php while ($r = mysqli_fetch_assoc($rooms)): ?>
                        <option value="<?= $r['id'] ?>" <?= $r['id'] == $res['room_id'] ? 'selected' : '' ?>><?= $r['room_number'] ?> - <?= $r['type_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-In</label>
                        <input type="datetime-local" name="check_in" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($res['check_in'])) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-Out</label>
                        <input type="datetime-local" name="check_out" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($res['check_out'])) ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control" value="<?= $res['adults'] ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control" value="<?= $res['children'] ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="Pending" <?= $res['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= $res['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Checked-In" <?= $res['status'] == 'Checked-In' ? 'selected' : '' ?>>Checked-In</option>
                        <option value="Checked-Out" <?= $res['status'] == 'Checked-Out' ? 'selected' : '' ?>>Checked-Out</option>
                        <option value="Cancelled" <?= $res['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
