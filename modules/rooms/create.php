<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist'])) {
    header('Location: index.php');
    exit();
}

$types = mysqli_query($conn, "SELECT * FROM room_types");
    $error = '';
    $success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $room_number = mysqli_real_escape_string($conn, $_POST['room_number']);
        $room_type_id = (int)$_POST['room_type_id'];
        $capacity = (int)$_POST['capacity'];
        $price = (float)$_POST['price'];
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            if (in_array(strtolower($ext), $allowed)) {
                $image_name = 'room_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'], '../../assets/images/rooms/' . $image_name);
            }
        }

        $check = mysqli_query($conn, "SELECT id FROM rooms WHERE room_number='$room_number'");
        if (mysqli_num_rows($check) > 0) {
            $error = 'Room number already exists!';
        } else {
            $query = "INSERT INTO rooms (room_number, room_type_id, capacity, price, description, image) 
                      VALUES ('$room_number', $room_type_id, $capacity, $price, '$description', '$image_name')";
            if (mysqli_query($conn, $query)) {
                $success = 'Room added successfully!';
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
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
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
                <div class="mb-3">
                    <label class="form-label">Price Per Night</label>
                    <input type="number" step="0.01" name="price" class="form-control" required>
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
