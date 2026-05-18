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
