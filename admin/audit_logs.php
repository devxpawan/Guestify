<?php
require_once '../includes/session.php';
require_once '../config/database.php';
require_once '../includes/pagination.php';

if (!has_role(['Admin'])) {
    header('Location: ../dashboard.php');
    exit();
}

// Pagination
$per_page = 20;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Count
$count_res = mysqli_query($conn, "SELECT COUNT(*) AS total FROM audit_logs");
$total_rows = mysqli_fetch_assoc($count_res)['total'];
$total_pages = ceil($total_rows / $per_page);

// Fetch logs with user names
$logs = mysqli_query($conn, "
    SELECT a.*, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.timestamp DESC 
    LIMIT $offset, $per_page
");

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header mb-4">
        <h2><i class="bi bi-journal-text"></i> System Audit Logs</h2>
        <p class="text-muted">Review system activity history</p>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td class="text-nowrap"><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                        <td><?= htmlspecialchars($log['username'] ?? 'System') ?></td>
                        <td><span class="badge bg-info"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" onclick="viewDetails(<?= htmlspecialchars(json_encode($log)) ?>)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing <?= min($total_rows, $offset + 1) ?>-<?= min($total_rows, $offset + $per_page) ?> of <?= $total_rows ?> logs</small>
            <?php render_pagination($page, $total_pages); ?>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="diffTable"></div>
            </div>
        </div>
    </div>
</div>

<script>
function formatLabel(key) {
    const labels = {
        'username': 'Username',
        'role_id': 'Role',
        'status': 'Status',
        'password': 'Password'
    };
    return labels[key] || key.charAt(0).toUpperCase() + key.slice(1).replace('_', ' ');
}

function formatValue(key, value) {
    if (value === null || value === undefined) return '<span class="text-muted">null</span>';
    if (key === 'status') return value == 1 ? 'Active' : 'Inactive';
    return value;
}

function viewDetails(log) {
    const oldValues = JSON.parse(log.old_value || '{}');
    const newValues = JSON.parse(log.new_value || '{}');
    
    // Combine all keys
    const allKeys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);
    
    let html = '<table class="table table-sm table-bordered">';
    html += '<thead class="table-light"><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead>';
    html += '<tbody>';
    
    allKeys.forEach(key => {
        const oldVal = oldValues[key];
        const newVal = newValues[key];
        const changed = oldVal !== newVal;
        
        html += `<tr class="${changed ? 'table-warning' : ''}">
                    <td><strong>${formatLabel(key)}</strong></td>
                    <td>${formatValue(key, oldVal)}</td>
                    <td>${formatValue(key, newVal)}</td>
                 </tr>`;
    });
    
    html += '</tbody></table>';
    document.getElementById('diffTable').innerHTML = html;
    
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}
</script>
<?php include '../includes/footer.php'; ?>
