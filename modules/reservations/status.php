<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

if (!has_role(['Admin', 'Receptionist'])) {
    $_SESSION['error'] = 'Unauthorized access.';
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method.';
    header('Location: index.php');
    exit();
}

verify_csrf_token();

$new_status = mysqli_real_escape_string($conn, $_POST['status']);
$villa_id = active_villa_id();

// Determine if single reservation or group
if (isset($_POST['gid'])) {
    $gid = (int)$_POST['gid'];
    // Get all reservations in this group
    $ids = [];
    $group_query = mysqli_query($conn, "SELECT id FROM reservations WHERE (id = $gid OR group_id = $gid) AND villa_id = $villa_id");
    while ($row = mysqli_fetch_assoc($group_query)) {
        $ids[] = (int)$row['id'];
    }
    if (empty($ids)) {
        $_SESSION['error'] = 'No reservations found for this group.';
        header('Location: index.php');
        exit();
    }
} else {
    $id = (int)$_POST['id'];
    $ids = [$id];
}

$is_group = count($ids) > 1;

// --- Step 1: Update all reservation statuses and room statuses ---
foreach ($ids as $id) {
    $res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id=$id AND villa_id = $villa_id"));
    if (!$res) continue;

    $old_status = $res['status'];
    mysqli_query($conn, "UPDATE reservations SET status='$new_status' WHERE id=$id AND villa_id = $villa_id");
    
    logAudit('UPDATE', 'reservations', $id, "Reservation status changed from $old_status to $new_status", ['status' => $old_status], ['status' => $new_status], $villa_id);

    if ($new_status == 'Checked-In') {
        mysqli_query($conn, "UPDATE rooms SET status='Occupied' WHERE id=" . $res['room_id']);
    } elseif (in_array($new_status, ['Checked-Out', 'Cancelled'])) {
        mysqli_query($conn, "UPDATE rooms SET status='Available' WHERE id=" . $res['room_id']);
    }
}

// --- Step 2: Handle invoices for Checked-Out ---
$any_checked_out = false;

