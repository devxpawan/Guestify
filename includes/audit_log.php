<?php
// includes/audit_log.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Logs an activity to the audit_logs table.
 *
 * @param string $action The action performed (e.g., 'create', 'update', 'delete', 'login').
 * @param string $table_name The name of the table affected.
 * @param int|null $record_id The ID of the record affected. Null if not applicable (e.g., login).
 * @param array|null $old_value The old data before the action (for 'update' actions).
 * @param array|null $new_value The new data after the action (for 'create' and 'update' actions).
 * @return bool True on success, false on failure.
 */
function log_activity($action, $table_name, $old_value = null, $new_value = null) {
    global $conn; // Use the global database connection

    $user_id = $_SESSION['user_id'] ?? null; // Get user ID from session

    // Serialize old and new values to JSON
    $old_value_json = $old_value ? json_encode($old_value) : null;
    $new_value_json = $new_value ? json_encode($new_value) : null;

    $stmt = $conn->prepare(
        "INSERT INTO audit_logs (user_id, action, table_name, old_value, new_value)
         VALUES (?, ?, ?, ?, ?)"
    );

    if ($stmt === false) {
        error_log("Failed to prepare audit log statement: " . $conn->error);
        return false;
    }

    $stmt->bind_param(
        "issss", // i: integer, s: string
        $user_id,
        $action,
        $table_name,
        $old_value_json,
        $new_value_json
    );

    $result = $stmt->execute();

    if ($result === false) {
        error_log("Failed to execute audit log statement: " . $stmt->error);
    }

    $stmt->close();

    return $result;
}
