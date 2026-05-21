<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit_log.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$types = mysqli_query($conn, "SELECT * FROM room_types");
$branding = mysqli_fetch_assoc(mysqli_query($conn, "SELECT company_name FROM settings LIMIT 1"));
$company_name = $branding['company_name'] ?? 'Villa';
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
        $room_type_id = (int)$_POST['room_type_id'];
        $capacity = (int)$_POST['capacity'];
        $price_day = (float)$_POST['price_day'];
        $price_night = (float)$_POST['price_night'];
        $price_short = (float)$_POST['price_short'];
        $price = $price_night; // fallback/standard price
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        $image_name = '';
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
                move_uploaded_file($_FILES['image']['tmp_name'], '../../uploads/' . $image_name);
            }
        }

        $check = mysqli_query($conn, "SELECT id FROM rooms WHERE room_number='$room_number'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Room number already exists!';
        } else {
            $query = "INSERT INTO rooms (room_number, room_type_id, capacity, price, price_day, price_night, price_short, description, image) 
                      VALUES ('$room_number', $room_type_id, $capacity, $price, $price_day, $price_night, $price_short, '$description', '$image_name')";
            if (mysqli_query($conn, $query)) {
                $room_id = mysqli_insert_id($conn);
                log_activity('create', 'rooms', $room_id, null, [
                    'room_number' => $room_number,
                    'room_type_id' => $room_type_id,
                    'capacity' => $capacity,
                    'price' => $price,
                    'price_day' => $price_day,
                    'price_night' => $price_night,
                    'price_short' => $price_short,
                    'description' => $description,
                    'image' => $image_name
                ]);
                $_SESSION['success'] = 'Room added successfully!';
                header("Location: index.php");
                exit();
            } else {
                $error = 'Failed to add room: ' . mysqli_error($conn);
            }
        }
    }

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <h2>Add Room</h2>

    <div class="card">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Room Number</label>
                    <input type="text" name="room_number" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room Type</label>
                    <select name="room_type_id" class="form-control" required>
                        <option value="">Select Type</option>
                        <?php while ($t = mysqli_fetch_assoc($types)): ?>
                        <option value="<?= $t['id'] ?>"><?= $t['type_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" class="form-control" required>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price - Day Time (<?= htmlspecialchars($global_currency) ?>)</label>
                        <input type="number" step="0.01" name="price_day" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price - Night Time (<?= htmlspecialchars($global_currency) ?>)</label>
                        <input type="number" step="0.01" name="price_night" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price - Short Time (<?= htmlspecialchars($global_currency) ?>)</label>
                        <input type="number" step="0.01" name="price_short" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Room Image</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Room</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
