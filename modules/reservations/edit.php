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
$room_types = mysqli_query($conn, "SELECT * FROM room_types ORDER BY type_name");
$current_room_id = (int)$res['room_id'];
$current_room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_type_id FROM rooms WHERE id = $current_room_id"));
$current_room_type_id = $current_room ? (int)$current_room['room_type_id'] : 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = (int)$_POST['customer_id'];
    $room_id = (int)$_POST['room_id'];
    $booking_type = mysqli_real_escape_string($conn, $_POST['booking_type'] ?? 'Night Time');
    $check_in = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_in']));
    $check_out = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_out']));
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($room_id)) {
        $error = 'Please select an available room!';
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $error = 'Check-out must be after check-in!';
    } else {
        // Precise datetime overlap check excluding current reservation
        $overlap = mysqli_query($conn, "SELECT id FROM reservations 
                                        WHERE room_id=$room_id 
                                        AND id != $id 
                                        AND status NOT IN ('Cancelled','Checked-Out') 
                                        AND (check_in < '$check_out' AND check_out > '$check_in')");
        if (mysqli_num_rows($overlap) > 0) {
            $error = 'Room is not available for the selected time slot!';
        } else {
            $query = "UPDATE reservations SET customer_id=$customer_id, room_id=$room_id, booking_type='$booking_type', check_in='$check_in', 
                      check_out='$check_out', adults=$adults, children=$children, status='$status' WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $success = 'Reservation updated successfully!';
                $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id=$id"));
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
    <h2>Edit Reservation</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <!-- Customer Selection Section -->
                <div class="mb-3">
                    <label class="form-label text-muted">Customer</label>
                    <select name="customer_id" class="form-control" required>
                        <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                        <option value="<?= $c['id'] ?>" <?= $c['id'] == $res['customer_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['nic_passport']) ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <!-- Check-In & Check-Out Date Selector First -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-In Date & Time</label>
                        <input type="datetime-local" name="check_in" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($res['check_in'])) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-Out Date & Time</label>
                        <input type="datetime-local" name="check_out" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($res['check_out'])) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Booking Type</label>
                        <select name="booking_type" id="bookingType" class="form-control" required>
                            <option value="Night Time" <?= (isset($_POST['booking_type']) ? $_POST['booking_type'] == 'Night Time' : $res['booking_type'] == 'Night Time') ? 'selected' : '' ?>>Night Time (Overnight)</option>
                            <option value="Day Time" <?= (isset($_POST['booking_type']) ? $_POST['booking_type'] == 'Day Time' : $res['booking_type'] == 'Day Time') ? 'selected' : '' ?>>Day Time (Day Use)</option>
                            <option value="Short Time" <?= (isset($_POST['booking_type']) ? $_POST['booking_type'] == 'Short Time' : $res['booking_type'] == 'Short Time') ? 'selected' : '' ?>>Short Time</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" id="roomTypeId" class="form-control" required disabled>
                            <option value="">Please select Check-In & Check-Out dates first...</option>
                            <?php mysqli_data_seek($room_types, 0); while ($t = mysqli_fetch_assoc($room_types)): ?>
                            <option value="<?= $t['id'] ?>" <?= (isset($_POST['room_type_id']) ? $_POST['room_type_id'] == $t['id'] : $current_room_type_id == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Room Selection -->
                <div class="mb-3">
                    <label class="form-label">Available Room</label>
                    <select name="room_id" class="form-control" required disabled>
                        <option value="">Please select Room Type first...</option>
                    </select>
                </div>

                <!-- Room Details / Adults & Children -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control" value="<?= htmlspecialchars($res['adults']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control" value="<?= htmlspecialchars($res['children']) ?>">
                    </div>
                </div>

                <!-- Reservation Status -->
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

                <button type="submit" class="btn btn-primary">Update Reservation</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    const checkInInput = $('input[name="check_in"]');
    const checkOutInput = $('input[name="check_out"]');
    const bookingTypeSelect = $('#bookingType');
    const roomTypeSelect = $('#roomTypeId');
    const roomSelect = $('select[name="room_id"]');
    const originalSelectedRoom = "<?= isset($_POST['room_id']) ? (int)$_POST['room_id'] : $res['room_id'] ?>";
    const excludeResId = "<?= $id ?>";

    function updateRoomTypeSelector() {
        const checkIn = checkInInput.val();
        const checkOut = checkOutInput.val();

        if (!checkIn || !checkOut || new Date(checkOut) <= new Date(checkIn)) {
            roomTypeSelect.prop('disabled', true).val('');
            roomTypeSelect.find('option:first').text('Please select Check-In & Check-Out dates first...');
            roomSelect.prop('disabled', true).html('<option value="">Please select Check-In & Check-Out dates first...</option>');
            return;
        }

        roomTypeSelect.prop('disabled', false);
        roomTypeSelect.find('option:first').text('Select Room Type');
        fetchAvailableRooms();
    }

    function fetchAvailableRooms() {
        const checkIn = checkInInput.val();
        const checkOut = checkOutInput.val();
        const roomTypeId = roomTypeSelect.val();
        const bookingType = bookingTypeSelect.val();

        if (!checkIn || !checkOut || new Date(checkOut) <= new Date(checkIn)) {
            return;
        }

        if (!roomTypeId) {
            roomSelect.prop('disabled', true).html('<option value="">Please select Room Type first...</option>');
            return;
        }

        roomSelect.prop('disabled', true).html('<option value="">Loading available rooms...</option>');

        $.ajax({
            url: 'get_available_rooms.php',
            type: 'GET',
            data: {
                check_in: checkIn,
                check_out: checkOut,
                room_type_id: roomTypeId,
                booking_type: bookingType,
                exclude_res_id: excludeResId
            },
            dataType: 'json',
            success: function(rooms) {
                if (rooms.error) {
                    roomSelect.html('<option value="">' + rooms.error + '</option>');
                    return;
                }

                if (rooms.length === 0) {
                    roomSelect.html('<option value="">No rooms available for this type</option>');
                    return;
                }

                let options = '<option value="">Select Room</option>';
                rooms.forEach(function(room) {
                    const isSelected = room.id == originalSelectedRoom ? 'selected' : '';
                    options += `<option value="${room.id}" ${isSelected}>Room ${room.room_number} - ${room.type_name} (<?= htmlspecialchars($global_currency) ?>${parseFloat(room.price).toFixed(2)})</option>`;
                });

                roomSelect.html(options).prop('disabled', false);
            },
            error: function() {
                roomSelect.html('<option value="">Failed to fetch available rooms</option>');
            }
        });
    }

    checkInInput.on('change', updateRoomTypeSelector);
    checkOutInput.on('change', updateRoomTypeSelector);
    bookingTypeSelect.on('change', fetchAvailableRooms);
    roomTypeSelect.on('change', fetchAvailableRooms);

    // Auto-trigger if values are already filled (on page load / form validation error reload)
    if (checkInInput.val() && checkOutInput.val()) {
        roomTypeSelect.prop('disabled', false);
        roomTypeSelect.find('option:first').text('Select Room Type');
        if (roomTypeSelect.val()) {
            fetchAvailableRooms();
        }
    }
});
</script>
<?php include '../../includes/footer.php'; ?>
