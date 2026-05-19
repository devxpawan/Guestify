<?php
$branding_query = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
$branding = mysqli_fetch_assoc($branding_query);

$global_company_name = $branding['company_name'] ?? 'VillaRS';
$global_currency = $branding['currency_symbol'] ?? '$';
$global_logo = $branding['logo_path'] ?? '';
$global_favicon = $branding['favicon_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($global_company_name) ?> - Reservation System</title>
    <?php if ($global_favicon): ?>
    <link rel="icon" href="<?= $base_url ?>uploads/<?= htmlspecialchars($global_favicon) ?>">
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/style.css">
</head>
<body>
<?php if (isset($_SESSION['user_id'])): ?>
<nav class="navbar navbar-expand-lg top-navbar">
    <div class="container-fluid">
        <button class="btn" id="menu-toggle"><i class="bi bi-list fs-5"></i></button>
        <div class="ms-auto">
            <div class="dropdown">
                <button class="btn dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-person"></i>
                    </div>
                    <div class="text-start">
                        <div class="fw-semibold" style="font-size: 0.85rem;"><?= htmlspecialchars($_SESSION['username']) ?></div>
                    </div>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="<?= $base_url ?>modules/profile/index.php"><i class="bi bi-person-gear me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= $base_url ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>
