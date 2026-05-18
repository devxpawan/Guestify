$(document).ready(function () {
    $('.table').addClass('table-bordered');

    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("body").toggleClass("toggled");
    });

    // Sidebar active state
    var path = window.location.pathname;

    $('.sidebar-link[data-page], .sidebar-sublink[data-page]').each(function() {
        var href = $(this).attr('href');
        if (href && path.indexOf(href.replace(/^.*\/\/[^\/]+/, '').replace(/\.\.\//g, '')) !== -1) {
            $(this).addClass('active');

            // Expand parent submenu if sublink is active
            var submenu = $(this).closest('.sidebar-submenu');
            if (submenu.length) {
                submenu.addClass('show');
                submenu.prev('.sidebar-link-toggle').attr('aria-expanded', 'true');
            }
        }
    });
});

// Global Toast Notification System
function showToast(type, message) {
    var toastEl = document.getElementById('systemToast');
    var toastBody = document.getElementById('toastMessage');
    
    if (!toastEl || !toastBody) return;

    // Reset classes
    toastEl.className = 'toast align-items-center text-white border-0';
    
    // Configure based on type
    if (type === 'success') {
        toastEl.classList.add('bg-success');
        toastBody.innerHTML = '<i class="bi bi-check-circle me-2"></i> ' + message;
    } else if (type === 'error') {
        toastEl.classList.add('bg-danger');
        toastBody.innerHTML = '<i class="bi bi-exclamation-circle me-2"></i> ' + message;
    }
    
    // Show toast
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
}
