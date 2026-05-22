<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/pagination.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}

$date_from = isset($_GET['date_from']) ? mysqli_real_escape_string($conn, $_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? mysqli_real_escape_string($conn, $_GET['date_to']) : '';
$filter_module = isset($_GET['module']) ? mysqli_real_escape_string($conn, $_GET['module']) : '';
$filter_action = isset($_GET['action']) ? mysqli_real_escape_string($conn, $_GET['action']) : '';
$filter_user = isset($_GET['user']) ? mysqli_real_escape_string($conn, $_GET['user']) : '';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

$where = [active_villa_where_raw()];
if ($date_from) {
    $where[] = "DATE(created_at) >= '$date_from'";
}
if ($date_to) {
    $where[] = "DATE(created_at) <= '$date_to'";
}
if ($filter_module) {
    $where[] = "module = '$filter_module'";
}
if ($filter_action) {
    $where[] = "action = '$filter_action'";
}
if ($filter_user) {
    $where[] = "user_id = " . (int)$filter_user;
}
if ($search) {
    $where[] = "(description LIKE '%$search%' OR username LIKE '%$search%')";
}
$where_clause = count($where) > 0 ? " WHERE " . implode(" AND ", $where) : "";

$per_page = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM audit_logs $where_clause");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

$logs = mysqli_query($conn, "SELECT * FROM audit_logs $where_clause ORDER BY created_at DESC LIMIT $offset, $per_page");
$users = mysqli_query($conn, "SELECT DISTINCT user_id, username FROM audit_logs WHERE " . active_villa_where_raw() . " ORDER BY username");

$modules = ['auth', 'reservations', 'rooms', 'customers', 'staff', 'finance', 'users', 'products', 'billing', 'room_service'];
$actions = ['CREATE', 'UPDATE', 'DELETE'];

function getActionBadge($action) {
    switch ($action) {
        case 'CREATE': return 'badge-success';
        case 'UPDATE': return 'badge-info';
        case 'DELETE': return 'badge-danger';
        default: return 'badge-secondary';
    }
}

function getModuleLabel($module) {
    $labels = [
        'auth' => 'Authentication',
        'reservations' => 'Reservations',
        'rooms' => 'Rooms',
        'customers' => 'Customers',
        'staff' => 'Staff',
        'finance' => 'Resources',
        'users' => 'Users',
        'products' => 'Products',
        'billing' => 'Billing',
        'room_service' => 'Room Service'
    ];
    return $labels[$module] ?? ucfirst($module);
}

function resolveForeignKeys($json, $module) {
    if (!$json) return $json;
    $data = json_decode($json, true);
    if (!$data) return $json;
    global $conn;

    if (isset($data['customer_id']) && $data['customer_id']) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name FROM customers WHERE id=" . (int)$data['customer_id']));
        if ($r) $data['customer_id'] = $r['full_name'];
    }
    if (isset($data['room_id']) && $data['room_id']) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT room_number FROM rooms WHERE id=" . (int)$data['room_id']));
        if ($r) $data['room_id'] = 'Room ' . $r['room_number'];
    }
    if (isset($data['room_type_id']) && $data['room_type_id']) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT type_name FROM room_types WHERE id=" . (int)$data['room_type_id']));
        if ($r) $data['room_type_id'] = $r['type_name'];
    }
    if (isset($data['role_id']) && $data['role_id']) {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role_name FROM user_roles WHERE id=" . (int)$data['role_id']));
        if ($r) $data['role_id'] = $r['role_name'];
    }
    if (isset($data['category_id']) && $data['category_id']) {
        if ($module === 'finance') {
            $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT name FROM finance_categories WHERE id=" . (int)$data['category_id']));
        } else {
            $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT category_name FROM product_categories WHERE id=" . (int)$data['category_id']));
        }
        if ($r) $data['category_id'] = $r['name'] ?? $r['category_name'];
    }
    if (isset($data['user_id']) && $data['user_id'] && $module === 'users') {
        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT username FROM users WHERE id=" . (int)$data['user_id']));
        if ($r) $data['user_id'] = $r['username'];
    }

    $statusMap = [0 => 'Inactive', 1 => 'Active'];
    if (isset($data['status']) && isset($statusMap[$data['status']])) {
        $data['status'] = $statusMap[$data['status']];
    }

    return json_encode($data);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-shield-lock"></i> Audit Logs</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">System activity tracking and history</p>
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
                <div class="col-md-2">
                    <label class="form-label small text-muted">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">All Modules</option>
                        <?php foreach ($modules as $m): ?>
                        <option value="<?= $m ?>" <?= $filter_module === $m ? 'selected' : '' ?>><?= getModuleLabel($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Action</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?= $a ?>" <?= $filter_action === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">User</label>
                    <select name="user" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <option value="<?= $u['user_id'] ?>" <?= $filter_user == $u['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Description..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-12 d-flex gap-2 mt-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <?php if ($date_from || $date_to || $filter_module || $filter_action || $filter_user || $search): ?>
                    <a href="audit_logs.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = mysqli_fetch_assoc($logs)):
                        $log['old_values'] = resolveForeignKeys($log['old_values'], $log['module']);
                        $log['new_values'] = resolveForeignKeys($log['new_values'], $log['module']);
                    ?>
                    <tr>
                        <td><small><?= $log['id'] ?></small></td>
                        <td><small><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></small></td>
                        <td><span class="fw-semibold"><?= htmlspecialchars($log['username']) ?></span></td>
                        <td><span class="badge badge-purple"><?= getModuleLabel($log['module']) ?></span></td>
                        <td><span class="badge <?= getActionBadge($log['action']) ?>"><?= $log['action'] ?></span></td>
                        <td>
                            <small><?= htmlspecialchars($log['description']) ?></small>
                            <?php
                            $old_vals = $log['old_values'] ? json_decode($log['old_values'], true) : null;
                            $new_vals = $log['new_values'] ? json_decode($log['new_values'], true) : null;
                            if ($new_vals && !$old_vals):
                            ?>
                            <div class="mt-2 small">
                                <span class="text-success fw-semibold"><i class="bi bi-plus-circle"></i> Created:</span>
                                <?php
                                $parts = [];
                                foreach ($new_vals as $key => $value) {
                                    $val = ($value === null || $value === '') ? 'null' : $value;
                                    $parts[] = getLabel($key) . ' = ' . htmlspecialchars($val);
                                }
                                echo implode(', ', $parts);
                                ?>
                            </div>
                            <?php elseif ($old_vals && !$new_vals): ?>
                            <div class="mt-2 small">
                                <span class="text-danger fw-semibold"><i class="bi bi-trash"></i> Deleted:</span>
                                <?php
                                $parts = [];
                                foreach ($old_vals as $key => $value) {
                                    $val = ($value === null || $value === '') ? 'null' : $value;
                                    $parts[] = getLabel($key) . ' = ' . htmlspecialchars($val);
                                }
                                echo implode(', ', $parts);
                                ?>
                            </div>
                            <?php elseif ($old_vals && $new_vals):
                                $changes = [];
                                $all_keys = array_unique(array_merge(array_keys($old_vals), array_keys($new_vals)));
                                foreach ($all_keys as $key) {
                                    $old_val = isset($old_vals[$key]) && $old_vals[$key] !== '' ? $old_vals[$key] : null;
                                    $new_val = isset($new_vals[$key]) && $new_vals[$key] !== '' ? $new_vals[$key] : null;
                                    $is_changed = true;
                                    if ($old_val === null && $new_val === null) {
                                        $is_changed = false;
                                    } elseif (is_numeric($old_val) && is_numeric($new_val)) {
                                        $is_changed = (float)$old_val !== (float)$new_val;
                                    } else {
                                        $is_changed = trim((string)$old_val) !== trim((string)$new_val);
                                    }
                                    if ($is_changed) {
                                        $changes[$key] = ['old' => $old_val, 'new' => $new_val];
                                    }
                                }
                                if (!empty($changes)):
                            ?>
                            <div class="mt-2 small">
                                <span class="text-primary fw-semibold">Changed:</span>
                                <?php
                                $change_parts = [];
                                foreach ($changes as $key => $vals) {
                                    $old_display = $vals['old'] === null ? 'null' : htmlspecialchars($vals['old']);
                                    $new_display = $vals['new'] === null ? 'null' : htmlspecialchars($vals['new']);
                                    $change_parts[] = getLabel($key) . ': <span class="text-danger">' . $old_display . '</span> → <span class="text-success">' . $new_display . '</span>';
                                }
                                echo implode('; ', $change_parts);
                                ?>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (mysqli_num_rows($logs) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No audit logs found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3">
        <small class="text-muted">Showing <?= $total_rows > 0 ? min($total_rows, $offset + 1) : 0 ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> logs</small>
        <?php render_pagination($page, $total_pages); ?>
    </div>
</div>

<?php
function getLabel($key) {
    $labels = [
        'id' => 'ID',
        'user_id' => 'User ID',
        'username' => 'Username',
        'password' => 'Password',
        'role_id' => 'Role',
        'status' => 'Status',
        'created_at' => 'Created At',
        'full_name' => 'Full Name',
        'nic_passport' => 'NIC / Passport',
        'phone' => 'Phone',
        'email' => 'Email',
        'address' => 'Address',
        'room_number' => 'Room Number',
        'room_type_id' => 'Room Type',
        'capacity' => 'Capacity',
        'price' => 'Price',
        'price_day' => 'Price (Day)',
        'price_night' => 'Price (Night)',
        'price_short' => 'Price (Short)',
        'description' => 'Description',
        'image' => 'Image',
        'booking_type' => 'Booking Type',
        'check_in' => 'Check-In',
        'check_out' => 'Check-Out',
        'adults' => 'Adults',
        'children' => 'Children',
        'customer_id' => 'Customer',
        'room_id' => 'Room',
        'name' => 'Name',
        'position' => 'Position',
        'nic' => 'NIC Number',
        'salary' => 'Salary',
        'type' => 'Type',
        'category_id' => 'Category',
        'amount' => 'Amount',
        'transaction_date' => 'Date',
        'product_name' => 'Product Name',
        'category' => 'Category',
        'quantity' => 'Quantity'
    ];
    return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
}
?>

<?php include '../includes/footer.php'; ?>
