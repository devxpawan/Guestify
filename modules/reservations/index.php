<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

$where = [];
$where[] = active_villa_where('r');
if ($search !== '') {
    $where[] = "(c.full_name LIKE '%$search%' OR r.id = '" . (int)$search . "' OR r.group_id = '" . (int)$search . "')";
}
if ($status !== '') {
    $where[] = "r.status = '$status'";
}
if ($date_from !== '') {
    $where[] = "DATE(r.created_at) >= '$date_from'";
}
if ($date_to !== '') {
    $where[] = "DATE(r.created_at) <= '$date_to'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination (by unique group)
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(DISTINCT COALESCE(r.group_id, r.id)) AS total FROM reservations r JOIN customers c ON r.customer_id = c.id $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$reservations = mysqli_query($conn, "SELECT 
    COALESCE(r.group_id, r.id) AS gid,
    GROUP_CONCAT(DISTINCT rm.room_number ORDER BY rm.room_number SEPARATOR ', ') AS room_numbers,
    GROUP_CONCAT(r.id ORDER BY rm.room_number) AS reservation_ids,
    r.group_id,
    MIN(r.check_in) AS check_in,
    MAX(r.check_out) AS check_out,
    SUM(r.adults) AS adults,
    SUM(r.children) AS children,
    COUNT(*) AS room_count,
    MAX(c.full_name) AS full_name,
    MIN(r.booking_type) AS booking_type,
    MIN(r.created_at) AS created_at,
    MIN(r.status) AS status
FROM reservations r
JOIN customers c ON r.customer_id = c.id
JOIN rooms rm ON r.room_id = rm.id
$where_clause
GROUP BY COALESCE(r.group_id, r.id)
ORDER BY gid DESC LIMIT $offset, $per_page");
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
                <div class="col-md-2">
                    <label class="form-label small text-muted">From Date</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">To Date</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Res ID, Group ID or Customer Name..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?= $status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Confirmed" <?= $status == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="Checked-In" <?= $status == 'Checked-In' ? 'selected' : '' ?>>Checked-In</option>
                        <option value="Checked-Out" <?= $status == 'Checked-Out' ? 'selected' : '' ?>>Checked-Out</option>
                        <option value="Cancelled" <?= $status == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $status || $date_from || $date_to): ?>
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
                        <th>ID</th><th>Customer</th><th>Room(s)</th><th>Check-In</th><th>Check-Out</th><th>Guests</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = mysqli_fetch_assoc($reservations)): 
                        $is_late = (strtotime($r['check_in']) < time() && in_array($r['status'], ['Pending', 'Confirmed']));
                        $res_ids = explode(',', $r['reservation_ids']);
                        $first_id = (int)$res_ids[0];
                        $is_group = $r['room_count'] > 1;
                        $display_id = $is_group ? 'G' . $r['gid'] : '#' . $first_id;
                    ?>
                    <tr class="<?= $is_late ? 'table-danger' : '' ?>">
                        <td><strong><?= $display_id ?></strong></td>
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
                            <?php if ($is_group): ?>
                                <span class="badge badge-secondary"><?= count($res_ids) ?> Rooms</span><br>
                            <?php endif; ?>
                            <span class="badge badge-secondary">Room <?= htmlspecialchars($r['room_numbers']) ?></span>
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
                                <a href="edit.php?<?= $r['group_id'] ? 'gid=' . $r['gid'] : 'id=' . $first_id ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Pending'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Confirm reservation?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="gid" value="<?= $r['gid'] ?>">
                                    <input type="hidden" name="status" value="Confirmed">
                                    <button type="submit" class="btn btn-sm btn-success" title="Confirm"><i class="fas fa-check-circle"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Confirmed'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Check-In guest?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="gid" value="<?= $r['gid'] ?>">
                                    <input type="hidden" name="status" value="Checked-In">
                                    <button type="submit" class="btn btn-sm btn-info" title="Check-In"><i class="fas fa-sign-in-alt"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['status'] == 'Checked-In'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Check-Out guest?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="gid" value="<?= $r['gid'] ?>">
                                    <input type="hidden" name="status" value="Checked-Out">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Check-Out"><i class="fas fa-sign-out-alt"></i></button>
                                </form>
                                <?php endif; ?>
                                <?php if ($r['status'] != 'Cancelled' && $r['status'] != 'Checked-Out'): ?>
                                <form method="POST" action="status.php" class="d-inline" onsubmit="return confirm('Cancel reservation? This will free up the room(s).');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="gid" value="<?= $r['gid'] ?>">
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
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> reservation groups</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
