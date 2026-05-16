<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$project_root = realpath(__DIR__ . '/..');
$current_script = realpath($_SERVER['SCRIPT_FILENAME']);
$relative_path = str_replace($project_root, '', $current_script);
$depth = substr_count(trim($relative_path, DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);

$base_url = str_repeat('../', $depth);
if ($depth == 0) $base_url = './';

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: ' . $base_url . 'login.php');
    exit();
}

function has_role($roles) {
    $allowed = is_array($roles) ? $roles : [$roles];
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $allowed);
}
?>
