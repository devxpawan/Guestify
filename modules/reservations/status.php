<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$new_status = mysqli_real_escape_string($conn, $_GET['s']);

$res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id=$id"));
if ($res) {
    mysqli_query($conn, "UPDATE reservations SET status='$new_status' WHERE id=$id");

    if ($new_status == 'Checked-In') {
        mysqli_query($conn, "UPDATE rooms SET status='Occupied' WHERE id=" . $res['room_id']);
    } elseif (in_array($new_status, ['Checked-Out', 'Cancelled'])) {
        mysqli_query($conn, "UPDATE rooms SET status='Available' WHERE id=" . $res['room_id']);
    }
}

header('Location: index.php');
exit();
?>
