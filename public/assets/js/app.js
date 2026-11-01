document.addEventListener('DOMContentLoaded', function () {
    const navLinks = document.querySelectorAll(
        '.navbar-nav .nav-link'
    );

    const navigation = document.getElementById('mainNavigation');

    navLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            if (
                navigation &&
                navigation.classList.contains('show') &&
                window.bootstrap
            ) {
                bootstrap.Collapse.getOrCreateInstance(
                    navigation
                ).hide();
            }
        });
    });
});