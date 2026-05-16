<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist', 'Manager'])) {
    header('Location: ../../index.php');
    exit();
}

$active_stays = mysqli_query($conn, "SELECT r.id, r.room_id, r.check_in, c.full_name, rm.room_number 
                                     FROM reservations r 
                                     JOIN customers c ON r.customer_id = c.id 
                                     JOIN rooms rm ON r.room_id = rm.id 
                                     WHERE r.status = 'Checked-In' 
                                     ORDER BY rm.room_number");

$recent_orders = mysqli_query($conn, "SELECT so.*, p.product_name, c.full_name, rm.room_number 
                                      FROM service_orders so 
                                      JOIN reservations r ON so.reservation_id = r.id 
                                      JOIN customers c ON r.customer_id = c.id 
                                      JOIN rooms rm ON r.room_id = rm.id 
                                      JOIN products p ON so.product_id = p.id 
                                      ORDER BY so.created_at DESC LIMIT 20");

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-cart-plus"></i> Room Service Management</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage guest room service orders and requests</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header"><h5><i class="bi bi-door-open"></i> Active Stays</h5></div>
                <div class="list-group list-group-flush">
                    <?php if (mysqli_num_rows($active_stays) > 0): ?>
                        <?php while ($stay = mysqli_fetch_assoc($active_stays)): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-primary"><i class="bi bi-door-closed me-1"></i> Room <?= $stay['room_number'] ?></strong><br>
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($stay['full_name']) ?></small>
                                </div>
                                <a href="create.php?reservation_id=<?= $stay['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Order
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i>
                            <p class="mt-2 mb-0">No guests currently checked-in.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="table-container">
                <div class="card-header"><h5><i class="bi bi-clock-history"></i> Recent Orders</h5></div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Room</th>
                                <th>Guest</th>
                                <th>Product</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td><small class="text-muted"><?= date('H:i', strtotime($order['created_at'])) ?></small></td>
                                <td><span class="badge badge-secondary">Room <?= $order['room_number'] ?></span></td>
                                <td><?= htmlspecialchars($order['full_name']) ?></td>
                                <td><?= htmlspecialchars($order['product_name']) ?></td>
                                <td><span class="badge badge-info"><?= $order['quantity'] ?></span></td>
                                <td><strong>$<?= number_format($order['quantity'] * $order['price'], 2) ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= $order['status'] == 'Pending' ? 'warning' : ($order['status'] == 'Served' ? 'success' : 'secondary') ?>">
                                        <?= $order['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (mysqli_num_rows($recent_orders) == 0): ?>
                            <tr><td colspan="7" class="text-center p-4 text-muted"><i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i><p class="mt-2 mb-0">No orders yet.</p></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
