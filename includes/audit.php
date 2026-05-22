<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/villa_context.php';

function logAudit($action, $module, $record_id = null, $description = '', $old_values = null, $new_values = null, $villa_id = null) {
    global $conn;

    $user_id = $_SESSION['user_id'] ?? 0;
    $username = $_SESSION['username'] ?? 'system';
    if ($villa_id === null) $villa_id = active_villa_id();

    $action = mysqli_real_escape_string($conn, $action);
    $module = mysqli_real_escape_string($conn, $module);
    $description = mysqli_real_escape_string($conn, $description);

    $old_values_json = $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null;
    $new_values_json = $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null;

    $record_id_val = $record_id ? (int)$record_id : 'NULL';
    $villa_id = (int)$villa_id;

    $stmt = mysqli_prepare($conn, "INSERT INTO audit_logs (villa_id, user_id, username, action, module, record_id, description, old_values, new_values) VALUES (?, ?, ?, ?, ?, $record_id_val, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iissssss', $villa_id, $user_id, $username, $action, $module, $description, $old_values_json, $new_values_json);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>
