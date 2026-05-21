<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

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

$id = (int)$_POST['id'];
$new_status = mysqli_real_escape_string($conn, $_POST['status']);

$res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id=$id"));
if ($res) {
    $old_status = $res['status'];
    // Update reservation status
    mysqli_query($conn, "UPDATE reservations SET status='$new_status' WHERE id=$id");
    

    // Update room status accordingly
    if ($new_status == 'Checked-In') {
        $room_id = $res['room_id'];
        $old_room_status_res = mysqli_query($conn, "SELECT status FROM rooms WHERE id=" . $room_id);
        $old_room_status = mysqli_fetch_assoc($old_room_status_res)['status'];

        mysqli_query($conn, "UPDATE rooms SET status='Occupied' WHERE id=" . $room_id);
        $_SESSION['success'] = "Guest checked in successfully!";
    } elseif (in_array($new_status, ['Checked-Out', 'Cancelled'])) {
        $room_id = $res['room_id'];
        $old_room_status_res = mysqli_query($conn, "SELECT status FROM rooms WHERE id=" . $room_id);
        $old_room_status = mysqli_fetch_assoc($old_room_status_res)['status'];

        mysqli_query($conn, "UPDATE rooms SET status='Available' WHERE id=" . $room_id);
        
        if ($new_status == 'Checked-Out') {
            // Check if invoice already exists for this reservation
            $check_invoice = mysqli_query($conn, "SELECT id FROM invoices WHERE reservation_id = $id");
            if (mysqli_num_rows($check_invoice) == 0) {
                // Fetch room rates
                $res_billing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT r.*, rm.price_day, rm.price_night, rm.price_short FROM reservations r JOIN rooms rm ON r.room_id = rm.id WHERE r.id=$id"));
                if ($res_billing) {
                    $price = $res_billing['price_night'];
                    if ($res_billing['booking_type'] === 'Day Time') {
                        $price = $res_billing['price_day'];
                    } elseif ($res_billing['booking_type'] === 'Short Time') {
                        $price = $res_billing['price_short'];
                    }

                    $days = max(1, ceil((strtotime($res_billing['check_out']) - strtotime($res_billing['check_in'])) / 86400));
                    $room_charges = $days * $price;
                    $product_charges = 0;

                    // Add Room Service orders
                    $service_orders = mysqli_query($conn, "SELECT * FROM service_orders WHERE reservation_id = $id AND status != 'Cancelled'");
                    while ($so = mysqli_fetch_assoc($service_orders)) {
                        $product_charges += $so['quantity'] * $so['price'];
                    }

                    $grand_total = $room_charges + $product_charges;

                    $query = "INSERT INTO invoices (reservation_id, room_charges, product_charges, discount, grand_total, payment_status) 
                              VALUES ($id, $room_charges, $product_charges, 0, $grand_total, 'Unpaid')";
                    
                    if (mysqli_query($conn, $query)) {
                        $invoice_id = mysqli_insert_id($conn);
                        
                        // Insert room charge item
                        mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total) 
                                             VALUES ($invoice_id, 'Room', 'Room Charges ({$res_billing['check_in']} to {$res_billing['check_out']})', $days, $price, $room_charges)");
                        $invoice_item_id = mysqli_insert_id($conn);

                        // Transfer Service Orders to Invoice Items
                        $service_orders = mysqli_query($conn, "SELECT so.*, p.product_name FROM service_orders so JOIN products p ON so.product_id = p.id WHERE so.reservation_id = $id AND so.status != 'Cancelled'");
                        while ($so = mysqli_fetch_assoc($service_orders)) {
                            $total = $so['quantity'] * $so['price'];
                            mysqli_query($conn, "INSERT INTO invoice_items (invoice_id, item_type, item_name, quantity, price, total) 
                                                 VALUES ($invoice_id, 'Product', 'RS: {$so['product_name']}', {$so['quantity']}, {$so['price']}, $total)");
                            $invoice_item_id = mysqli_insert_id($conn);
                        }
                        $_SESSION['success'] = "Guest checked out successfully! Invoice #{$invoice_id} has been auto-generated.";
                    } else {
                        $_SESSION['success'] = "Guest checked out successfully, but invoice auto-generation failed.";
                    }
                }
            } else {
                $_SESSION['success'] = "Guest checked out successfully!";
            }
        } elseif ($new_status == 'Cancelled') {
            $_SESSION['success'] = "Reservation cancelled successfully.";
        }
    } else {
        $_SESSION['success'] = "Reservation status updated to {$new_status} successfully!";
    }
} else {
    $_SESSION['error'] = 'Reservation not found.';
}

// Redirect back to referring page if it's dashboard, otherwise reservations list
$referer = $_SERVER['HTTP_REFERER'] ?? '';
if (strpos($referer, 'dashboard.php') !== false) {
    header('Location: ../../dashboard.php');
} else {
    header('Location: index.php');
}
exit();
?>
