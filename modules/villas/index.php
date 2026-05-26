<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

if (!has_role(['Admin'])) {
    header('Location: ../../dashboard.php');
    exit();
}

// Toggle villa status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verify_csrf_token();
    $id = (int)$_POST['id'];
    if ($id === active_villa_id()) {
        $_SESSION['error'] = 'You cannot deactivate the villa you are currently logged into.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM villas WHERE id=$id"));
    if ($item) {
        $new_status = $item['status'] === 'Active' ? 'Inactive' : 'Active';
        mysqli_query($conn, "UPDATE villas SET status='$new_status' WHERE id=$id");
        $_SESSION['success'] = 'Villa status updated!';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$where = [];
if ($search !== '') {
    $where[] = "name LIKE '%$search%'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM villas $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$villas = mysqli_query($conn, "SELECT v.*, (SELECT COUNT(*) FROM rooms WHERE villa_id = v.id) as room_count, (SELECT COUNT(*) FROM user_villas WHERE villa_id = v.id) as user_count, (SELECT COUNT(*) FROM staff WHERE villa_id = v.id) as staff_count FROM villas v $where_clause ORDER BY v.id ASC LIMIT $offset, $per_page");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-building-gear"></i> Villa Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage multiple properties and their settings</p>
            </div>
            <div>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Villa</a>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by villa name..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search): ?>
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
                        <th>ID</th>
                        <th>Name</th>
                        <th>Currency</th>
                        <th>Rooms</th>
                        <th>Staff</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($villas)): ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($row['logo_path'] && file_exists('../../uploads/' . $row['logo_path'])): ?>
                                    <img src="../../uploads/<?= htmlspecialchars($row['logo_path']) ?>" style="width: 28px; height: 28px; object-fit: contain; border-radius: 4px;">
                                <?php endif; ?>
                                <strong><?= htmlspecialchars($row['name']) ?></strong>
                            </div>
                        </td>
                        <td><strong><?= htmlspecialchars($row['currency_symbol']) ?></strong></td>
                        <td><span class="badge bg-info"><?= $row['room_count'] ?></span></td>
                        <td><span class="badge bg-warning text-dark"><?= $row['staff_count'] ?></span></td>
                        <td><span class="badge bg-secondary"><?= $row['user_count'] ?></span></td>
                        <td>
                            <span class="badge badge-<?= $row['status'] === 'Active' ? 'success' : 'danger' ?>">
                                <?= $row['status'] ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns d-flex gap-1">
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit" style="width: 36px;"><i class="fas fa-pencil-alt"></i></a>
                                <?php if ($row['id'] != active_villa_id()): ?>
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $row['status'] === 'Active' ? 'outline-warning' : 'outline-success' ?>" title="<?= $row['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>" style="width: 36px;">
                                        <i class="fas fa-<?= $row['status'] === 'Active' ? 'ban' : 'check' ?>"></i>
                                    </button>
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
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> villas</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
