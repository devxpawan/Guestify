<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../../dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $slug = mysqli_real_escape_string($conn, trim($_POST['slug']));
    $company_name = mysqli_real_escape_string($conn, trim($_POST['company_name']));
    $company_email = mysqli_real_escape_string($conn, trim($_POST['company_email']));
    $currency_symbol = mysqli_real_escape_string($conn, trim($_POST['currency_symbol']));
    $address = mysqli_real_escape_string($conn, trim($_POST['address']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));

    if (empty($name)) {
        $error = 'Villa name is required.';
    } elseif (empty($slug)) {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $name));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));
    } else {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $slug));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));
    }

    // Check slug uniqueness
    $check = mysqli_query($conn, "SELECT id FROM villas WHERE slug = '$slug' LIMIT 1");
    if (mysqli_num_rows($check) > 0) {
        $error = 'A villa with this slug already exists.';
    }

    if (empty($error)) {
        $query = "INSERT INTO villas (name, slug, company_name, company_email, currency_symbol, address, phone, status)
                  VALUES ('$name', '$slug', '$company_name', '$company_email', '$currency_symbol', '$address', '$phone', 'Active')";
        if (mysqli_query($conn, $query)) {
            $villa_id = mysqli_insert_id($conn);

            // Assign the creating admin to this villa
            $uid = (int)$_SESSION['user_id'];
            mysqli_query($conn, "INSERT INTO user_villas (user_id, villa_id, is_default) VALUES ($uid, $villa_id, 0)");

            $_SESSION['success'] = "Villa '$name' created successfully!";
            header("Location: index.php");
            exit();
        } else {
            $error = 'Database error: ' . mysqli_error($conn);
        }
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
            <h5><i class="bi bi-building"></i> Villa Details</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Villa Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Slug (URL-friendly)</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>" placeholder="e.g. beach-villa">
                        <small class="text-muted">Auto-generated from name if left empty.</small>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($_POST['company_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($_POST['currency_symbol'] ?? '$') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
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
