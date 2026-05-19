<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager'])) {
    header('Location: ../../dashboard.php');
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$position = isset($_GET['position']) ? mysqli_real_escape_string($conn, $_GET['position']) : '';

$where = [];
if ($search !== '') {
    $where[] = "(name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%')";
}
if ($position !== '') {
    $where[] = "position = '$position'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

$staff = mysqli_query($conn, "SELECT * FROM staff $where_clause ORDER BY id DESC");
$positions_res = mysqli_query($conn, "SELECT DISTINCT position FROM staff ORDER BY position");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-person-badge"></i> Staff Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage team members and positions</p>
            </div>
            <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Staff</a>
        </div>
    </div>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search Name, Email, or Phone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="position" class="form-select">
                        <option value="">All Positions</option>
                        <?php while($p = mysqli_fetch_assoc($positions_res)): ?>
                        <option value="<?= htmlspecialchars($p['position']) ?>" <?= $position == $p['position'] ? 'selected' : '' ?>><?= htmlspecialchars($p['position']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($search || $position): ?>
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
                        <th>ID</th><th>Name</th><th>Position</th><th>Phone</th><th>Email</th><th>Salary</th><th>Actions</th>
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
                        <td><i class="bi bi-envelope text-muted me-1"></i><?= htmlspecialchars($s['email']) ?></td>
                        <td><strong>$<?= number_format($s['salary'], 2) ?></strong></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete"><i class="bi bi-trash"></i></a>
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
