<?php
require_once '../../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/audit.php';

if (!has_role(['Admin', 'Manager', 'Cashier'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $transaction = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM transactions WHERE id=$id"));
    if ($transaction) {
        logAudit('DELETE', 'finance', $id, "{$transaction['type']} transaction of {$transaction['amount']} deleted", $transaction, null);
    }
    mysqli_query($conn, "DELETE FROM transactions WHERE id=$id");
    $_SESSION['success'] = 'Transaction deleted successfully!';
}

header('Location: index.php');
exit();
