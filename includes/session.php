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
$normalized_path = '/' . ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $relative_path), '/');

if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php') {
    header('Location: ' . $base_url . 'login.php');
    exit();
}

// Auto-initialize villa context if user is logged in but no villa_id is set
if (isset($_SESSION['user_id']) && !isset($_SESSION['villa_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $uid = (int)$_SESSION['user_id'];
    $q = @mysqli_query($conn, "SELECT villa_id FROM user_villas WHERE user_id = $uid AND is_default = 1 LIMIT 1");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        $_SESSION['villa_id'] = (int)$row['villa_id'];
    } else {
        $q2 = @mysqli_query($conn, "SELECT villa_id FROM user_villas WHERE user_id = $uid LIMIT 1");
        if ($q2 && $row2 = mysqli_fetch_assoc($q2)) {
            $_SESSION['villa_id'] = (int)$row2['villa_id'];
        } else {
            $_SESSION['villa_id'] = 0;
        }
    }
}

// Load villa context helpers for use in all pages
require_once __DIR__ . '/villa_context.php';

function has_role($roles) {
    $allowed = is_array($roles) ? $roles : [$roles];
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $allowed);
}

function is_active_link($urls) {
    global $normalized_path;
    if (!is_array($urls)) {
        $urls = [$urls];
    }
    foreach ($urls as $url) {
        if (strpos($normalized_path, $url) !== false) {
            return true;
        }
    }
    return false;
}
?>
