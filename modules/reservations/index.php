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
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-calendar-check"></i> Reservation Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage all guest reservations and bookings</p>
            </div>
            <div class="d-flex gap-2">
                <a href="calendar.php" class="btn btn-outline-info"><i class="bi bi-calendar3"></i> View Calendar</a>
                <?php if (has_role(['Admin', 'Receptionist'])): ?>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Reservation</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th><th>Customer</th><th>Room</th><th>Check-In</th><th>Check-Out</th><th>Guests</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($reservations)): 
                        $is_late = (strtotime($r['check_in']) < time() && in_array($r['status'], ['Pending', 'Confirmed']));
                    ?>
                    <tr class="<?= $is_late ? 'table-danger' : '' ?>">
                        <td><strong>#<?= $r['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($r['full_name']) ?></strong>
                                    <?php if ($is_late): ?>
                                        <br><span class="badge badge-danger"><i class="bi bi-exclamation-triangle"></i> NO-SHOW</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-secondary">Room <?= $r['room_number'] ?></span>
                            <br><small class="text-muted font-monospace"><?= htmlspecialchars($r['booking_type']) ?></small>
                        </td>
                        <td>
                            <div><?= date('M d, Y', strtotime($r['check_in'])) ?></div>
                            <small class="text-muted"><?= date('h:i A', strtotime($r['check_in'])) ?></small>
                        </td>
                        <td>
                            <div><?= date('M d, Y', strtotime($r['check_out'])) ?></div>
                            <small class="text-muted"><?= date('h:i A', strtotime($r['check_out'])) ?></small>
                        </td>
                        <td>
                            <span class="badge badge-info"><?= $r['adults'] ?>A / <?= $r['children'] ?>C</span>
                        </td>
                        <td>
                            <span class="badge badge-<?= $r['status'] == 'Confirmed' ? 'success' : ($r['status'] == 'Pending' ? 'warning' : ($r['status'] == 'Checked-In' ? 'info' : ($r['status'] == 'Checked-Out' ? 'secondary' : 'danger'))) ?>">
                                <?= $r['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <?php if ($r['status'] == 'Pending'): ?>
                                <a href="status.php?id=<?= $r['id'] ?>&s=Confirmed" class="btn btn-sm btn-success" title="Confirm"><i class="bi bi-check-circle"></i></a>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Confirmed'): ?>
                                <a href="status.php?id=<?= $r['id'] ?>&s=Checked-In" class="btn btn-sm btn-info" title="Check-In"><i class="bi bi-box-arrow-in-right"></i></a>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Checked-In'): ?>
                                <a href="status.php?id=<?= $r['id'] ?>&s=Checked-Out" class="btn btn-sm btn-secondary" title="Check-Out"><i class="bi bi-box-arrow-right"></i></a>
                                <?php endif; ?>
                                <?php if ($r['status'] != 'Cancelled' && $r['status'] != 'Checked-Out'): ?>
                                <a href="status.php?id=<?= $r['id'] ?>&s=Cancelled" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this reservation? This will free up the room.')" title="Cancel"><i class="bi bi-x-circle"></i></a>
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
