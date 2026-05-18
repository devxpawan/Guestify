<?php
$js_path = $base_url . 'assets/js/script.js';

// Consolidate session and local messages
$toast_success = '';
$toast_error = '';

if (isset($success) && !empty($success)) {
    $toast_success = $success;
} elseif (isset($_SESSION['success']) && !empty($_SESSION['success'])) {
    $toast_success = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($error) && !empty($error)) {
    $toast_error = $error;
} elseif (isset($_SESSION['error']) && !empty($_SESSION['error'])) {
    $toast_error = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>
</div>

<!-- Global Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1055;">
    <div id="systemToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
                <!-- Message will be injected here -->
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?php echo $js_path; ?>"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!empty($toast_success)): ?>
            if (typeof showToast === 'function') {
                showToast('success', <?php echo json_encode($toast_success); ?>);
            }
        <?php endif; ?>
        
        <?php if (!empty($toast_error)): ?>
            if (typeof showToast === 'function') {
                showToast('error', <?php echo json_encode($toast_error); ?>);
            }
        <?php endif; ?>
    });
</script>
</body>
</html>
