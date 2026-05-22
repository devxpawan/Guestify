<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$villa_id = active_villa_id();
$room_types = mysqli_query($conn, "SELECT * FROM room_types ORDER BY type_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $nic_passport = mysqli_real_escape_string($conn, $_POST['nic_passport']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $room_ids = isset($_POST['room_ids']) ? $_POST['room_ids'] : [];
    $booking_type = mysqli_real_escape_string($conn, $_POST['booking_type'] ?? 'Night Time');
    $check_in = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_in']));
    $check_out = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_out']));
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];

    $validation_error = null;

    if (!empty($email) && !str_contains($email, '@')) {
        $validation_error = 'Email must contain @.';
    } elseif (!empty($phone) && (!ctype_digit($phone) || strlen($phone) !== 10)) {
        $validation_error = 'Phone must be exactly 10 digits.';
    } elseif (empty($full_name) || empty($nic_passport) || empty($phone)) {
        $validation_error = 'Please fill all customer details!';
    } elseif (empty($room_ids)) {
        $validation_error = 'Please select at least one room!';
    } elseif (strtotime($check_out) <= strtotime($check_in)) {
        $validation_error = 'Check-out must be after check-in!';
    }

    if ($validation_error) {
        $_SESSION['error'] = $validation_error;
    } else {
        $c_query = "INSERT INTO customers (full_name, nic_passport, phone, email, villa_id) VALUES ('$full_name', '$nic_passport', '$phone', '$email', $villa_id)";
        if (mysqli_query($conn, $c_query)) {
            $customer_id = mysqli_insert_id($conn);
        } else {
            $_SESSION['error'] = "Failed to create customer: " . mysqli_error($conn);
            $validation_error = true;
        }
    }

    if (!$validation_error) {

        $first_id = null;
        $created_ids = [];
        $all_ok = true;

        foreach ($room_ids as $rid) {
            $room_id = (int)$rid;
            if ($room_id <= 0) continue;

            // Overlap check
            $overlap_query = "SELECT id FROM reservations 
                              WHERE room_id = $room_id 
                              AND villa_id = $villa_id
                              AND status NOT IN ('Cancelled','Checked-Out') 
                              AND (check_in < '$check_out' AND check_out > '$check_in')";
            $overlap = mysqli_query($conn, $overlap_query);
            
            if (mysqli_num_rows($overlap) > 0) {
                $_SESSION['error'] = "Room ID $room_id is not available for the selected time slot!";
                $all_ok = false;
                break;
            }

            $query = "INSERT INTO reservations (group_id, customer_id, room_id, booking_type, check_in, check_out, adults, children, status, villa_id) 
                      VALUES (NULL, $customer_id, $room_id, '$booking_type', '$check_in', '$check_out', $adults, $children, 'Pending', $villa_id)";
            if (mysqli_query($conn, $query)) {
                $reservation_id = mysqli_insert_id($conn);
                if ($first_id === null) {
                    $first_id = $reservation_id;
                }
                $created_ids[] = $reservation_id;
            } else {
                $_SESSION['error'] = 'Failed: ' . mysqli_error($conn);
                $all_ok = false;
                break;
            }
        }

        if ($all_ok && $first_id !== null && count($created_ids) > 1) {
            // Update all created reservations with the group_id
            $ids_str = implode(',', $created_ids);
            mysqli_query($conn, "UPDATE reservations SET group_id = $first_id WHERE id IN ($ids_str)");
        }

        if ($all_ok && $first_id !== null) {
            $room_names = [];
            foreach ($created_ids as $res_id) {
                $rn = mysqli_fetch_assoc(mysqli_query($conn, "SELECT rm.room_number FROM reservations r JOIN rooms rm ON r.room_id = rm.id WHERE r.id = $res_id"));
                if ($rn) $room_names[] = $rn['room_number'];
            }
            
            if (count($created_ids) > 1) {
                logAudit('CREATE', 'reservations', $first_id, "Group booking created for customer ID $customer_id, rooms: " . implode(', ', $room_names) . " (group_id: $first_id)");
                $_SESSION['success'] = 'Group reservation created successfully! Rooms: ' . implode(', ', $room_names) . '.';
            } else {
                logAudit('CREATE', 'reservations', $first_id, "Reservation created for customer ID $customer_id, room ID $room_id");
                $_SESSION['success'] = 'Reservation created successfully!';
            }
            
            header("Location: index.php");
            exit();
        } elseif (!$all_ok) {
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-calendar-plus"></i> New Reservation</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Create a booking for one or more rooms</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Reservations</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header"><h5><i class="fas fa-calendar-check"></i> Reservation Details</h5></div>
        <div class="card-body">
            <form method="POST">
                <!-- Customer Information -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3"><i class="fas fa-user me-1"></i> Customer Information</h6>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Full Name *</label>
                            <input type="text" name="full_name" id="fullName" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">NIC / Passport *</label>
                            <input type="text" name="nic_passport" id="nicPassport" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['nic_passport'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Phone *</label>
                            <input type="text" name="phone" id="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" maxlength="10" oninput="this.value = this.value.replace(/\D/g, '')" required>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Booking Details -->
                <h6 class="text-muted mb-3"><i class="fas fa-calendar-alt me-1"></i> Booking Details</h6>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-In Date & Time</label>
                        <input type="datetime-local" name="check_in" class="form-control" value="<?= htmlspecialchars($_POST['check_in'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Check-Out Date & Time</label>
                        <input type="datetime-local" name="check_out" class="form-control" value="<?= htmlspecialchars($_POST['check_out'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Booking Type</label>
                        <select name="booking_type" id="bookingType" class="form-select" required>
                            <option value="Night Time" <?= (isset($_POST['booking_type']) && $_POST['booking_type'] == 'Night Time') ? 'selected' : '' ?>>Night Time (Overnight)</option>
                            <option value="Day Time" <?= (isset($_POST['booking_type']) && $_POST['booking_type'] == 'Day Time') ? 'selected' : '' ?>>Day Time (Day Use)</option>
                            <option value="Short Time" <?= (isset($_POST['booking_type']) && $_POST['booking_type'] == 'Short Time') ? 'selected' : '' ?>>Short Time</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Adults</label>
                        <input type="number" name="adults" class="form-control" value="<?= htmlspecialchars($_POST['adults'] ?? '1') ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Children</label>
                        <input type="number" name="children" class="form-control" value="<?= htmlspecialchars($_POST['children'] ?? '0') ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Room Type <small class="text-muted">(optional filter)</small></label>
                        <select name="room_type_id" id="roomTypeId" class="form-select" disabled>
                            <option value="">All Types</option>
                            <?php mysqli_data_seek($room_types, 0); while ($t = mysqli_fetch_assoc($room_types)): ?>
                            <option value="<?= $t['id'] ?>" <?= (isset($_POST['room_type_id']) && $_POST['room_type_id'] == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <!-- Multi-Room Selection -->
                <div class="mb-3">
                    <label class="form-label">Available Rooms <small class="text-muted">(select one or more)</small></label>
                    <div id="roomCheckboxes" class="border rounded p-3 bg-light" style="min-height: 60px;">
                        <p class="text-muted mb-0"><em>Select Check-In & Check-Out dates and Room Type first...</em></p>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Create Reservation</button>
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
    const bookingTypeSelect = $('#bookingType');
    const roomTypeSelect = $('#roomTypeId');
    const roomCheckboxes = $('#roomCheckboxes');

    function updateRoomTypeSelector() {
        const checkIn = checkInInput.val();
        const checkOut = checkOutInput.val();

        if (!checkIn || !checkOut || new Date(checkOut) <= new Date(checkIn)) {
            roomTypeSelect.prop('disabled', true);
            roomCheckboxes.html('<p class="text-muted mb-0"><em>Select Check-In & Check-Out dates first...</em></p>');
            return;
        }

        roomTypeSelect.prop('disabled', false);
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

        roomCheckboxes.html('<p class="text-muted mb-0"><em>Loading available rooms...</em></p>');

        $.ajax({
            url: 'get_available_rooms.php',
            type: 'GET',
            data: {
                check_in: checkIn,
                check_out: checkOut,
                room_type_id: roomTypeId || 0,
                booking_type: bookingType
            },
            dataType: 'json',
            success: function(rooms) {
                if (rooms.error) {
                    roomCheckboxes.html('<p class="text-muted mb-0"><em>' + rooms.error + '</em></p>');
                    return;
                }

                if (rooms.length === 0) {
                    roomCheckboxes.html('<p class="text-muted mb-0"><em>No rooms available</em></p>');
                    return;
                }

                // Group by type_name
                const grouped = {};
                rooms.forEach(function(room) {
                    if (!grouped[room.type_name]) grouped[room.type_name] = [];
                    grouped[room.type_name].push(room);
                });

                let html = '';
                const typeKeys = Object.keys(grouped);
                typeKeys.forEach(function(typeName, idx) {
                    html += '<div class="mb-3' + (idx > 0 ? ' mt-3' : '') + '">';
                    html += '<strong class="d-block mb-2 text-muted" style="font-size:0.85rem;">' + typeName + '</strong>';
                    html += '<div class="row g-2">';
                    grouped[typeName].forEach(function(room) {
                        html += '<div class="col-md-4 col-sm-6">';
                        html += '<div class="form-check form-check-inline border bg-white rounded p-2 w-100">';
                        html += '<input class="form-check-input" type="checkbox" name="room_ids[]" value="' + room.id + '" id="room_' + room.id + '">';
                        html += '<label class="form-check-label d-block" for="room_' + room.id + '">';
                        html += '<strong>Room ' + room.room_number + '</strong><br>';
                        html += '<small class="text-muted">' + room.type_name + ' (<?= htmlspecialchars($global_currency) ?>' + parseFloat(room.price).toFixed(2) + ')</small>';
                        html += '</label></div></div>';
                    });
                    html += '</div></div>';
                });
                roomCheckboxes.html(html);
            },
            error: function() {
                roomCheckboxes.html('<p class="text-danger mb-0"><em>Failed to fetch available rooms</em></p>');
            }
        });
    }

    checkInInput.on('change', updateRoomTypeSelector);
    checkOutInput.on('change', updateRoomTypeSelector);
    bookingTypeSelect.on('change', fetchAvailableRooms);
    roomTypeSelect.on('change', fetchAvailableRooms);

    if (checkInInput.val() && checkOutInput.val()) {
        roomTypeSelect.prop('disabled', false);
        fetchAvailableRooms();
    }
});
</script>
<?php include '../../includes/footer.php'; ?>
