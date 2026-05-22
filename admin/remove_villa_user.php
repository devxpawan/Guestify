<?php
require_once '../includes/session.php';
require_once '../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = (int)$_POST['user_id'];
    $villa_id = (int)$_POST['villa_id'];

    if ($user_id > 0 && $villa_id > 0) {
        // Prevent removing yourself from the villa you're currently using
        if ($user_id == $_SESSION['user_id'] && $villa_id == active_villa_id()) {
            $_SESSION['error'] = 'You cannot remove yourself from the current villa.';
        } else {
            mysqli_query($conn, "DELETE FROM user_villas WHERE user_id = $user_id AND villa_id = $villa_id");
            $_SESSION['success'] = 'User removed from villa.';
        }
    }
}

$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header("Location: $redirect");
exit();
