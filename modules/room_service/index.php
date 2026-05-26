<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Receptionist', 'Manager'])) {
    header('Location: ../../index.php');
    exit();
}

// Handle edit / cancel POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token();
    $id = (int)$_POST['id'];
    $action = $_POST['action'] ?? '';

    $order = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT so.*, r.status AS reservation_status
        FROM service_orders so
        JOIN reservations r ON so.reservation_id = r.id
        WHERE so.id = $id AND " . active_villa_where('so') . "
    "));

    if ($order && $order['reservation_status'] === 'Checked-In') {
        if ($action === 'cancel') {
            mysqli_query($conn, "UPDATE products SET quantity = quantity + {$order['quantity']} WHERE id = {$order['product_id']} AND " . active_villa_where_raw());
            mysqli_query($conn, "UPDATE service_orders SET status = 'Cancelled' WHERE id = $id AND " . active_villa_where_raw());
            $_SESSION['success'] = 'Order cancelled. Stock restored.';
        } elseif ($action === 'update') {
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            $status = mysqli_real_escape_string($conn, $_POST['status']);

            if ($quantity > 0) {
                $prod = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM products WHERE id=$product_id AND " . active_villa_where_raw()));
                if ($prod) {
                    $old_qty = $order['quantity'];
                    $diff = $quantity - $old_qty;

                    if ($diff > 0 && $prod['quantity'] < $diff) {
                        $_SESSION['error'] = 'Insufficient stock! Only ' . $prod['quantity'] . ' available.';
                    } else {
                        if ($diff != 0) {
                            mysqli_query($conn, "UPDATE products SET quantity = quantity - $diff WHERE id = $product_id AND " . active_villa_where_raw());
                        }
                        $price = $prod['price'];
                        mysqli_query($conn, "UPDATE service_orders SET product_id=$product_id, quantity=$quantity, price=$price, status='$status' WHERE id=$id AND " . active_villa_where_raw());
                        $_SESSION['success'] = 'Order updated successfully!';
                    }
                }
            }
        }
    }

    header('Location: index.php');
    exit();
}

$active_stays = mysqli_query($conn, "SELECT r.id, r.room_id, r.check_in, c.full_name, rm.room_number 
                                     FROM reservations r 
                                     JOIN customers c ON r.customer_id = c.id 
                                     JOIN rooms rm ON r.room_id = rm.id 
                                     WHERE r.status = 'Checked-In' AND " . active_villa_where('r') . "
                                     ORDER BY rm.room_number");

$recent_orders = mysqli_query($conn, "SELECT so.*, p.product_name, c.full_name, rm.room_number, r.status AS reservation_status
                                      FROM service_orders so 
                                      JOIN reservations r ON so.reservation_id = r.id 
                                      JOIN customers c ON r.customer_id = c.id 
                                      JOIN rooms rm ON r.room_id = rm.id 
                                      JOIN products p ON so.product_id = p.id 
                                      WHERE " . active_villa_where('so') . "
                                      ORDER BY so.created_at DESC LIMIT 20");

$products = mysqli_query($conn, "SELECT * FROM products WHERE is_active = 1 AND " . active_villa_where_raw() . " ORDER BY product_name");

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
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recent_orders)):
                                $can_edit = $order['reservation_status'] === 'Checked-In' && $order['status'] !== 'Cancelled';
                            ?>
                            <tr>
                                <td><small class="text-muted"><?= date('H:i', strtotime($order['created_at'])) ?></small></td>
                                <td><span class="badge badge-secondary">Room <?= $order['room_number'] ?></span></td>
                                <td><?= htmlspecialchars($order['full_name']) ?></td>
                                <td><?= htmlspecialchars($order['product_name']) ?></td>
                                <td><span class="badge badge-info"><?= $order['quantity'] ?></span></td>
                                <td><strong><?= htmlspecialchars($global_currency) . number_format($order['quantity'] * $order['price'], 2) ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= $order['status'] == 'Pending' ? 'warning' : ($order['status'] == 'Served' ? 'success' : 'secondary') ?>">
                                        <?= $order['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($can_edit): ?>
                                    <div class="action-btns">
                                        <button type="button" class="btn btn-sm btn-outline-primary" title="Edit"
                                            data-bs-toggle="modal" data-bs-target="#editModal"
                                            data-id="<?= $order['id'] ?>"
                                            data-product="<?= $order['product_id'] ?>"
                                            data-qty="<?= $order['quantity'] ?>"
                                            data-status="<?= $order['status'] ?>">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                        <form method="POST" style="display:inline" onsubmit="return confirm('Cancel this order? Stock will be restored.')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-ban"></i></button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if (mysqli_num_rows($recent_orders) == 0): ?>
                            <tr><td colspan="8" class="text-center p-4 text-muted"><i class="bi bi-inbox" style="font-size: 2rem; opacity: 0.3;"></i><p class="mt-2 mb-0">No orders yet.</p></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Edit Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="action" value="update">
                <div class="mb-3">
                    <label class="form-label">Product</label>
                    <select name="product_id" id="editProduct" class="form-control" required>
                        <?php mysqli_data_seek($products, 0); while ($p = mysqli_fetch_assoc($products)): ?>
                        <option value="<?= $p['id'] ?>">
                            <?= htmlspecialchars($p['product_name']) ?> - <?= htmlspecialchars($global_currency) ?><?= number_format($p['price'], 2) ?> (Stock: <?= $p['quantity'] ?>)
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="editQty" class="form-control" min="1" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="editStatus" class="form-control">
                        <option value="Pending">Pending</option>
                        <option value="Served">Served</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('editModal');
    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('editId').value = btn.dataset.id;
        document.getElementById('editProduct').value = btn.dataset.product;
        document.getElementById('editQty').value = btn.dataset.qty;
        document.getElementById('editStatus').value = btn.dataset.status;
    });
});
</script>

<?php include '../../includes/footer.php'; ?>
