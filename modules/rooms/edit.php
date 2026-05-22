<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rooms WHERE id=$id"));
if (!$room) {
    header('Location: index.php');
    exit();
}

$branding = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_name FROM settings LIMIT 1"));
$company_name = $branding['company_name'] ?? 'Villa';

$types = mysqli_query($conn, "SELECT * FROM room_types");
$error = '';
$success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
        $room_type_id = (int)$_POST['room_type_id'];
        $capacity = (int)$_POST['capacity'];
        $price_day = (float)$_POST['price_day'];
        $price_night = (float)$_POST['price_night'];
        $price_short = (float)$_POST['price_short'];
        $price = $price_night; // fallback
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);

        $image_query = "";
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($ext), $allowed)) {
                $type_query = mysqli_query($conn, "SELECT type_name FROM room_types WHERE id = $room_type_id");
                $type_row = mysqli_fetch_assoc($type_query);
                $type_name = $type_row ? $type_row['type_name'] : 'Type';

                $safe_room_number = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $room_number);
                $safe_type_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $type_name);

                $safe_company = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $company_name);
                $image_name = $safe_company . '_room_' . $safe_room_number . '_' . $safe_type_name . '.' . $ext;
                if (!is_dir('../../uploads')) {
                    mkdir('../../uploads', 0777, true);
                }
                if (move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/' . $image_name)) {
                    $image_query = ", image='$image_name'";
                    // Optional: delete old image
                    if (!empty($room['image']) && file_exists('../../uploads/' . $room['image'])) {
                        unlink('../../uploads/' . $room['image']);
                    }
                }
            }
        }

        $check = mysqli_query($conn, "SELECT id FROM rooms WHERE room_number='$room_number' AND id != $id");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Room number already exists!';
        } else {
            $query = "UPDATE rooms SET room_number='$room_number', room_type_id=$room_type_id, capacity=$capacity, 
                      price=$price, price_day=$price_day, price_night=$price_night, price_short=$price_short,
                      status='$status', description='$description' $image_query WHERE id=$id";
            if (mysqli_query($conn, $query)) {
                $old_room_data = [
                    'room_number' => $room['room_number'],
                    'room_type_id' => $room['room_type_id'],
                    'capacity' => $room['capacity'],
                    'price' => $room['price'],
                    'price_day' => $room['price_day'],
                    'price_night' => $room['price_night'],
                    'price_short' => $room['price_short'],
                    'status' => $room['status'],
                    'description' => $room['description'],
                    'image' => $room['image']
                ];
                $new_room_data = [
                    'room_number' => $room_number,
                    'room_type_id' => $room_type_id,
                    'capacity' => $capacity,
                    'price' => $price,
                    'price_day' => $price_day,
                    'price_night' => $price_night,
                    'price_short' => $price_short,
                    'status' => $status,
                    'description' => $description,
                    'image' => empty($image_name) ? $room['image'] : $image_name
                ];

                logAudit('UPDATE', 'rooms', $id, "Room $room_number updated", $old_room_data, $new_room_data);

                $_SESSION['success'] = 'Room updated successfully!';
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                $error = 'Failed to update room: ' . mysqli_error($conn);
            }
        }
    }

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Edit Room</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" name="room_number" class="form-control" value="<?= htmlspecialchars($room['room_number']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Room Type</label>
                            <select name="room_type_id" class="form-control" required>
                                <?php mysqli_data_seek($types, 0); while ($t = mysqli_fetch_assoc($types)): ?>
                                <option value="<?= $t['id'] ?>" <?= $t['id'] == $room['room_type_id'] ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" name="capacity" class="form-control" value="<?= $room['capacity'] ?>" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price - Day Time (<?= htmlspecialchars($global_currency) ?>)</label>
                                <input type="number" step="0.01" name="price_day" class="form-control" value="<?= $room['price_day'] ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price - Night Time (<?= htmlspecialchars($global_currency) ?>)</label>
                                <input type="number" step="0.01" name="price_night" class="form-control" value="<?= $room['price_night'] ?>" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Price - Short Time (<?= htmlspecialchars($global_currency) ?>)</label>
                                <input type="number" step="0.01" name="price_short" class="form-control" value="<?= $room['price_short'] ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3 text-center">
                            <label class="form-label d-block">Current Image</label>
                            <?php if ($room['image'] && file_exists('../../uploads/' . $room['image'])): ?>
                                <img src="../../uploads/<?= htmlspecialchars($room['image']) ?>" class="img-thumbnail mb-2" style="max-height: 200px;">
                            <?php else: ?>
                                <div class="bg-light border p-4 mb-2">No Image</div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        <option value="Available" <?= $room['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Occupied" <?= $room['status'] == 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                        <option value="Maintenance" <?= $room['status'] == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($room['description']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Room</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
