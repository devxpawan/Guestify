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
        $check = mysqli_query($conn, "SELECT id FROM rooms WHERE room_type_id=$id");
        if (mysqli_num_rows($check) > 0) {
            $error = "Cannot delete: This type is being used by rooms.";
        } else {
            mysqli_query($conn, "DELETE FROM room_types WHERE id=$id");
            $success = "Room type deleted.";
        }
    }
}

$types = mysqli_query($conn, "SELECT rt.*, COUNT(r.id) as total_rooms, SUM(CASE WHEN r.status = 'Available' THEN 1 ELSE 0 END) as available_rooms FROM room_types rt LEFT JOIN rooms r ON rt.id = r.room_type_id GROUP BY rt.id ORDER BY rt.id");

include "../../includes/header.php";
include "../../includes/sidebar.php";
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-tags"></i> Room Types Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Define and manage room categories</p>
            </div>
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Rooms</a>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $success ?></div><?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="profile-card mb-4">
                <div class="card-header"><h5><i class="bi bi-plus-circle"></i> Add New Type</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Type Name</label>
                            <input type="text" name="type_name" class="form-control" required placeholder="e.g. Penthouse, Deluxe, Suite">
                        </div>
                        <button type="submit" name="add_type" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Room Type</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="table-container">
                <div class="card-header"><h5><i class="bi bi-list-ul"></i> Existing Types</h5></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type Name</th>
                                <th class="text-center">Total Rooms</th>
                                <th class="text-center">Available</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($t = mysqli_fetch_assoc($types)): ?>
                            <tr>
                                <td><strong>#<?= $t['id'] ?></strong></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <strong><?= htmlspecialchars($t['type_name']) ?></strong>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary"><?= $t['total_rooms'] ?> rooms</span>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-<?= $t['available_rooms'] > 0 ? 'success' : 'danger' ?>">
                                        <?= $t['available_rooms'] ?: 0 ?> available
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="index.php?type_id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> View Rooms
                                        </a>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Delete this type?')">
                                            <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                            <button type="submit" name="delete_type" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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
