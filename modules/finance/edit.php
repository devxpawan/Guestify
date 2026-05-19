<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';

if (!has_role(['Admin', 'Manager', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];
$transaction = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transactions WHERE id=$id"));
if (!$transaction) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

$categories = mysqli_query($conn, "SELECT * FROM finance_categories ORDER BY type, name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $category_id = (int)$_POST['category_id'];
    $amount = (float)$_POST['amount'];
    $description = mysqli_real_escape_string($conn, trim($_POST['description']));
    $transaction_date = mysqli_real_escape_string($conn, $_POST['transaction_date']);

    if ($amount <= 0) {
        $error = 'Amount must be greater than 0.';
    } elseif (empty($transaction_date)) {
        $error = 'Please select a date.';
    } else {
        $query = "UPDATE transactions SET type='$type', category_id=$category_id, amount=$amount, description='$description', transaction_date='$transaction_date' WHERE id=$id";
        if (mysqli_query($conn, $query)) {
            $_SESSION['success'] = 'Transaction updated successfully!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Failed: ' . mysqli_error($conn);
        }
    }
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>
<div id="page-content-wrapper" class="container-fluid p-4">
    <div class="page-header mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2><i class="bi bi-pencil-square"></i> Edit Transaction</h2>
                <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">Update transaction details</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Transaction Type</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="typeIncome" value="Income" <?= $transaction['type'] === 'Income' ? 'checked' : '' ?> onchange="toggleCategories()">
                            <label class="form-check-label text-success fw-bold" for="typeIncome">
                                <i class="bi bi-arrow-down-circle"></i> Income
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="typeExpense" value="Expense" <?= $transaction['type'] === 'Expense' ? 'checked' : '' ?> onchange="toggleCategories()">
                            <label class="form-check-label text-danger fw-bold" for="typeExpense">
                                <i class="bi bi-arrow-up-circle"></i> Expense
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category_id" id="categorySelect" class="form-select" required>
                        <option value="">Select Category</option>
                        <?php mysqli_data_seek($categories, 0); while ($cat = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?= $cat['id'] ?>" data-type="<?= $cat['type'] ?>" <?= $transaction['category_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount (<?= htmlspecialchars($global_currency) ?>)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" value="<?= $transaction['amount'] ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($transaction['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="transaction_date" class="form-control" value="<?= $transaction['transaction_date'] ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Update Transaction</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
function toggleCategories() {
    const type = document.querySelector('input[name="type"]:checked').value;
    document.querySelectorAll('#categorySelect option').forEach(opt => {
        if (opt.value === '') return;
        opt.style.display = opt.dataset.type === type ? '' : 'none';
    });
}
document.addEventListener('DOMContentLoaded', toggleCategories);
</script>

<?php include '../../includes/footer.php'; ?>
