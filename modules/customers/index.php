<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
include '../../includes/header.php';
include '../../includes/sidebar.php';

$search = '';
$where = '';
if (isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $where = "WHERE full_name LIKE '%$search%' OR nic_passport LIKE '%$search%' OR phone LIKE '%$search%'";
}
$customers = mysqli_query($conn, "SELECT * FROM customers $where ORDER BY id DESC");
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
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search by name, NIC, or phone..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-search"></i> Search</button>
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
                        <th>ID</th><th>Full Name</th><th>NIC/Passport</th><th>Phone</th><th>Email</th><th>Address</th><th>Actions</th>
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
                        <td><i class="bi bi-geo-alt text-muted me-1"></i><?= htmlspecialchars($c['address']) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="delete.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete"><i class="bi bi-trash"></i></a>
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
