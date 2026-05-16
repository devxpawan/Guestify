<?php
$path = $_SERVER['PHP_SELF'];
if (strpos($path, 'modules') !== false) {
    $depth = 2;
} elseif (strpos($path, 'admin') !== false) {
    $depth = 1;
} else {
    $depth = 0;
}
$js_path = str_repeat('../', $depth) . 'assets/js/script.js';
?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?php echo $js_path; ?>"></script>
</body>
</html>
