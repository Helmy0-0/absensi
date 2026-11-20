document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = button.parentElement.querySelector('input');
    const eyeIcon = button.querySelector('[data-eye]');
    const eyeOffIcon = button.querySelector('[data-eye-off]');

    button.addEventListener('click', () => {
        const isVisible = input.type === 'password';

        input.type = isVisible ? 'text' : 'password';
        button.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
        button.setAttribute('aria-pressed', String(isVisible));
        eyeIcon.hidden = isVisible;
        eyeOffIcon.hidden = !isVisible;
    });
});
