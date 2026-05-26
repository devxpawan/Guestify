<div class="d-flex" id="wrapper">
    <div id="sidebar-wrapper">
        <!-- Brand -->
        <div class="sidebar-brand">
            <div class="sidebar-brand-logo">
                <?php if (!empty($global_logo)): ?>
                    <img src="<?= $base_url ?>uploads/<?= htmlspecialchars($global_logo) ?>" alt="Logo">
                <?php else: ?>
                    <img src="<?= $base_url ?>assets/images/logo.png" alt="Logo" style="max-height: 40px;">
                    <span class="sidebar-brand-name "><?= htmlspecialchars($global_company_name) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav">
            <div class="sidebar-section">
                <div class="sidebar-section-label">Overview</div>
                <a href="<?= $base_url ?>dashboard.php" class="sidebar-link <?= is_active_link('/dashboard.php') ? 'active' : '' ?>" data-page="dashboard">
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
                    <a href="<?= $base_url ?>modules/rooms/create.php" class="sidebar-sublink <?= is_active_link('/modules/rooms/create.php') ? 'active' : '' ?>" data-page="rooms/create">
                        <span>Add Room</span>
                    </a>
                    <a href="<?= $base_url ?>modules/rooms/index.php" class="sidebar-sublink <?= is_active_link(['/modules/rooms/index.php', '/modules/rooms/edit.php', '/modules/rooms/types.php']) ? 'active' : '' ?>" data-page="rooms/index">
                        <span>Room List</span>
                    </a>
                </div>

                <a href="#resMenu" data-bs-toggle="collapse" class="sidebar-link sidebar-link-toggle" aria-expanded="false">
                    <i class="bi bi-calendar-check"></i>
                    <span>Reservations</span>
                    <i class="bi bi-chevron-right sidebar-chevron"></i>
                </a>
                <div class="collapse sidebar-submenu" id="resMenu">
                    <a href="<?= $base_url ?>modules/reservations/create.php" class="sidebar-sublink <?= is_active_link('/modules/reservations/create.php') ? 'active' : '' ?>" data-page="reservations/create">
                        <span>New Booking</span>
                    </a>
                    <a href="<?= $base_url ?>modules/reservations/index.php" class="sidebar-sublink <?= is_active_link(['/modules/reservations/index.php', '/modules/reservations/edit.php', '/modules/reservations/status.php']) ? 'active' : '' ?>" data-page="reservations/index">
                        <span>All Reservations</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="sidebar-section">
                <div class="sidebar-section-label">Operations</div>

                <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
                <a href="<?= $base_url ?>modules/room_service/index.php" class="sidebar-link <?= is_active_link(['/modules/room_service/index.php', '/modules/room_service/create.php']) ? 'active' : '' ?>" data-page="room_service">
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
                    <a href="<?= $base_url ?>modules/customers/create.php" class="sidebar-sublink <?= is_active_link('/modules/customers/create.php') ? 'active' : '' ?>" data-page="customers/create">
                        <span>Add Customer</span>
                    </a>
                    <a href="<?= $base_url ?>modules/customers/index.php" class="sidebar-sublink <?= is_active_link(['/modules/customers/index.php', '/modules/customers/edit.php']) ? 'active' : '' ?>" data-page="customers/index">
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
                    <a href="<?= $base_url ?>modules/staff/create.php" class="sidebar-sublink <?= is_active_link('/modules/staff/create.php') ? 'active' : '' ?>" data-page="staff/create">
                        <span>Add Staff</span>
                    </a>
                    <a href="<?= $base_url ?>modules/staff/index.php" class="sidebar-sublink <?= is_active_link(['/modules/staff/index.php', '/modules/staff/edit.php']) ? 'active' : '' ?>" data-page="staff/index">
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
                    <a href="<?= $base_url ?>modules/products/create.php" class="sidebar-sublink <?= is_active_link('/modules/products/create.php') ? 'active' : '' ?>" data-page="products/create">
                        <span>Add Product</span>
                    </a>
                    <a href="<?= $base_url ?>modules/products/index.php" class="sidebar-sublink <?= is_active_link(['/modules/products/index.php', '/modules/products/edit.php']) ? 'active' : '' ?>" data-page="products/index">
                        <span>Product List</span>
                    </a>
                </div>
            </div>

            <?php if (has_role(['Admin', 'Cashier', 'Manager'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">Finance</div>
                <a href="<?= $base_url ?>modules/finance/index.php" class="sidebar-link <?= is_active_link(['/modules/finance/index.php', '/modules/finance/create.php', '/modules/finance/edit.php', '/modules/finance/categories.php']) ? 'active' : '' ?>" data-page="finance">
                    <i class="bi bi-wallet2"></i>
                    <span>Income & Expenses</span>
                </a>
                <?php if (has_role(['Admin', 'Cashier'])): ?>
                <a href="<?= $base_url ?>modules/billing/index.php" class="sidebar-link <?= is_active_link(['/modules/billing/index.php', '/modules/billing/payments.php', '/modules/billing/view.php', '/modules/billing/print.php']) ? 'active' : '' ?>" data-page="billing">
                    <i class="bi bi-receipt"></i>
                    <span>Billing</span>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (has_role(['Admin', 'Manager', 'Receptionist'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">Analytics</div>
                <a href="<?= $base_url ?>modules/reports/index.php" class="sidebar-link <?= is_active_link('/modules/reports/index.php') ? 'active' : '' ?>" data-page="reports">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                </a>
            </div>
            <?php endif; ?>

            <?php if (has_role(['Admin'])): ?>
            <div class="sidebar-section">
                <div class="sidebar-section-label">System</div>
                <a href="<?= $base_url ?>modules/villas/index.php" class="sidebar-link <?= is_active_link('/modules/villas/') ? 'active' : '' ?>" data-page="villas">
                    <i class="bi bi-building-gear"></i>
                    <span>Villas</span>
                </a>
                <a href="<?= $base_url ?>admin/users.php" class="sidebar-link <?= is_active_link(['/admin/users.php', '/admin/create.php']) ? 'active' : '' ?>" data-page="users">
                    <i class="bi bi-shield-lock"></i>
                    <span>Users & Roles</span>
                </a>
                <a href="<?= $base_url ?>admin/audit_logs.php" class="sidebar-link <?= is_active_link('/admin/audit_logs.php') ? 'active' : '' ?>" data-page="audit_logs">
                    <i class="bi bi-journal-text"></i>
                    <span>Audit Logs</span>
                </a>
            </div>
            <?php endif; ?>
        </nav>
    </div>
    <div id="sidebar-overlay" class="sidebar-overlay"></div>
