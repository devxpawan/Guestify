<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
if (!has_role(['Admin'])) {
    header('Location: ../../dashboard.php');
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
    $company_email = mysqli_real_escape_string($conn, $_POST['company_email']);
    $currency_symbol = mysqli_real_escape_string($conn, $_POST['currency_symbol']);

    $update_query = "UPDATE settings SET company_name = '$company_name', company_email = '$company_email', currency_symbol = '$currency_symbol'";
    
    // Ensure root uploads folder exists
    if (!is_dir('../../uploads')) {
        mkdir('../../uploads', 0777, true);
    }

    // Handle Logo Upload
    if (!empty($_FILES['logo']['name'])) {
        $logo_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $safe_company = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $company_name);
        $logo_name = $safe_company . '_logo_' . date('YmdHis') . '.' . $logo_ext;
        $logo_target = '../../uploads/' . $logo_name;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_target)) {
            $update_query .= ", logo_path = '$logo_name'";
        } else {
            $error .= "Failed to upload logo. ";
        }
    }

    // Handle Favicon Upload
    if (!empty($_FILES['favicon']['name'])) {
        $favicon_ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
        $safe_company = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $company_name);
        $favicon_name = $safe_company . '_fav_' . date('YmdHis') . '.' . $favicon_ext;
        $favicon_target = '../../uploads/' . $favicon_name;
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $favicon_target)) {
            $update_query .= ", favicon_path = '$favicon_name'";
        } else {
            $error .= "Failed to upload favicon. ";
        }
    }

    if (empty($error)) {
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = "Branding settings updated successfully! Please refresh to see all changes.";
            header("Location: " . $_SERVER['REQUEST_URI']);
            exit();
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    }
}

$branding_query = mysqli_query($conn, "SELECT * FROM settings LIMIT 1");
$branding = mysqli_fetch_assoc($branding_query);

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header mb-4">
        <h2><i class="bi bi-palette"></i> Branding Settings</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Manage your company details, logo, and currency symbol here.</p>
    </div>



    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-building"></i> Company Information</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($branding['company_name'] ?? 'VillaRS') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($branding['company_email'] ?? 'info@villars.com') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($branding['currency_symbol'] ?? '$') ?>" required>
                        <small class="text-muted">Used for dashboard revenues, room prices, and billing.</small>
                    </div>
                </div>

                <hr>

                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Logo (Sidebar)</label>
                        <?php if (!empty($branding['logo_path'])): ?>
                            <div class="mb-2 d-flex align-items-center" style="height: 85px;">
                                <img src="../../uploads/<?= htmlspecialchars($branding['logo_path']) ?>" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
                            </div>
                        <?php else: ?>
                            <div class="mb-2" style="height: 85px;"></div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <small class="text-muted">Recommended: Transparent PNG, max height 50px.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Favicon (Browser Tab)</label>
                        <?php if (!empty($branding['favicon_path'])): ?>
                            <div class="mb-2 d-flex align-items-center" style="height: 85px;">
                                <img src="../../uploads/<?= htmlspecialchars($branding['favicon_path']) ?>" alt="Favicon" class="img-thumbnail" style="max-height: 40px;">
                            </div>
                        <?php else: ?>
                            <div class="mb-2" style="height: 85px;"></div>
                        <?php endif; ?>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png,image/jpeg">
                        <small class="text-muted">Recommended: Square format (e.g., 32x32) PNG or ICO.</small>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
