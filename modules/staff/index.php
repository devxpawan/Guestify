<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: ../../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verify_csrf_token();
    $id = (int)$_POST['id'];
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM staff WHERE id=$id AND " . active_villa_where_raw()));
    if ($item) {
        $old_is_active = $item['is_active'];
        $new_is_active = $item['is_active'] ? 0 : 1;
        mysqli_query($conn, "UPDATE staff SET is_active=$new_is_active WHERE id=$id AND " . active_villa_where_raw());
        $_SESSION['success'] = 'Staff status updated successfully!';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$position = isset($_GET['position']) ? mysqli_real_escape_string($conn, $_GET['position']) : '';
$is_active_filter = isset($_GET['is_active']) ? mysqli_real_escape_string($conn, $_GET['is_active']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

$where = [active_villa_where_raw()];
if ($search !== '') {
    $where[] = "(name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}
if ($position !== '') {
    $where[] = "position = '$position'";
}
if ($is_active_filter !== '') {
    $where[] = "is_active = " . (int)$is_active_filter;
}
if ($date_from !== '') {
    $where[] = "DATE(created_at) >= '$date_from'";
}
if ($date_to !== '') {
    $where[] = "DATE(created_at) <= '$date_to'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM staff $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$staff = mysqli_query($conn, "SELECT * FROM staff $where_clause ORDER BY id DESC LIMIT $offset, $per_page");
$positions_res = mysqli_query($conn, "SELECT id, position_name AS position FROM staff_positions WHERE " . active_villa_where_raw() . " ORDER BY position_name");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-person-badge"></i> Staff Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage team members and positions</p>
            </div>
            <div class="d-flex gap-2">
                <a href="positions.php" class="btn btn-outline-secondary"><i class="fas fa-tags"></i> Manage Positions</a>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Staff</a>
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
                <div class="col-md-3">
                    <label class="form-label small text-muted">Search</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Name, Email, or Phone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Position</label>
                    <select name="position" class="form-select form-select-sm">
                        <option value="">All Positions</option>
                        <?php while($p = mysqli_fetch_assoc($positions_res)): ?>
                        <option value="<?= htmlspecialchars($p['position']) ?>" <?= $position == $p['position'] ? 'selected' : '' ?>><?= htmlspecialchars($p['position']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small text-muted">State</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="1" <?= $is_active_filter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $is_active_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $position || $is_active_filter !== '' || $date_from || $date_to): ?>
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
                        <th>ID</th><th>Name</th><th>Position</th><th>Phone</th><th>NIC</th><th>Email</th><th>Salary</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($s = mysqli_fetch_assoc($staff)): ?>
                    <tr>
                        <td><strong>#<?= $s['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-purple bg-opacity-10 text-purple rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                                    <i class="bi bi-person-badge"></i>
                                </div>
                                <?= htmlspecialchars($s['name']) ?>
                            </div>
                        </td>
                        <td><span class="badge badge-purple"><?= htmlspecialchars($s['position']) ?></span></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($s['phone']) ?></td>
                        <td><?= htmlspecialchars($s['nic']) ?></td>
                        <td><i class="bi bi-envelope text-muted me-1"></i><?= htmlspecialchars($s['email']) ?></td>
                        <td><strong><?= htmlspecialchars($global_currency) ?><?= number_format($s['salary'], 2) ?></strong></td>
                        <td>
                            <span class="badge badge-<?= $s['is_active'] ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $s['is_active'] ? 'check-circle' : 'x-circle' ?>"></i> <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $s['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $s['is_active'] ? 'Deactivate' : 'Activate' ?>" style="width: 36px;">
                                        <i class="fas fa-<?= $s['is_active'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit" style="width: 36px;"><i class="fas fa-pencil-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> staff</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>