<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$where = [];
if ($search !== '') {
    $where[] = "(c.full_name LIKE '%$search%' OR r.id = '" . (int)$search . "')";
}
if ($status !== '') {
    $where[] = "r.status = '$status'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM reservations r JOIN customers c ON r.customer_id = c.id $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$reservations = mysqli_query($conn, "SELECT r.*, c.full_name, rm.room_number 
                                     FROM reservations r 
                                     JOIN customers c ON r.customer_id = c.id 
                                     JOIN rooms rm ON r.room_id = rm.id 
                                     $where_clause
                                     ORDER BY r.id DESC LIMIT $offset, $per_page");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-calendar-check"></i> Reservation Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage all guest reservations and bookings</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (has_role(['Admin', 'Receptionist'])): ?>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> New Reservation</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by Res ID or Customer Name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= $status == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Checked-In" <?= $status == 'Checked-In' ? 'selected' : '' ?>>Checked-In</option>
                        <option value="Checked-Out" <?= $status == 'Checked-Out' ? 'selected' : '' ?>>Checked-Out</option>
                        <option value="Cancelled" <?= $status == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $status): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
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
                            <div class="action-btns d-flex gap-1 align-items-center">
                                <?php if ($r['status'] == 'Pending'): ?>
                                <a href="edit.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Pending'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Confirm reservation #<?= $r['id'] ?>?');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="Confirmed">
                                    <button type="submit" class="btn btn-sm btn-success" title="Confirm"><i class="fas fa-check-circle"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Confirmed'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Check-In guest for reservation #<?= $r['id'] ?>?');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="Checked-In">
                                    <button type="submit" class="btn btn-sm btn-info" title="Check-In"><i class="fas fa-sign-in-alt"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Checked-In'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Check-Out guest for reservation #<?= $r['id'] ?>?');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="Checked-Out">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Check-Out"><i class="fas fa-sign-out-alt"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['status'] != 'Cancelled' && $r['status'] != 'Checked-Out'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Cancel reservation #<?= $r['id'] ?>? This will free up the room.');">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="status" value="Cancelled">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-times-circle"></i></button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> reservations</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>