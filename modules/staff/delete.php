<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
mysqli_query($conn, "DELETE FROM staff WHERE id=$id");
header('Location: index.php');
exit();
?>
