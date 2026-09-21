document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dropdown').forEach(function (dropdown) {
        dropdown.addEventListener('mouseenter', function () {
            dropdown.classList.add('open');
        });

        dropdown.addEventListener('focus', function () {
            dropdown.classList.add('open');
        });

        dropdown.addEventListener('mouseleave', function () {
            dropdown.classList.remove('open');
        });

        dropdown.addEventListener('blur', function () {
            dropdown.classList.remove('open');
        });
    });
});
