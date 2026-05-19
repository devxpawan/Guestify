<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/pagination.php';

if (!has_role(['Admin', 'Manager', 'Cashier'])) {
    header('Location: ../../dashboard.php');
    exit();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';

$type_filter = isset($_GET['type']) ? mysqli_real_escape_string($conn, $_GET['type']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';

// Summary
$summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COALESCE(SUM(CASE WHEN type='Income' THEN amount ELSE 0 END), 0) AS total_income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END), 0) AS total_expense
    FROM transactions
"));

$profit = $summary['total_income'] - $summary['total_expense'];

// Current month
$month_summary = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT
        COALESCE(SUM(CASE WHEN type='Income' THEN amount ELSE 0 END), 0) AS income,
        COALESCE(SUM(CASE WHEN type='Expense' THEN amount ELSE 0 END), 0) AS expense
    FROM transactions
    WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE())
    AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
"));

// Where for list
$where = [];
if ($type_filter !== '') {
    $where[] = "t.type = '$type_filter'";
}
if ($category_filter > 0) {
    $where[] = "t.category_id = $category_filter";
}
if ($date_from !== '') {
    $where[] = "t.transaction_date >= '$date_from'";
}
if ($date_to !== '') {
    $where[] = "t.transaction_date <= '$date_to'";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

// Pagination
$per_page = 15;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM transactions t $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$transactions = mysqli_query($conn, "
    SELECT t.*, fc.name AS category_name, u.username AS created_by_name
    FROM transactions t
    LEFT JOIN finance_categories fc ON t.category_id = fc.id
    LEFT JOIN users u ON t.created_by = u.id
    $where_clause
    ORDER BY t.transaction_date DESC, t.id DESC
    LIMIT $offset, $per_page
");

$categories = mysqli_query($conn, "SELECT * FROM finance_categories ORDER BY type, name");
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-wallet2"></i> Income & Expenses</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Track villa income and expenses</p>
            </div>
            <div class="d-flex gap-2">
                <a href="categories.php" class="btn btn-outline-secondary"><i class="bi bi-tags"></i> Categories</a>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Transaction</a>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Total Income</p>
                            <h4 class="mb-0 text-success"><?= htmlspecialchars($global_currency) ?><?= number_format($summary['total_income'], 2) ?></h4>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-arrow-down-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Total Expenses</p>
                            <h4 class="mb-0 text-danger"><?= htmlspecialchars($global_currency) ?><?= number_format($summary['total_expense'], 2) ?></h4>
                        </div>
                        <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-arrow-up-circle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">Net Profit / Loss</p>
                            <h4 class="mb-0 <?= $profit >= 0 ? 'text-primary' : 'text-danger' ?>"><?= htmlspecialchars($global_currency) ?><?= number_format($profit, 2) ?></h4>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1" style="font-size: 0.8rem;">This Month Net</p>
                            <h4 class="mb-0 <?= ($month_summary['income'] - $month_summary['expense']) >= 0 ? 'text-primary' : 'text-danger' ?>">
                                <?= htmlspecialchars($global_currency) ?><?= number_format($month_summary['income'] - $month_summary['expense'], 2) ?>
                            </h4>
                        </div>
                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-calendar-check fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-2">
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="Income" <?= $type_filter === 'Income' ? 'selected' : '' ?>>Income</option>
                        <option value="Expense" <?= $type_filter === 'Expense' ? 'selected' : '' ?>>Expense</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="0">All Categories</option>
                        <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?> (<?= $cat['type'] ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>" placeholder="To">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($type_filter || $category_filter || $date_from || $date_to): ?>
                    <a href="index.php" class="btn btn-outline-secondary" title="Clear Filters"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th><th>Type</th><th>Category</th><th>Description</th><th>Amount</th><th>Recorded By</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($transactions) === 0): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No transactions found</td></tr>
                    <?php endif; ?>
                    <?php while ($t = mysqli_fetch_assoc($transactions)): ?>
                    <tr>
                        <td><?= date('d M Y', strtotime($t['transaction_date'])) ?></td>
                        <td>
                            <span class="badge badge-<?= $t['type'] === 'Income' ? 'success' : 'danger' ?>">
                                <i class="bi bi-<?= $t['type'] === 'Income' ? 'arrow-down' : 'arrow-up' ?>"></i> <?= $t['type'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($t['category_name']) ?></td>
                        <td><?= htmlspecialchars($t['description']) ?></td>
                        <td>
                            <strong class="<?= $t['type'] === 'Income' ? 'text-success' : 'text-danger' ?>">
                                <?= $t['type'] === 'Income' ? '+' : '-' ?><?= htmlspecialchars($global_currency) ?><?= number_format($t['amount'], 2) ?>
                            </strong>
                        </td>
                        <td><small class="text-muted"><?= htmlspecialchars($t['created_by_name'] ?? 'N/A') ?></small></td>
                        <td>
                            <div class="action-btns">
                                <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                                <form method="POST" action="delete.php" style="display:inline" onsubmit="return confirm('Delete this transaction?')">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> transactions</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
