document.addEventListener('DOMContentLoaded', () => {
    const passwordInput = document.getElementById('password');
    const togglePassword = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    if (passwordInput && togglePassword && passwordIcon) {
        togglePassword.addEventListener('click', () => {
            const passwordIsHidden =
                passwordInput.type === 'password';

            passwordInput.type =
                passwordIsHidden ? 'text' : 'password';

            passwordIcon.className = passwordIsHidden
                ? 'bi bi-eye-slash'
                : 'bi bi-eye';
        });
    }

    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarBackdrop =
        document.getElementById('sidebarBackdrop');

    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        sidebarBackdrop?.classList.remove('show');
    };

    sidebarToggle?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        sidebarBackdrop?.classList.toggle('show');
    });

    sidebarBackdrop?.addEventListener('click', closeSidebar);

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 992) {
            closeSidebar();
        }
    });
});