if ($new_status == 'Checked-Out') {
    $any_checked_out = true;

    // Collect billing data per room
    $room_billing = [];
    foreach ($ids as $id) {
        $res_billing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT r.*, rm.price_day, rm.price_night, rm.price_short, rm.room_number FROM reservations r JOIN rooms rm ON r.room_id = rm.id WHERE r.id=$id"));
        if (!$res_billing) continue;

        $price = $res_billing['price_night'];
        if ($res_billing['booking_type'] === 'Day Time') {
            $price = $res_billing['price_day'];
        } elseif ($res_billing['booking_type'] === 'Short Time') {
            $price = $res_billing['price_short'];
        }

        $days = max(1, ceil((strtotime($res_billing['check_out']) - strtotime($res_billing['check_in'])) / 86400));
        $room_charges = $days * $price;
        $product_charges = 0;

        $service_orders = mysqli_query($conn, "SELECT so.*, p.product_name FROM service_orders so JOIN products p ON so.product_id = p.id WHERE so.reservation_id = $id AND so.status != 'Cancelled'");
        $so_list = [];
        while ($so = mysqli_fetch_assoc($service_orders)) {
            $product_charges += $so['quantity'] * $so['price'];
            $so_list[] = $so;
        }

        $room_billing[] = [
            'id' => $id,
            'room_id' => $res_billing['room_id'],
            'room_number' => $res_billing['room_number'],
            'check_in' => $res_billing['check_in'],
            'check_out' => $res_billing['check_out'],
            'days' => $days,
            'price' => $price,
            'room_charges' => $room_charges,
            'product_charges' => $product_charges,
            'discount' => (float)($res_billing['discount'] ?? 0),
            'so_list' => $so_list
        ];
    }

    if ($is_group) {
        // --- Combined invoice for the whole group ---
        $group_room_total = array_sum(array_column($room_billing, 'room_charges'));
        $group_product_total = array_sum(array_column($room_billing, 'product_charges'));
        $group_discount = array_sum(array_column($room_billing, 'discount'));
        $grand_total = $group_room_total + $group_product_total - $group_discount;
        if ($grand_total < 0) $grand_total = 0;

        // Find or create group invoice
        $existing = mysqli_query($conn, "SELECT id FROM invoices WHERE group_id = $gid");
        $inv_row = $existing ? mysqli_fetch_assoc($existing) : null;
        if ($inv_row) {
            $invoice_id = $inv_row['id'];
            mysqli_query($conn, "DELETE FROM invoice_items WHERE invoice_id = $invoice_id");
        } else {
            $first_res_id = $room_billing[0]['id'];
            mysqli_query($conn, "INSERT INTO invoices (reservation_id, group_id, room_charges, product_charges, discount, grand_total, payment_status, villa_id)
                                 VALUES ($first_res_id, $gid, $group_room_total, $group_product_total, $group_discount, $grand_total, 'Unpaid', $villa_id)");
            $invoice_id = mysqli_insert_id($conn);
        }

        // Insert items for all rooms
        foreach ($room_billing as $rb) {
            $room_label = $rb['room_number'] ?? ('Room ID ' . $rb['room_id']);
            mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total, villa_id)
                                 VALUES ($invoice_id, 'Room', '{$room_label} Charges ({$rb['check_in']} to {$rb['check_out']})', {$rb['days']}, {$rb['price']}, {$rb['room_charges']}, $villa_id)");

            foreach ($rb['so_list'] as $so) {
                $total = $so['quantity'] * $so['price'];
                mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total, villa_id)
                                     VALUES ($invoice_id, 'Product', 'RS: {$so['product_name']}', {$so['quantity']}, {$so['price']}, $total, $villa_id)");
            }
        }

        // Update invoice totals
        mysqli_query($conn, "UPDATE invoices SET room_charges = $group_room_total, product_charges = $group_product_total, discount = $group_discount, grand_total = $grand_total WHERE id = $invoice_id");
    } else {
        // --- Per-room invoice (single reservation) ---
        foreach ($room_billing as $rb) {
            $discount = $rb['discount'];
            $grand_total = $rb['room_charges'] + $rb['product_charges'] - $discount;
            if ($grand_total < 0) $grand_total = 0;

            $existing = mysqli_query($conn, "SELECT id FROM invoices WHERE reservation_id = {$rb['id']}");
            $inv_row = $existing ? mysqli_fetch_assoc($existing) : null;
            if ($inv_row) {
                $invoice_id = $inv_row['id'];
                mysqli_query($conn, "DELETE FROM invoice_items WHERE invoice_id = $invoice_id");
            } else {
                mysqli_query($conn, "INSERT INTO invoices (reservation_id, group_id, room_charges, product_charges, discount, grand_total, payment_status, villa_id)
                                     VALUES ({$rb['id']}, NULL, {$rb['room_charges']}, {$rb['product_charges']}, $discount, $grand_total, 'Unpaid', $villa_id)");
                $invoice_id = mysqli_insert_id($conn);
            }

            $room_label = $rb['room_number'] ?? ('Room ID ' . $rb['room_id']);
            mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total, villa_id)
                                 VALUES ($invoice_id, 'Room', '{$room_label} Charges ({$rb['check_in']} to {$rb['check_out']})', {$rb['days']}, {$rb['price']}, {$rb['room_charges']}, $villa_id)");

            foreach ($rb['so_list'] as $so) {
                $total = $so['quantity'] * $so['price'];
                mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total, villa_id)
                                     VALUES ($invoice_id, 'Product', 'RS: {$so['product_name']}', {$so['quantity']}, {$so['price']}, $total, $villa_id)");
            }

            mysqli_query($conn, "UPDATE invoices SET room_charges = {$rb['room_charges']}, product_charges = {$rb['product_charges']}, discount = $discount, grand_total = $grand_total WHERE id = $invoice_id");
        }
    }
}

// --- Step 3: Success message ---
if ($new_status == 'Checked-Out') {
    if ($any_checked_out) {
        $_SESSION['success'] = "Guest checked out successfully! Invoice auto-generated.";
    } else {
        $_SESSION['success'] = "Guest checked out successfully!";
    }
} elseif ($new_status == 'Cancelled') {
    $_SESSION['success'] = "Reservation cancelled successfully.";
} elseif ($new_status == 'Checked-In') {
    $_SESSION['success'] = "Guest checked in successfully!";
} else {
    $_SESSION['success'] = "Reservation status updated to {$new_status} successfully!";
}

// Redirect back to referring page if it's dashboard, otherwise reservations list
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'dashboard.php') !== false) {
    header('Location: ../../dashboard.php');
} else {
    header('Location: index.php');
}
exit();
