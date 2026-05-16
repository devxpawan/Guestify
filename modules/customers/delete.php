<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

$id = (int)$_GET['id'];
$check = mysqli_query($conn, "SELECT id FROM reservations WHERE customer_id=$id LIMIT 1");
if (mysqli_num_rows($check) > 0) {
    $_SESSION['error'] = 'Cannot delete customer with existing reservations.';
} else {
    mysqli_query($conn, "DELETE FROM customers WHERE id=$id");
}
header('Location: index.php');
exit();
?>
