<?php
/**
 * Render Bootstrap pagination links preserving GET parameters.
 * 
 * @param int $page    Current page number (1-based)
 * @param int $total   Total number of pages
 * @param int $surround Number of page links to show around current page
 */
function render_pagination($page, $total, $surround = 2) {
    if ($total <= 1) return;

    // Build query string excluding 'page'
    $qs = $_GET;
    unset($qs['page']);
    $base = '?' . http_build_query($qs);
    if ($base === '?') $base = '';
    // Append page param
    $url = fn($p) => '?' . http_build_query(array_merge($qs, ['page' => $p]));

    echo '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';

    // Previous
    if ($page > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $url($page - 1) . '"><i class="bi bi-chevron-left"></i></a></li>';
    } else {
        echo '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>';
    }

    // Page numbers
    $start = max(1, $page - $surround);
    $end = min($total, $page + $surround);
    if ($start > 1) {
        echo '<li class="page-item"><a class="page-link" href="' . $url(1) . '">1</a></li>';
        if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $page ? ' active' : '';
        echo '<li class="page-item' . $active . '"><a class="page-link" href="' . $url($i) . '">' . $i . '</a></li>';
    }
    if ($end < $total) {
        if ($end < $total - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        echo '<li class="page-item"><a class="page-link" href="' . $url($total) . '">' . $total . '</a></li>';
    }

    // Next
    if ($page < $total) {
        echo '<li class="page-item"><a class="page-link" href="' . $url($page + 1) . '"><i class="bi bi-chevron-right"></i></a></li>';
    } else {
        echo '<li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>';
    }

    echo '</ul></nav>';
}