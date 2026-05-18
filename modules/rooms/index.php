<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
$where_clause = "";
$filter_name = "";

if ($type_id > 0) {
    $where_clause = " WHERE r.room_type_id = $type_id ";
    $type_res = mysqli_query($conn, "SELECT type_name FROM room_types WHERE id = $type_id");
    if ($t_row = mysqli_fetch_assoc($type_res)) {
        $filter_name = $t_row['type_name'];
    }
}

$rooms = mysqli_query($conn, "SELECT r.*, t.type_name FROM rooms r JOIN room_types t ON r.room_type_id = t.id $where_clause ORDER BY r.id DESC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-building"></i> Room Management</h2>
                <?php if ($filter_name): ?>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none">All Rooms</a></li>
                        <li class="breadcrumb-item active">Type: <?= $filter_name ?></li>
                    </ol>
                </nav>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <?php if ($type_id > 0): ?>
                    <a href="index.php" class="btn btn-outline-danger"><i class="bi bi-x-lg"></i> Clear Filter</a>
                <?php endif; ?>
                <?php if (has_role(['Admin'])): ?>
                <a href="types.php" class="btn btn-outline-secondary"><i class="bi bi-tags"></i> Manage Types</a>
                <?php endif; ?>
                <?php if (has_role(['Admin', 'Receptionist'])): ?>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Room</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Image</th><th>Room No.</th><th>Type</th><th>Capacity</th><th>Price/Night</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($rooms)): ?>
                    <tr>
                        <td>
                            <?php if ($row['image']): ?>
                                <?php
                                $img_src = file_exists('../../uploads/' . $row['image']) ? '../../uploads/' . $row['image'] : '../../assets/images/rooms/' . $row['image'];
                                ?>
                                <img src="<?= $img_src ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div class="bg-light border text-center d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 8px; font-size: 10px; color: #94a3b8;">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($row['room_number']) ?></strong></td>
                        <td><?= $row['type_name'] ?></td>
                        <td><span class="badge badge-secondary"><?= $row['capacity'] ?> <i class="bi bi-person"></i></span></td>
                        <td><strong><?= htmlspecialchars($global_currency) ?><?= number_format($row['price'], 2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $row['status'] == 'Available' ? 'success' : ($row['status'] == 'Occupied' ? 'danger' : 'warning') ?>">
                                <i class="bi bi-<?= $row['status'] == 'Available' ? 'check-circle' : ($row['status'] == 'Occupied' ? 'x-circle' : 'clock') ?>"></i> <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if (has_role(['Admin'])): ?>
                                <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
