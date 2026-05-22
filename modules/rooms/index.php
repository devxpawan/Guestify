<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

$vid = (int)active_villa_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!has_role(['Admin'])) {
        $_SESSION['error'] = 'Permission denied.';
        header("Location: index.php");
        exit();
    }
    $id = (int)$_POST['id'];
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM rooms WHERE id=$id AND villa_id=$vid"));
    if ($item) {
        $old_is_active = $item['is_active'];
        $new_is_active = $item['is_active'] ? 0 : 1;
        mysqli_query($conn, "UPDATE rooms SET is_active=$new_is_active WHERE id=$id");
        $_SESSION['success'] = 'Room status updated successfully!';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$type_id = isset($_GET['type_id']) ? (int)$_GET['type_id'] : 0;
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$is_active_filter = isset($_GET['is_active']) ? mysqli_real_escape_string($conn, $_GET['is_active']) : '';

$where = [];
$where[] = active_villa_where('r');
if ($search !== '') {
    $where[] = "r.room_number LIKE '%$search%'";
}
if ($type_id > 0) {
    $where[] = "r.room_type_id = $type_id";
}
if ($status !== '') {
    $where[] = "r.status = '$status'";
}
if ($is_active_filter !== '') {
    $where[] = "r.is_active = " . (int)$is_active_filter;
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rooms r $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$rooms = mysqli_query($conn, "SELECT r.*, t.type_name FROM rooms r JOIN room_types t ON r.room_type_id = t.id $where_clause ORDER BY r.id DESC LIMIT $offset, $per_page");
$types_res = mysqli_query($conn, "SELECT * FROM room_types WHERE villa_id=$vid ORDER BY type_name");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-building"></i> Room Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage your villa's accommodations and statuses</p>
            </div>
            <div class="d-flex gap-2">
                <?php if (has_role(['Admin'])): ?>
                <a href="types.php" class="btn btn-outline-secondary"><i class="bi bi-tags"></i> Manage Types</a>
                <?php endif; ?>
                <?php if (has_role(['Admin', 'Receptionist'])): ?>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Room</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Room No..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="type_id" class="form-select">
                        <option value="">All Room Types</option>
                        <?php while($t = mysqli_fetch_assoc($types_res)): ?>
                        <option value="<?= $t['id'] ?>" <?= $type_id == $t['id'] ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Available" <?= $status == 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="Occupied" <?= $status == 'Occupied' ? 'selected' : '' ?>>Occupied</option>
                        <option value="Maintenance" <?= $status == 'Maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="is_active" class="form-select">
                        <option value="">All States</option>
                        <option value="1" <?= $is_active_filter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $is_active_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $type_id || $status || $is_active_filter !== ''): ?>
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
                        <th>Image</th><th>Room No.</th><th>Type</th><th>Capacity</th><th>Pricing</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($rooms)): ?>
                    <tr>
                        <td>
                            <?php if ($row['image'] && file_exists('../../uploads/' . $row['image'])): ?>
                                <img src="../../uploads/<?= htmlspecialchars($row['image']) ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div class="bg-light border text-center d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; border-radius: 8px; font-size: 10px; color: #94a3b8;">No Img</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($row['room_number']) ?></strong>
                            <?php if ($row['status'] == 'Available'): ?>
                                <div class="small text-muted">Available</div>
                            <?php endif; ?>
                        </td>
                        <td><?= $row['type_name'] ?></td>
                        <td><span class="badge badge-secondary"><?= $row['capacity'] ?> <i class="bi bi-person"></i></span></td>
                        <td>
                            <div class="small text-muted">Day: <strong class="text-dark"><?= htmlspecialchars($global_currency) ?><?= number_format($row['price_day'], 2) ?></strong></div>
                            <div class="small text-muted">Night: <strong class="text-dark"><?= htmlspecialchars($global_currency) ?><?= number_format($row['price_night'], 2) ?></strong></div>
                            <div class="small text-muted">Short: <strong class="text-dark"><?= htmlspecialchars($global_currency) ?><?= number_format($row['price_short'], 2) ?></strong></div>
                        </td>
                        <td>
                            <span class="badge badge-<?= $row['is_active'] ? 'success' : 'danger' ?> d-block">
                                <i class="bi bi-<?= $row['is_active'] ? 'check-circle' : 'x-circle' ?>"></i> <?= $row['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <?php if (has_role(['Admin'])): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $row['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $row['is_active'] ? 'Deactivate' : 'Activate' ?>" style="width: 36px;">
                                        <i class="fas fa-<?= $row['is_active'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit" style="width: 36px;"><i class="fas fa-pencil-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> rooms</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>