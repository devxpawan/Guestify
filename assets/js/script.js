$(document).ready(function () {
    $('.table').addClass('table-bordered');

    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("toggled");
    });
});
