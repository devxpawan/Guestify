<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$check_in = isset($_GET['check_in']) ? str_replace('T', ' ', mysqli_real_escape_string($conn, $_GET['check_in'])) : '';
$check_out = isset($_GET['check_out']) ? str_replace('T', ' ', mysqli_real_escape_string($conn, $_GET['check_out'])) : '';

if (empty($check_in) || empty($check_out)) {
    echo json_encode([]);
    exit();
}

if (strtotime($check_out) <= strtotime($check_in)) {
    echo json_encode(['error' => 'Check-out must be after check-in']);
    exit();
}

$exclude_res_id = isset($_GET['exclude_res_id']) ? (int)$_GET['exclude_res_id'] : 0;
$exclude_cond = $exclude_res_id > 0 ? "AND id != $exclude_res_id" : "";

$room_type_id = isset($_GET['room_type_id']) ? (int)$_GET['room_type_id'] : 0;
$type_cond = $room_type_id > 0 ? "AND r.room_type_id = $room_type_id" : "";

$booking_type = isset($_GET['booking_type']) ? mysqli_real_escape_string($conn, $_GET['booking_type']) : 'Night Time';
$price_field = "r.price_night";
if ($booking_type === 'Day Time') {
    $price_field = "r.price_day";
} elseif ($booking_type === 'Short Time') {
    $price_field = "r.price_short";
}

$query = "SELECT r.id, r.room_number, t.type_name, $price_field AS price 
          FROM rooms r 
          JOIN room_types t ON r.room_type_id = t.id 
          WHERE r.status != 'Maintenance' AND r.is_active = 1 
          $type_cond
          AND r.id NOT IN (
              SELECT DISTINCT room_id FROM reservations 
              WHERE status NOT IN ('Cancelled', 'Checked-Out') 
              $exclude_cond 
              AND (
                  (check_in < '$check_out' AND check_out > '$check_in')
              )
          )
          ORDER BY r.room_number";

$result = mysqli_query($conn, $query);
$rooms = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = [
        'id' => $row['id'],
        'room_number' => $row['room_number'],
        'type_name' => $row['type_name'],
        'price' => (float)$row['price']
    ];
}

header('Content-Type: application/json');
echo json_encode($rooms);
exit();
