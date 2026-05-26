<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verify_csrf_token();
    $id = (int)$_POST['id'];
    $item = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_active FROM customers WHERE id=$id AND " . active_villa_where()));
    if ($item) {
        $old_is_active = $item['is_active'];
        $new_is_active = $item['is_active'] ? 0 : 1;
        mysqli_query($conn, "UPDATE customers SET is_active=$new_is_active WHERE id=$id AND " . active_villa_where());
        $_SESSION['success'] = 'Customer status updated successfully!';
    }
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = '';
$is_active_filter = isset($_GET['is_active']) ? mysqli_real_escape_string($conn, $_GET['is_active']) : '';
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

$where_arr = [];
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where_arr[] = "(full_name LIKE '%$search%' OR nic_passport LIKE '%$search%' OR phone LIKE '%$search%')";
}
if ($is_active_filter !== '') {
    $where_arr[] = "is_active = " . (int)$is_active_filter;
}
if ($date_from !== '') {
    $where_arr[] = "DATE(created_at) >= '$date_from'";
}
if ($date_to !== '') {
    $where_arr[] = "DATE(created_at) <= '$date_to'";
}
$where_arr[] = active_villa_where();
$where = count($where_arr) > 0 ? "WHERE " . implode(" AND ", $where_arr) : "";

// Pagination
$per_page = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers $where");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$customers = mysqli_query($conn, "SELECT * FROM customers $where ORDER BY id DESC LIMIT $offset, $per_page");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-people"></i> Customer Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage guest information and details</p>
            </div>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Customer</a>
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
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, NIC, or phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="is_active" class="form-select form-select-sm">
                        <option value="">All States</option>
                        <option value="1" <?= $is_active_filter === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $is_active_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-search"></i> Filter</button>
                    <?php if ($search || $is_active_filter !== '' || $date_from || $date_to): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
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
                        <th>ID</th><th>Full Name</th><th>NIC/Passport</th><th>Phone</th><th>Email</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($c = mysqli_fetch_assoc($customers)): ?>
                    <tr>
                        <td><strong>#<?= $c['id'] ?></strong></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                    <i class="bi bi-person"></i>
                                </div>
                                <?= htmlspecialchars($c['full_name']) ?>
                            </div>
                        </td>
                        <td><code><?= htmlspecialchars($c['nic_passport']) ?></code></td>
                        <td><i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($c['phone']) ?></td>
                        <td><i class="bi bi-envelope text-muted me-1"></i><?= htmlspecialchars($c['email']) ?></td>
                        <td>
                            <span class="badge badge-<?= $c['is_active'] ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $c['is_active'] ? 'check-circle' : 'x-circle' ?>"></i> <?= $c['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <form method="POST" style="display:inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-<?= $c['is_active'] ? 'outline-warning' : 'outline-success' ?>" title="<?= $c['is_active'] ? 'Deactivate' : 'Activate' ?>" style="width: 36px;">
                                        <i class="fas fa-<?= $c['is_active'] ? 'ban' : 'check' ?>"></i>
                                    </button>
                                </form>
                                <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit" style="width: 36px;"><i class="fas fa-pencil-alt"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> customers</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>