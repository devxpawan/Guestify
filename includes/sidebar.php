<div class="d-flex" id="wrapper">
    <div class="bg-dark text-white sidebar" id="sidebar-wrapper">
        <div class="sidebar-heading text-center py-4 fw-bold">Villa RS</div>
        <div class="list-group list-group-flush">
            <a href="<?= $base_url ?>dashboard.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <?php if (has_role(['Admin', 'Receptionist'])): ?>
            <a href="<?= $base_url ?>modules/rooms/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-building"></i> Rooms
            </a>
            <a href="<?= $base_url ?>modules/reservations/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-calendar-check"></i> Reservations
            </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>modules/customers/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-people"></i> Customers
            </a>
            <?php if (has_role(['Admin', 'Manager'])): ?>
            <a href="<?= $base_url ?>modules/staff/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-person-badge"></i> Staff
            </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>modules/products/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-box-seam"></i> Products
            </a>
            <?php if (has_role(['Admin', 'Cashier'])): ?>
            <a href="<?= $base_url ?>modules/billing/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-receipt"></i> Billing
            </a>
            <?php endif; ?>
            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
            <a href="<?= $base_url ?>modules/reports/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <?php endif; ?>
            <?php if (has_role(['Admin'])): ?>
            <a href="<?= $base_url ?>admin/users.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-gear"></i> Users
            </a>
            <?php endif; ?>
            <a href="<?= $base_url ?>modules/profile/index.php" class="list-group-item list-group-item-action bg-dark text-white">
                <i class="bi bi-person-circle"></i> Profile
            </a>
            <a href="<?= $base_url ?>logout.php" class="list-group-item list-group-item-action bg-danger text-white">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>
