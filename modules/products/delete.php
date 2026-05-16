<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

$id = (int)$_GET['id'];
mysqli_query($conn, "DELETE FROM products WHERE id=$id");
header('Location: index.php');
exit();
?>
