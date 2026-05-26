<?php
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

function verify_csrf_token() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $submitted = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submitted)) {
        $_SESSION['error'] = 'Invalid or expired form token. Please try again.';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'index.php';
        header("Location: $referer");
        exit();
    }
}
