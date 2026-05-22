<?php
/**
 * Villa Context Helper
 * Provides functions for multi-villa support.
 * Include this after session.php and database.php.
 */

function active_villa_id() {
    return isset($_SESSION['villa_id']) ? (int)$_SESSION['villa_id'] : 0;
}

function active_villa_where($alias = '') {
    $vid = active_villa_id();
    if ($vid <= 0) return '1=0';
    $prefix = $alias ? "`$alias`." : '';
    return $prefix . "villa_id = $vid";
}

function active_villa_where_raw() {
    $vid = active_villa_id();
    if ($vid <= 0) return '1=0';
    return "villa_id = $vid";
}

function get_villa_branding($villa_id = null) {
    global $conn;
    if ($villa_id === null) $villa_id = active_villa_id();
    $villa_id = (int)$villa_id;
    if ($villa_id <= 0) return [];
    $q = @mysqli_query($conn, "SELECT * FROM villas WHERE id = $villa_id LIMIT 1");
    return ($q && $row = mysqli_fetch_assoc($q)) ? $row : [];
}

function get_user_villas($user_id = null) {
    global $conn;
    if ($user_id === null) $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0) return [];
    $user_id = (int)$user_id;
    $q = @mysqli_query($conn, "
        SELECT v.*, uv.is_default
        FROM user_villas uv
        JOIN villas v ON uv.villa_id = v.id
        WHERE uv.user_id = $user_id AND v.status = 'Active'
        ORDER BY v.name
    ");
    $villas = [];
    if ($q) {
        while ($row = mysqli_fetch_assoc($q)) {
            $villas[] = $row;
        }
    }
    return $villas;
}

function set_active_villa($villa_id) {
    global $conn;
    $villa_id = (int)$villa_id;
    $user_id = (int)($_SESSION['user_id'] ?? 0);
    if ($user_id <= 0 || $villa_id <= 0) return false;

    $q = @mysqli_query($conn, "
        SELECT 1 FROM user_villas
        WHERE user_id = $user_id AND villa_id = $villa_id
        LIMIT 1
    ");
    if (!$q || mysqli_num_rows($q) === 0) return false;

    $_SESSION['villa_id'] = $villa_id;
    return true;
}

function user_has_villa_access($villa_id, $user_id = null) {
    global $conn;
    $villa_id = (int)$villa_id;
    if ($user_id === null) $user_id = (int)($_SESSION['user_id'] ?? 0);
    $user_id = (int)$user_id;
    if ($user_id <= 0 || $villa_id <= 0) return false;

    $role = $_SESSION['role'] ?? '';
    if ($role === 'Admin') return true;

    $q = @mysqli_query($conn, "
        SELECT 1 FROM user_villas
        WHERE user_id = $user_id AND villa_id = $villa_id
        LIMIT 1
    ");
    return $q && mysqli_num_rows($q) > 0;
}
