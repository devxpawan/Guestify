<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../../dashboard.php');
    exit();
}

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $company_email = mysqli_real_escape_string($conn, trim($_POST['company_email']));
    $currency_symbol = mysqli_real_escape_string($conn, trim($_POST['currency_symbol']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));

    if (empty($name)) {
        $_SESSION['error'] = 'Villa name is required.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if (!empty($company_email) && !str_contains($company_email, '@')) {
        $_SESSION['error'] = 'Company email must contain @.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    if (!empty($phone) && (!ctype_digit($phone) || strlen($phone) !== 10)) {
        $_SESSION['error'] = 'Phone must be exactly 10 digits.';
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }

    $query = "INSERT INTO villas (name, company_email, currency_symbol, address, phone, status)
              VALUES ('$name', '$company_email', '$currency_symbol', '$address', '$phone', 'Active')";
    if (mysqli_query($conn, $query)) {
        $villa_id = mysqli_insert_id($conn);

        // Assign the creating admin to this villa
        $uid = (int)$_SESSION['user_id'];
        mysqli_query($conn, "INSERT INTO user_villas (user_id, villa_id, is_default) VALUES ($uid, $villa_id, 0)");

        // Handle Logo Upload
        if (!empty($_FILES['logo']['name'])) {
            if (!is_dir('../../uploads')) {
                mkdir('../../uploads', 0777, true);
            }
            $logo_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
            $logo_name = $safe_name . '_logo_' . date('YmdHis') . '.' . $logo_ext;
            $logo_target = '../../uploads/' . $logo_name;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $logo_target)) {
                mysqli_query($conn, "UPDATE villas SET logo_path = '$logo_name' WHERE id = $villa_id");
            }
        }

        // Handle Favicon Upload
        if (!empty($_FILES['favicon']['name'])) {
            if (!is_dir('../../uploads')) {
                mkdir('../../uploads', 0777, true);
            }
            $favicon_ext = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
            $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
            $favicon_name = $safe_name . '_fav_' . date('YmdHis') . '.' . $favicon_ext;
            $favicon_target = '../../uploads/' . $favicon_name;
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $favicon_target)) {
                mysqli_query($conn, "UPDATE villas SET favicon_path = '$favicon_name' WHERE id = $villa_id");
            }
        }

        $_SESSION['success'] = "Villa '$name' created successfully!";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = 'Database error: ' . mysqli_error($conn);
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header mb-4">
        <h2><i class="bi bi-plus-lg"></i> Add New Villa</h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Create a new property in the system</p>
    </div>


    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-building"></i> Villa Information</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Villa Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($_POST['company_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($_POST['currency_symbol'] ?? '$') ?>" oninput="this.value = this.value.replace(/\s/g, '')">
                        <small class="text-muted">Used for dashboard revenues, room prices, and billing.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" maxlength="10" oninput="this.value = this.value.replace(/\D/g, '')">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                </div>

                <hr>

                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Logo (Sidebar)</label>
                        <div class="mb-2 d-flex align-items-center" style="height: 85px;">
                            <img src="../../assets/images/logo.png" alt="Fallback Logo" class="img-thumbnail" style="max-height: 80px; opacity: 0.6;">
                            <small class="text-muted ms-2">Default Logo</small>
                        </div>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                        <small class="text-muted">Recommended: Transparent PNG, max height 50px.</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Favicon (Browser Tab)</label>
                        <div class="mb-2 d-flex align-items-center" style="height: 85px;">
                            <img src="../../assets/images/favicon.png" alt="Fallback Favicon" class="img-thumbnail" style="max-height: 40px; opacity: 0.6;">
                            <small class="text-muted ms-2">Default Favicon</small>
                        </div>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png,image/jpeg">
                        <small class="text-muted">Recommended: Square format (e.g., 32x32) PNG or ICO.</small>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Create Villa</button>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
