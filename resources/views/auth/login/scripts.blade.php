<script>
    (() => {
        const password = document.getElementById('password');
        const passwordButton = document.getElementById('togglePassword');
        const openEye = passwordButton?.querySelector('[data-eye-open]');
        const closedEye = passwordButton?.querySelector('[data-eye-closed]');

        passwordButton?.addEventListener('click', () => {
            const willShowPassword = password.type === 'password';

            password.type = willShowPassword ? 'text' : 'password';
            passwordButton.setAttribute('aria-pressed', String(willShowPassword));
            passwordButton.setAttribute('aria-label', willShowPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
            openEye?.classList.toggle('hidden', willShowPassword);
            closedEye?.classList.toggle('hidden', !willShowPassword);
            password.focus();
        });

        const captchaImage = document.getElementById('loginCaptchaImage');
        const captchaInput = document.getElementById('captcha');
        const captchaButton = document.getElementById('refreshLoginCaptcha');

        captchaButton?.addEventListener('click', () => {
            const source = new URL(captchaImage.dataset.source, window.location.origin);

            // El valor variable evita que el navegador reutilice una imagen anterior.
            source.searchParams.set('refresh', Date.now().toString());
            captchaImage.src = source.toString();
            captchaInput.value = '';
            captchaInput.focus();
        });
    })();
</script>
