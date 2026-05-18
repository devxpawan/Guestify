<div class="d-flex" id="wrapper">
    <div id="sidebar-wrapper">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <?php if (!empty($global_logo)): ?>
                    <img src="<?= $base_url ?>uploads/<?= htmlspecialchars($global_logo) ?>" alt="Logo">
                <?php endif; ?>
                <span class="sidebar-brand-name "><?= htmlspecialchars($global_company_name) ?></span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-label">Overview</div>
                <a href="<?= $base_url ?>dashboard.php" class="sidebar-link" data-page="dashboard">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <?php if (has_role(['Admin', 'Receptionist'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">Management</div>
                <a href="#roomsMenu" data-bs-toggle="collapse" class="sidebar-link sidebar-link-toggle" aria-expanded="false">
                    <i class="bi bi-building"></i>
                    <span>Rooms</span>
                    <i class="bi bi-chevron-right sidebar-chevron"></i>
                </a>
                <div class="collapse sidebar-submenu" id="roomsMenu">
                    <a href="<?= $base_url ?>modules/rooms/create.php" class="sidebar-sublink" data-page="rooms/create">
                        <span>Add Room</span>
                    </a>
                    <a href="<?= $base_url ?>modules/rooms/index.php" class="sidebar-sublink" data-page="rooms/index">
                        <span>Room List</span>
                    </a>
                </div>

                <a href="#resMenu" data-bs-toggle="collapse" class="sidebar-link sidebar-link-toggle" aria-expanded="false">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reservations</span>
                    <i class="bi bi-chevron-right sidebar-chevron"></i>
                </a>
                <div class="collapse sidebar-submenu" id="resMenu">
                    <a href="<?= $base_url ?>modules/reservations/create.php" class="sidebar-sublink" data-page="reservations/create">
                        <span>New Booking</span>
                    </a>
                    <a href="<?= $base_url ?>modules/reservations/index.php" class="sidebar-sublink" data-page="reservations/index">
                        <span>All Reservations</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="sidebar-section">
                <div class="sidebar-section-label">Operations</div>

                <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
                <a href="<?= $base_url ?>modules/room_service/index.php" class="sidebar-link" data-page="room_service">
                    <i class="bi bi-cart-plus"></i>
                    <span>Room Service</span>
                </a>
                <?php endif; ?>

                <a href="#custMenu" data-bs-toggle="collapse" class="sidebar-link sidebar-link-toggle" aria-expanded="false">
                    <i class="bi bi-people"></i>
                    <span>Customers</span>
                    <i class="bi bi-chevron-right sidebar-chevron"></i>
                </a>
                <div class="collapse sidebar-submenu" id="custMenu">
                    <a href="<?= $base_url ?>modules/customers/create.php" class="sidebar-sublink" data-page="customers/create">
                        <span>Add Customer</span>
                    </a>
                    <a href="<?= $base_url ?>modules/customers/index.php" class="sidebar-sublink" data-page="customers/index">
                        <span>Customer List</span>
                    </a>
                </div>

                <?php if (has_role(['Admin', 'Manager'])): ?>
                <a href="#staffMenu" data-bs-toggle="collapse" class="sidebar-link sidebar-link-toggle" aria-expanded="false">
                    <i class="bi bi-person-badge"></i>
                    <span>Staff</span>
                    <i class="bi bi-chevron-right sidebar-chevron"></i>
                </a>
                <div class="collapse sidebar-submenu" id="staffMenu">
                    <a href="<?= $base_url ?>modules/staff/create.php" class="sidebar-sublink" data-page="staff/create">
                        <span>Add Staff</span>
                    </a>
                    <a href="<?= $base_url ?>modules/staff/index.php" class="sidebar-sublink" data-page="staff/index">
                        <span>Staff List</span>
                    </a>
                </div>
                <?php endif; ?>

                <a href="#prodMenu" data-bs-toggle="collapse" class="sidebar-link sidebar-link-toggle" aria-expanded="false">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                    <i class="bi bi-chevron-right sidebar-chevron"></i>
                </a>
                <div class="collapse sidebar-submenu" id="prodMenu">
                    <a href="<?= $base_url ?>modules/products/create.php" class="sidebar-sublink" data-page="products/create">
                        <span>Add Product</span>
                    </a>
                    <a href="<?= $base_url ?>modules/products/index.php" class="sidebar-sublink" data-page="products/index">
                        <span>Product List</span>
                    </a>
                </div>
            </div>

            <?php if (has_role(['Admin', 'Cashier'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">Finance</div>
                <a href="<?= $base_url ?>modules/billing/index.php" class="sidebar-link" data-page="billing">
                    <i class="bi bi-receipt"></i>
                    <span>Billing</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">Analytics</div>
                <a href="<?= $base_url ?>modules/reports/index.php" class="sidebar-link" data-page="reports">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (has_role(['Admin'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">System</div>
                <a href="<?= $base_url ?>admin/users.php" class="sidebar-link" data-page="users">
                    <i class="bi bi-shield-lock"></i>
                    <span>Users & Roles</span>
                </a>
                <a href="<?= $base_url ?>modules/branding/index.php" class="sidebar-link" data-page="branding">
                    <i class="bi bi-palette"></i>
                    <span>Branding</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>
