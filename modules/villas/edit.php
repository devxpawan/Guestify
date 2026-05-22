<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin'])) {
    header('Location: ../../dashboard.php');
    exit();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$villa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM villas WHERE id = $id"));
if (!$villa) {
    header('Location: index.php');
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
    } else {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '-', $slug));
        $slug = preg_replace('/-+/', '-', trim($slug, '-'));

        $check = mysqli_query($conn, "SELECT id FROM villas WHERE slug = '$slug' AND id != $id LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            $error = 'A villa with this slug already exists.';
        }
    }

    if (empty($error)) {
        $query = "UPDATE villas SET
                    name = '$name',
                    slug = '$slug',
                    company_name = '$company_name',
                    company_email = '$company_email',
                    currency_symbol = '$currency_symbol',
                    address = '$address',
                    phone = '$phone'
                  WHERE id = $id";

        if (mysqli_query($conn, $query)) {
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
                    mysqli_query($conn, "UPDATE villas SET logo_path = '$logo_name' WHERE id = $id");
                } else {
                    $error .= "Failed to upload logo. ";
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
                    mysqli_query($conn, "UPDATE villas SET favicon_path = '$favicon_name' WHERE id = $id");
                } else {
                    $error .= "Failed to upload favicon. ";
                }
            }

            if (empty($error)) {
                $_SESSION['success'] = "Villa updated successfully!";
                header("Location: edit.php?id=$id");
                exit();
            }
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
        <h2><i class="bi bi-pencil"></i> Edit Villa: <?= htmlspecialchars($villa['name']) ?></h2>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Update villa details, branding, and settings</p>
    </div>

    <div class="card">
        <div class="card-header">
            <h5><i class="bi bi-building"></i> Villa Details</h5>
        </div>
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Villa Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($villa['name']) ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Slug</label>
                        <input type="text" name="slug" class="form-control" value="<?= htmlspecialchars($villa['slug']) ?>" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($villa['company_name'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?= htmlspecialchars($villa['company_email'] ?? '') ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency Symbol</label>
                        <input type="text" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($villa['currency_symbol'] ?? '$') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($villa['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($villa['address'] ?? '') ?></textarea>
                </div>

                <hr>

                <div class="row mt-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Logo</label>
                        <?php if (!empty($villa['logo_path'])): ?>
                            <div class="mb-2">
                                <img src="../../uploads/<?= htmlspecialchars($villa['logo_path']) ?>" alt="Logo" class="img-thumbnail" style="max-height: 80px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Favicon</label>
                        <?php if (!empty($villa['favicon_path'])): ?>
                            <div class="mb-2">
                                <img src="../../uploads/<?= htmlspecialchars($villa['favicon_path']) ?>" alt="Favicon" class="img-thumbnail" style="max-height: 40px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="favicon" class="form-control" accept="image/x-icon,image/png,image/jpeg">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">Back to List</a>
                </div>
            </form>
        </div>
    </div>

    <!-- User Assignment -->
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people"></i> Assigned Users</h5>
        </div>
        <div class="card-body">
            <?php
            $users_q = mysqli_query($conn, "
                SELECT u.id, u.username, uv.is_default
                FROM user_villas uv
                JOIN users u ON uv.user_id = u.id
                WHERE uv.villa_id = $id
                ORDER BY u.username
            ");
            ?>
            <?php if (mysqli_num_rows($users_q) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Default</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = mysqli_fetch_assoc($users_q)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                            <td>
                                <?php if ($u['is_default']): ?>
                                    <span class="badge badge-success">Default</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="../../admin/remove_villa_user.php" class="d-inline" onsubmit="return confirm('Remove this user from this villa?');">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <input type="hidden" name="villa_id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-user-minus"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted mb-0">No users assigned to this villa.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>
