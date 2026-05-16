<?php
require_once "../../includes/session.php";
require_once "../../config/database.php";

if (!has_role(['Admin'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_type'])) {
        $type_name = mysqli_real_escape_string($conn, $_POST['type_name']);
        if (!empty($type_name)) {
            $query = "INSERT INTO room_types (type_name) VALUES ('$type_name')";
            if (mysqli_query($conn, $query)) {
                $success = "Room type added successfully!";
            } else {
                $error = "Error: " . mysqli_error($conn);
            }
        }
    } elseif (isset($_POST['delete_type'])) {
        $id = (int)$_POST['id'];
        // Check if rooms use this type
        $check = mysqli_query($conn, "SELECT id FROM rooms WHERE room_type_id=$id");
        if (mysqli_num_rows($check) > 0) {
            $error = "Cannot delete: This type is being used by rooms.";
        } else {
            mysqli_query($conn, "DELETE FROM room_types WHERE id=$id");
            $success = "Room type deleted.";
        }
    }
}

$types = mysqli_query($conn, "SELECT * FROM room_types ORDER BY id");

include "../../includes/header.php";
include "../../includes/sidebar.php";
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Room Types Management</h2>
        <a href="index.php" class="btn btn-secondary">Back to Rooms</a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5>Add New Type</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Type Name</label>
                            <input type="text" name="type_name" class="form-control" required placeholder="e.g. Penthouse">
                        </div>
                        <button type="submit" name="add_type" class="btn btn-primary w-100">Add Room Type</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h5>Existing Types</h5></div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead><tr><th>ID</th><th>Type Name</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php while ($t = mysqli_fetch_assoc($types)): ?>
                            <tr>
                                <td><?= $t['id'] ?></td>
                                <td><?= htmlspecialchars($t['type_name']) ?></td>
                                <td>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this type?')">
                                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                        <button type="submit" name="delete_type" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "../../includes/footer.php"; ?>
