<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$rooms = mysqli_query($conn, "SELECT r.*, t.type_name FROM rooms r JOIN room_types t ON r.room_type_id = t.id ORDER BY r.id DESC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Room Management</h2>
        <div>
            <?php if (has_role(['Admin'])): ?>
            <a href="types.php" class="btn btn-outline-secondary me-2"><i class="bi bi-tags"></i> Manage Types</a>
            <?php endif; ?>
            <?php if (has_role(['Admin', 'Receptionist'])): ?>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Room</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
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
                                <img src="../../assets/images/rooms/<?= $row['image'] ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light border text-center" style="width: 50px; height: 50px; line-height: 50px; font-size: 10px;">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['room_number']) ?></td>
                        <td><?= $row['type_name'] ?></td>
                        <td><?= $row['capacity'] ?></td>
                        <td>$<?= number_format($row['price'], 2) ?></td>
                        <td>
                            <span class="badge bg-<?= $row['status'] == 'Available' ? 'success' : ($row['status'] == 'Occupied' ? 'danger' : 'warning') ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php if (has_role(['Admin'])): ?>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
