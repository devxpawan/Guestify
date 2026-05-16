<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
mysqli_query($conn, "DELETE FROM rooms WHERE id=$id");
header('Location: index.php');
exit();
?>
