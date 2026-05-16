<div class="d-flex" id="wrapper">
    <div id="sidebar-wrapper">
        <div class="sidebar-heading">
            <i class="bi bi-house-heart-fill"></i> VillaRS
        </div>
        <div class="list-group list-group-flush">
            <div class="sidebar-label">Main</div>
            <a href="<?= $base_url ?>dashboard.php" class="list-group-item list-group-item-action">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>

            <?php if (has_role(['Admin', 'Receptionist'])): ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-label">Management</div>
            <a href="#roomSubmenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action" aria-expanded="false">
                <i class="bi bi-building"></i> Rooms
                <i class="bi bi-chevron-down"></i>
            </a>
            <div class="collapse" id="roomSubmenu">
                <a href="<?= $base_url ?>modules/rooms/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-dot"></i> All Rooms
                </a>
                <?php if (has_role(['Admin'])): ?>
                <a href="<?= $base_url ?>modules/rooms/types.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-dot"></i> Room Types
                </a>
                <?php endif; ?>
            </div>

            <a href="#resSubmenu" data-bs-toggle="collapse" class="list-group-item list-group-item-action" aria-expanded="false">
                <i class="bi bi-calendar-check"></i> Reservations
                <i class="bi bi-chevron-down"></i>
            </a>
            <div class="collapse" id="resSubmenu">
                <a href="<?= $base_url ?>modules/reservations/index.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-dot"></i> All Reservations
                </a>
                <a href="<?= $base_url ?>modules/reservations/calendar.php" class="list-group-item list-group-item-action">
                    <i class="bi bi-dot"></i> Calendar
                </a>
            </div>
            <?php endif; ?>

            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
            <a href="<?= $base_url ?>modules/room_service/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-cart-plus"></i> Room Service
            </a>
            <?php endif; ?>


            <a href="<?= $base_url ?>modules/customers/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-people"></i> Customers
            </a>

            <?php if (has_role(['Admin', 'Manager'])): ?>
            <a href="<?= $base_url ?>modules/staff/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person-badge"></i> Staff
            </a>
            <?php endif; ?>

            <a href="<?= $base_url ?>modules/products/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-box-seam"></i> Products
            </a>

            <?php if (has_role(['Admin', 'Cashier'])): ?>
            <a href="<?= $base_url ?>modules/billing/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-receipt"></i> Billing
            </a>
            <?php endif; ?>

            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
            <a href="<?= $base_url ?>modules/reports/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-graph-up"></i> Reports
            </a>
            <?php endif; ?>

            <?php if (has_role(['Admin'])): ?>
            <div class="sidebar-divider"></div>
            <div class="sidebar-label">System</div>
            <a href="<?= $base_url ?>admin/users.php" class="list-group-item list-group-item-action">
                <i class="bi bi-gear"></i> Users
            </a>
            <?php endif; ?>

            <a href="<?= $base_url ?>modules/profile/index.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person-circle"></i> Profile
            </a>
        </div>
    </div>
