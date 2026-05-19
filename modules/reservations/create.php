<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

$customers = mysqli_query($conn, "SELECT * FROM customers WHERE is_active = 1 ORDER BY full_name");
$room_types = mysqli_query($conn, "SELECT * FROM room_types ORDER BY type_name");

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
        $booking_type = mysqli_real_escape_string($conn, $_POST['booking_type'] ?? 'Night Time');
        $check_in = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_in']));
        $check_out = str_replace('T', ' ', mysqli_real_escape_string($conn, $_POST['check_out']));
        $adults = (int)$_POST['adults'];
        $children = (int)$_POST['children'];

        if (empty($room_id)) {
            $error = 'Please select an available room!';
        } elseif (strtotime($check_out) <= strtotime($check_in)) {
            $error = 'Check-out must be after check-in!';
        } else {
            // Precise datetime overlap check including Pending/Confirmed/Checked-In reservations
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
                $query = "INSERT INTO reservations (customer_id, room_id, booking_type, check_in, check_out, adults, children, status) 
                          VALUES ($customer_id, $room_id, '$booking_type', '$check_in', '$check_out', $adults, $children, 'Pending')";
                if (mysqli_query($conn, $query)) {
                    $reservation_id = mysqli_insert_id($conn);
                    
                    // Calculate room charges based on booking type & duration
                    $room_res = mysqli_query($conn, "SELECT * FROM rooms WHERE id = $room_id");
                    $room = mysqli_fetch_assoc($room_res);
                    
                    $check_in_ts = strtotime($check_in);
                    $check_out_ts = strtotime($check_out);
                    $hours_diff = ($check_out_ts - $check_in_ts) / 3600;
                    
                    if ($booking_type == 'Day Time') {
                        $room_charges = $room['price_day'];
                    } elseif ($booking_type == 'Short Time') {
                        $room_charges = $room['price_short'];
                    } else {
                        // Night Time - calculate by nights
                        $nights = max(1, ceil($hours_diff / 24));
                        $room_charges = $room['price_night'] * $nights;
                    }
                    
                    $grand_total = $room_charges; // No tax applied
                    
                    // Create invoice
                    mysqli_query($conn, "INSERT INTO invoices (reservation_id, room_charges, product_charges, discount, grand_total, payment_status)
                                         VALUES ($reservation_id, $room_charges, 0, 0, $grand_total, 'Unpaid')");
                    $invoice_id = mysqli_insert_id($conn);
                    
                    // Record payment if amount was provided
                    $payment_amount = (float)($_POST['payment_amount'] ?? 0);
                    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'Cash');
                    
                    if ($payment_amount > 0) {
                        mysqli_query($conn, "INSERT INTO payments (invoice_id, amount, payment_method)
                                             VALUES ($invoice_id, $payment_amount, '$payment_method')");
                        
                        if ($payment_amount >= $grand_total) {
                            mysqli_query($conn, "UPDATE invoices SET payment_status='Paid' WHERE id=$invoice_id");
                        } else {
                            mysqli_query($conn, "UPDATE invoices SET payment_status='Partial' WHERE id=$invoice_id");
                        }
                    }
                    
                    $_SESSION['success'] = 'Reservation created successfully! Invoice #' . $invoice_id . ' generated.';
                    header("Location: index.php");
                    exit();
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
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-calendar-plus"></i> New Reservation</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Create a booking with optional advance payment</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Back to Reservations</a>
        </div>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header"><h5><i class="fas fa-calendar-check"></i> Reservation Details</h5></div>
        <div class="card-body">
            <form method="POST">
                <!-- Customer Selection Section -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3"><i class="fas fa-user me-1"></i> Customer Information</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="isNewCustomer" name="is_new_customer" <?= isset($_POST['is_new_customer']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-bold" for="isNewCustomer">New Customer?</label>
                    </div>

                    <div id="existingCustomerDiv" <?= isset($_POST['is_new_customer']) ? 'style="display:none;"' : '' ?>>
                        <label class="form-label text-muted">Select Existing Customer</label>
                        <select name="customer_id" id="customerId" class="form-select" <?= isset($_POST['is_new_customer']) ? '' : 'required' ?>>
                            <option value="">Choose...</option>
                            <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                            <option value="<?= $c['id'] ?>" <?= (isset($_POST['customer_id']) && $_POST['customer_id'] == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['full_name']) ?> (<?= htmlspecialchars($c['nic_passport']) ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div id="newCustomerDiv" <?= isset($_POST['is_new_customer']) ? 'style="display:block;"' : 'style="display:none;"' ?> class="p-3 bg-light rounded border">
                        <h6 class="mb-3">Enter Customer Details</h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Full Name *</label>
                                <input type="text" name="full_name" id="fullName" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" <?= isset($_POST['is_new_customer']) ? 'required' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">NIC / Passport *</label>
                                <input type="text" name="nic_passport" id="nicPassport" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['nic_passport'] ?? '') ?>" <?= isset($_POST['is_new_customer']) ? 'required' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Phone *</label>
                                <input type="text" name="phone" id="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" <?= isset($_POST['is_new_customer']) ? 'required' : '' ?>>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
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
                        <label class="form-label">Room Type</label>
                        <select name="room_type_id" id="roomTypeId" class="form-select" required disabled>
                            <option value="">Please select Check-In & Check-Out dates first...</option>
                            <?php mysqli_data_seek($room_types, 0); while ($t = mysqli_fetch_assoc($room_types)): ?>
                            <option value="<?= $t['id'] ?>" <?= (isset($_POST['room_type_id']) && $_POST['room_type_id'] == $t['id']) ? 'selected' : '' ?>><?= htmlspecialchars($t['type_name']) ?></option>
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

                <!-- Payment Section -->
                <div class="card bg-light border mt-4 mb-3">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-credit-card me-1"></i> Advance Payment <small class="text-muted">(Optional)</small></h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Payment Amount (<?= htmlspecialchars($global_currency) ?>)</label>
                                <input type="number" step="0.01" name="payment_amount" class="form-control" placeholder="0.00" min="0" value="<?= htmlspecialchars($_POST['payment_amount'] ?? '') ?>">
                                <small class="text-muted">Leave 0 for no advance payment. Full amount can be paid later.</small>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label class="form-label small">Payment Method</label>
                                <select name="payment_method" class="form-select">
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>
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

<script>
document.getElementById('isNewCustomer').addEventListener('change', function() {
    const isNew = this.checked;
    document.getElementById('existingCustomerDiv').style.display = isNew ? 'none' : 'block';
    document.getElementById('newCustomerDiv').style.display = isNew ? 'block' : 'none';
    
    document.getElementById('customerId').required = !isNew;
    document.getElementById('fullName').required = isNew;
    document.getElementById('nicPassport').required = isNew;
    document.getElementById('phone').required = isNew;
});
</script>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    const checkInInput = $('input[name="check_in"]');
    const checkOutInput = $('input[name="check_out"]');
    const bookingTypeSelect = $('#bookingType');
    const roomTypeSelect = $('#roomTypeId');
    const roomSelect = $('select[name="room_id"]');
    const originalSelectedRoom = "<?= isset($_POST['room_id']) ? (int)$_POST['room_id'] : '' ?>";

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
                booking_type: bookingType
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