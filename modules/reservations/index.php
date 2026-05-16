<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$reservations = mysqli_query($conn, "SELECT r.*, c.full_name, rm.room_number 
                                     FROM reservations r 
                                     JOIN customers c ON r.customer_id = c.id 
                                     JOIN rooms rm ON r.room_id = rm.id 
                                     ORDER BY r.id DESC");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="d-flex justify-content-between mb-3">
        <h2>Reservation Management</h2>
        <div>
            <a href="calendar.php" class="btn btn-outline-info me-2"><i class="bi bi-calendar3"></i> View Calendar</a>
            <?php if (has_role(['Admin', 'Receptionist'])): ?>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Reservation</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Customer</th><th>Room</th><th>Check-In</th><th>Check-Out</th><th>Adults</th><th>Children</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($reservations)): ?>
                    <tr>
                        <td><?= $r['id'] ?></td>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= $r['room_number'] ?></td>
                        <td><?= $r['check_in'] ?></td>
                        <td><?= $r['check_out'] ?></td>
                        <td><?= $r['adults'] ?></td>
                        <td><?= $r['children'] ?></td>
                        <td>
                            <span class="badge bg-<?= $r['status'] == 'Confirmed' ? 'success' : ($r['status'] == 'Pending' ? 'warning' : ($r['status'] == 'Checked-In' ? 'info' : ($r['status'] == 'Checked-Out' ? 'secondary' : 'dark'))) ?>">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                            <?php if ($r['status'] == 'Pending'): ?>
                            <a href="status.php?id=<?= $r['id'] ?>&s=Confirmed" class="btn btn-sm btn-success">Confirm</a>
                            <?php endif; ?>
                            <?php if ($r['status'] == 'Confirmed'): ?>
                            <a href="status.php?id=<?= $r['id'] ?>&s=Checked-In" class="btn btn-sm btn-info">Check-In</a>
                            <?php endif; ?>
                            <?php if ($r['status'] == 'Checked-In'): ?>
                            <a href="status.php?id=<?= $r['id'] ?>&s=Checked-Out" class="btn btn-sm btn-secondary">Check-Out</a>
                            <?php endif; ?>
                            <?php if (has_role(['Admin']) && $r['status'] != 'Cancelled' && $r['status'] != 'Checked-Out'): ?>
                            <a href="status.php?id=<?= $r['id'] ?>&s=Cancelled" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this reservation?')">Cancel</a>
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
