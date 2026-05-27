<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$villa_id = active_villa_id();

// Support both single reservation (id) and group (gid)
if (isset($_GET['gid'])) {
    $gid = (int)$_GET['gid'];
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations r WHERE (r.id = $gid OR r.group_id = $gid) AND " . active_villa_where('r') . " ORDER BY r.id ASC LIMIT 1"));
    if (!$res || $res['status'] !== 'Pending') {
        header('Location: index.php');
        exit();
    }
    $id = (int)$res['id'];
    $group_reservations = mysqli_query($conn, "SELECT r.*, rm.room_number, t.type_name FROM reservations r JOIN rooms rm ON r.room_id = rm.id JOIN room_types t ON rm.room_type_id = t.id WHERE (r.id = $gid OR r.group_id = $gid) AND " . active_villa_where('r') . " ORDER BY r.id ASC");
    $is_group = mysqli_num_rows($group_reservations) > 1;
} else {
    $id = (int)$_GET['id'];
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations r WHERE r.id=$id AND " . active_villa_where('r')));
    if (!$res || $res['status'] !== 'Pending') {
        header('Location: index.php');
        exit();
    }
    $gid = null;
    $is_group = false;
}

$customer = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM customers WHERE id = {$res['customer_id']}"));
$room_types = mysqli_query($conn, "SELECT * FROM room_types ORDER BY type_name");
$current_room_id = (int)$res['room_id'];
$current_room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_type_id FROM rooms WHERE id = $current_room_id"));
$current_room_type_id = $current_room ? (int)$current_room['room_type_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $nic_passport = mysqli_real_escape_string($conn, $_POST['nic_passport']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);

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
    if (empty($full_name) || empty($nic_passport) || empty($phone)) {
        $_SESSION['error'] = 'Please fill all customer details!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $booking_type = mysqli_real_escape_string($conn, $_POST['booking_type'] ?? 'Night Time');
    $check_in = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_in']));
    $check_out = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_out']));
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $discount = (float)($_POST['discount'] ?? 0);
    if ($discount < 0) $discount = 0;

    if (strtotime($check_out) <= strtotime($check_in)) {
        $_SESSION['error'] = 'Check-out must be after check-in!';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // Build list of affected reservation IDs (current group or single)
    $all_ids = [$id];
    $exclude_ids_pool = [$id];
    if ($gid) {
        $grp = mysqli_query($conn, "SELECT id FROM reservations WHERE (id = $gid OR group_id = $gid) AND id != $id");
        while ($gr = mysqli_fetch_assoc($grp)) {
            $all_ids[] = (int)$gr['id'];
            $exclude_ids_pool[] = (int)$gr['id'];
        }
    }

    // Handle room removals (unlink from group)
    if ($is_group && isset($_POST['remove_room_ids'])) {
        foreach ($_POST['remove_room_ids'] as $rid) {
            $rid = (int)$rid;
            if ($rid <= 0) continue;
            // Unlink from group so it becomes standalone
            mysqli_query($conn, "UPDATE reservations SET group_id = NULL, status='Cancelled' WHERE id = $rid AND villa_id = $villa_id");
            // Free the room
            $rr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_id FROM reservations WHERE id = $rid"));
            if ($rr) {
                mysqli_query($conn, "UPDATE rooms SET status='Available' WHERE id = {$rr['room_id']}");
            }
        }
    }

    // Handle new room additions
    if ($is_group && isset($_POST['new_room_ids'])) {
        foreach ($_POST['new_room_ids'] as $rid) {
            $room_id = (int)$rid;
            if ($room_id <= 0) continue;
            $overlap = mysqli_query($conn, "SELECT id FROM reservations 
                                            WHERE room_id = $room_id 
                                            AND villa_id = $villa_id
                                            AND status NOT IN ('Cancelled','Checked-Out') 
                                            AND (check_in < '$check_out' AND check_out > '$check_in')");
            if (mysqli_num_rows($overlap) == 0) {
                $adults = (int)$_POST['new_adults_' . $room_id] ?? 1;
                $children = (int)$_POST['new_children_' . $room_id] ?? 0;
                $insert_id = null;
                $iq = "INSERT INTO reservations (group_id, customer_id, room_id, booking_type, check_in, check_out, adults, children, status, villa_id) 
                       VALUES ($gid, {$res['customer_id']}, $room_id, '$booking_type', '$check_in', '$check_out', $adults, $children, 'Pending', $villa_id)";
                if (mysqli_query($conn, $iq)) {
                    $insert_id = mysqli_insert_id($conn);
                    $all_ids[] = $insert_id;
                }
            }
        }
        // Re-fetch all group IDs (including new additions)
        $all_ids = [];
        $grp = mysqli_query($conn, "SELECT id FROM reservations WHERE (id = $gid OR group_id = $gid) AND villa_id = $villa_id");
        while ($gr = mysqli_fetch_assoc($grp)) {
            $all_ids[] = (int)$gr['id'];
        }
    }

    // For single-room: check the room_id was provided
    if (!$is_group) {
        $room_id = (int)$_POST['room_id'];
        if (empty($room_id)) {
            $_SESSION['error'] = 'Please select an available room!';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    // Overlap check for current room(s) — only for single room change OR new group rooms
    if (!$is_group) {
        $exclude_str = implode(',', array_unique($exclude_ids_pool));
        $overlap = mysqli_query($conn, "SELECT id FROM reservations 
                                        WHERE room_id=$room_id 
                                        AND id NOT IN ($exclude_str)
                                        AND villa_id = $villa_id
                                        AND status NOT IN ('Cancelled','Checked-Out') 
                                        AND (check_in < '$check_out' AND check_out > '$check_in')");
        if (mysqli_num_rows($overlap) > 0) {
            $_SESSION['error'] = 'Room is not available for the selected time slot!';
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        }
    }

    // Update customer record
    $cu = mysqli_query($conn, "UPDATE customers SET full_name='$full_name', nic_passport='$nic_passport', phone='$phone', email='$email' WHERE id={$res['customer_id']}");
    if (!$cu) {
        $_SESSION['error'] = 'Failed to update customer: ' . mysqli_error($conn);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    // Update all group/common fields for every room in the group
    if (!empty($all_ids)) {
        $all_str = implode(',', $all_ids);
        if (!$is_group) {
            $uq = "UPDATE reservations SET customer_id={$res['customer_id']}, room_id=$room_id, booking_type='$booking_type', check_in='$check_in', 
                   check_out='$check_out', discount=$discount, status='$status' WHERE id IN ($all_str) AND villa_id = $villa_id";
        } else {
            $uq = "UPDATE reservations SET customer_id={$res['customer_id']}, booking_type='$booking_type', check_in='$check_in', 
                   check_out='$check_out', discount=$discount, status='$status' WHERE id IN ($all_str) AND villa_id = $villa_id";
        }
        mysqli_query($conn, $uq);

        // Per-room adults/children updates
        if ($is_group && isset($_POST['room_adults'])) {
            foreach ($_POST['room_adults'] as $rid => $adults_val) {
                $rid = (int)$rid;
                $adults_val = (int)$adults_val;
                $children_val = (int)($_POST['room_children'][$rid] ?? 0);
                mysqli_query($conn, "UPDATE reservations SET adults=$adults_val, children=$children_val WHERE id=$rid AND villa_id = $villa_id");
            }
        } elseif (!$is_group) {
            $adults = (int)$_POST['adults'];
            $children = (int)$_POST['children'];
            mysqli_query($conn, "UPDATE reservations SET adults=$adults, children=$children WHERE id=$id AND villa_id = $villa_id");
        }
    }

    logAudit('UPDATE', 'reservations', $id, "Reservation #$id updated", ['old' => 'edit'], ['new' => 'edit']);
    $_SESSION['success'] = 'Reservation updated successfully!';
    header("Location: index.php");
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

// Collect group rooms for display
$room_list = [];
$current_room_ids = '0';
if ($is_group) {
    mysqli_data_seek($group_reservations, 0);
    while ($gr = mysqli_fetch_assoc($group_reservations)) {
        $room_list[] = $gr;
    }
    $current_room_ids = implode(',', array_map(function($rl) { return (int)$rl['room_id']; }, $room_list));
    if ($current_room_ids === '') $current_room_ids = '0';
}
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2><?= $is_group ? 'Edit Group Reservation #' . $gid : 'Edit Reservation #' . $id ?></h2>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST">
                <?= csrf_field() ?>
                <!-- Customer Information -->
                <h6 class="text-muted mb-3"><i class="fas fa-user me-1"></i> Customer Information</h6>
                <div class="row mb-4">
                    <div class="col-md-6 mb-2">
                        <label class="form-label small">Full Name *</label>
                        <input type="text" name="full_name" class="form-control form-control-sm" value="<?= htmlspecialchars($customer['full_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label small">NIC / Passport *</label>
                        <input type="text" name="nic_passport" class="form-control form-control-sm" value="<?= htmlspecialchars($customer['nic_passport'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label small">Phone *</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" maxlength="10" oninput="this.value = this.value.replace(/\D/g, '')" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label small">Email</label>
                        <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($customer['email'] ?? '') ?>">
                    </div>
                </div>

                <!-- Booking Details -->
                <h6 class="text-muted mb-3"><i class="fas fa-calendar-alt me-1"></i> Booking Details</h6>
                <div class="row mb-3">
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Check-In Date & Time</label>
                        <input type="datetime-local" name="check_in" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i', strtotime($res['check_in'])) ?>" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Check-Out Date & Time</label>
                        <input type="datetime-local" name="check_out" class="form-control form-control-sm" value="<?= date('Y-m-d\TH:i', strtotime($res['check_out'])) ?>" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Booking Type</label>
                        <select name="booking_type" id="bookingType" class="form-select form-select-sm" required>
                            <option value="Night Time" <?= $res['booking_type'] == 'Night Time' ? 'selected' : '' ?>>Night Time (Overnight)</option>
                            <option value="Day Time" <?= $res['booking_type'] == 'Day Time' ? 'selected' : '' ?>>Day Time (Day Use)</option>
                            <option value="Short Time" <?= $res['booking_type'] == 'Short Time' ? 'selected' : '' ?>>Short Time</option>
                        </select>
                    </div>
                </div>

                <!-- Group Rooms Table -->
                <?php if ($is_group && !empty($room_list)): ?>
                <h6 class="text-muted mb-3"><i class="fas fa-door-open me-1"></i> Rooms in This Booking</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Room</th>
                                <th>Type</th>
                                <th style="width:100px;">Adults</th>
                                <th style="width:100px;">Children</th>
                                <th style="width:90px;">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($room_list as $i => $rl): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><span class="badge bg-secondary">Room <?= htmlspecialchars($rl['room_number']) ?></span></td>
                                <td><?= htmlspecialchars($rl['type_name'] ?? '') ?></td>
                                <td>
                                    <input type="number" name="room_adults[<?= $rl['id'] ?>]" class="form-control form-control-sm" value="<?= (int)$rl['adults'] ?>" min="1" required>
                                </td>
                                <td>
                                    <input type="number" name="room_children[<?= $rl['id'] ?>]" class="form-control form-control-sm" value="<?= (int)$rl['children'] ?>" min="0">
                                </td>
                                <td class="text-center">
                                    <?php if (count($room_list) > 1): ?>
                                    <label class="form-check-label">
                                        <input type="checkbox" name="remove_room_ids[]" value="<?= $rl['id'] ?>" class="form-check-input">
                                        <small class="text-danger">Remove</small>
                                    </label>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Add Rooms Section -->
                <div class="card bg-light border mb-3">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-plus-circle me-1"></i> Add More Rooms <small class="text-muted">(optional)</small></h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Room Type</label>
                                <select id="groupRoomTypeId" class="form-select form-select-sm">
                                    <option value="">Select Room Type...</option>
                                    <?php mysqli_data_seek($room_types, 0); while ($t = mysqli_fetch_assoc($room_types)): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['type_name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div id="groupNewRooms" class="border rounded p-3 bg-white" style="min-height: 50px;">
                            <p class="text-muted mb-0 small"><em>Select dates and a room type to see available rooms.</em></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Single Room Selection (only when NOT a group) -->
                <?php if (!$is_group): ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" id="roomTypeId" class="form-select" required disabled>
                            <option value="">Please select Check-In & Check-Out dates first...</option>
                            <?php mysqli_data_seek($room_types, 0); while ($t = mysqli_fetch_assoc($room_types)): ?>
                            <option value="<?= $t['id'] ?>" <?= $current_room_type_id == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Available Room</label>
                        <select name="room_id" class="form-select" required disabled>
                            <option value="">Please select Room Type first...</option>
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control" value="<?= htmlspecialchars($res['adults']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control" value="<?= htmlspecialchars($res['children']) ?>">
                    </div>
                </div>
                <?php endif; ?>

                <!-- Status -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Discount (<?= htmlspecialchars($global_currency) ?>)</label>
                        <input type="number" name="discount" class="form-control" value="<?= htmlspecialchars($res['discount'] ?? '0') ?>" min="0" step="0.01">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                        <option value="Pending" <?= $res['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= $res['status'] == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Checked-In" <?= $res['status'] == 'Checked-In' ? 'selected' : '' ?>>Checked-In</option>
                        <option value="Checked-Out" <?= $res['status'] == 'Checked-Out' ? 'selected' : '' ?>>Checked-Out</option>
                        <option value="Cancelled" <?= $res['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Update Reservation</button>
                    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    const checkInInput = $('input[name="check_in"]');
    const checkOutInput = $('input[name="check_out"]');

    function updateCheckOutMin() {
        const checkIn = checkInInput.val();
        if (checkIn) {
            checkOutInput.attr('min', checkIn);
        } else {
            checkOutInput.removeAttr('min');
        }
    }

    checkInInput.on('change', updateCheckOutMin);
    updateCheckOutMin();

    <?php if ($is_group): ?>
    // Group room addition: room type -> checkboxes
    const checkInInput = $('input[name="check_in"]');
    const checkOutInput = $('input[name="check_out"]');
    const bookingTypeSelect = $('#bookingType');
    const groupRoomType = $('#groupRoomTypeId');
    const groupNewRooms = $('#groupNewRooms');

    function fetchGroupAvailableRooms() {
        const checkIn = checkInInput.val();
        const checkOut = checkOutInput.val();
        const roomTypeId = groupRoomType.val();
        const bookingType = bookingTypeSelect.val();

        if (!checkIn || !checkOut || !roomTypeId || new Date(checkOut) <= new Date(checkIn)) {
            groupNewRooms.html('<p class="text-muted mb-0 small"><em>Select dates and a room type to see available rooms.</em></p>');
            return;
        }

        groupNewRooms.html('<p class="text-muted mb-0 small"><em>Loading...</em></p>');

        $.ajax({
            url: 'get_available_rooms.php',
            type: 'GET',
            data: {
                check_in: checkIn,
                check_out: checkOut,
                room_type_id: roomTypeId,
                booking_type: bookingType,
                exclude_ids: '<?= implode(',', array_map('intval', $all_ids ?? [$id])) ?>',
                exclude_room_ids: '<?= $current_room_ids ?>'
            },
            dataType: 'json',
            success: function(rooms) {
                if (rooms.error) {
                    groupNewRooms.html('<p class="text-muted mb-0 small"><em>' + rooms.error + '</em></p>');
                    return;
                }
                if (rooms.length === 0) {
                    groupNewRooms.html('<p class="text-muted mb-0 small"><em>No rooms available</em></p>');
                    return;
                }
                let html = '<div class="row g-2">';
                rooms.forEach(function(room) {
                    html += '<div class="col-md-4 col-sm-6">';
                    html += '<div class="border bg-white rounded p-2">';
                    html += '<label class="form-check">';
                    html += '<input class="form-check-input" type="checkbox" name="new_room_ids[]" value="' + room.id + '"> ';
                    html += '<strong>Room ' + room.room_number + '</strong>';
                    html += '<br><small class="text-muted">' + room.type_name + ' (<?= htmlspecialchars($global_currency) ?>' + parseFloat(room.price).toFixed(2) + ')</small>';
                    html += '<div class="row g-1 mt-1">';
                    html += '<div class="col-6"><input type="number" name="new_adults_' + room.id + '" class="form-control form-control-sm" placeholder="Adults" value="1" min="1"></div>';
                    html += '<div class="col-6"><input type="number" name="new_children_' + room.id + '" class="form-control form-control-sm" placeholder="Children" value="0" min="0"></div>';
                    html += '</div>';
                    html += '</label></div></div>';
                });
                html += '</div>';
                groupNewRooms.html(html);
            },
            error: function() {
                groupNewRooms.html('<p class="text-danger mb-0 small"><em>Failed to fetch rooms</em></p>');
            }
        });
    }

    groupRoomType.on('change', fetchGroupAvailableRooms);
    checkInInput.on('change', fetchGroupAvailableRooms);
    checkOutInput.on('change', fetchGroupAvailableRooms);
    bookingTypeSelect.on('change', fetchGroupAvailableRooms);

    <?php else: ?>
    // Single room: room type -> room dropdown (original behavior)
    const checkInInput = $('input[name="check_in"]');
    const checkOutInput = $('input[name="check_out"]');
    const bookingTypeSelect = $('#bookingType');
    const roomTypeSelect = $('#roomTypeId');
    const roomSelect = $('select[name="room_id"]');
    const originalSelectedRoom = "<?= $res['room_id'] ?>";
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
        if (!checkIn || !checkOut || new Date(checkOut) <= new Date(checkIn)) return;
        if (!roomTypeId) {
            roomSelect.prop('disabled', true).html('<option value="">Please select Room Type first...</option>');
            return;
        }
        roomSelect.prop('disabled', true).html('<option value="">Loading...</option>');
        $.ajax({
            url: 'get_available_rooms.php',
            type: 'GET',
            data: { check_in: checkIn, check_out: checkOut, room_type_id: roomTypeId, booking_type: bookingType, exclude_ids: excludeResId },
            dataType: 'json',
            success: function(rooms) {
                if (rooms.error) { roomSelect.html('<option value="">' + rooms.error + '</option>'); return; }
                if (rooms.length === 0) { roomSelect.html('<option value="">No rooms available</option>'); return; }
                let options = '<option value="">Select Room</option>';
                rooms.forEach(function(room) {
                    const sel = room.id == originalSelectedRoom ? 'selected' : '';
                    options += '<option value="' + room.id + '" ' + sel + '>Room ' + room.room_number + ' - ' + room.type_name + ' (<?= htmlspecialchars($global_currency) ?>' + parseFloat(room.price).toFixed(2) + ')</option>';
                });
                roomSelect.html(options).prop('disabled', false);
            },
            error: function() { roomSelect.html('<option value="">Failed to fetch rooms</option>'); }
        });
    }

    checkInInput.on('change', updateRoomTypeSelector);
    checkOutInput.on('change', updateRoomTypeSelector);
    bookingTypeSelect.on('change', fetchAvailableRooms);
    roomTypeSelect.on('change', fetchAvailableRooms);

    if (checkInInput.val() && checkOutInput.val()) {
        roomTypeSelect.prop('disabled', false);
        roomTypeSelect.find('option:first').text('Select Room Type');
        if (roomTypeSelect.val()) fetchAvailableRooms();
    }
    <?php endif; ?>
});
</script>
<?php include '../../includes/footer.php'; ?>
