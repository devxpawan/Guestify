<?php
require_once 'includes/session.php';
require_once 'config/database.php';

$villa_id = isset($_GET['villa_id']) ? (int)$_GET['villa_id'] : 0;
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'dashboard.php';

if ($villa_id > 0) {
    require_once 'includes/villa_context.php';
    set_active_villa($villa_id);
}

header("Location: $redirect");
exit();
