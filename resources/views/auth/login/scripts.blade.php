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

        const loginForm = document.getElementById('loginForm');
        const loginSubmit = document.getElementById('loginSubmit');
        const loginSubmitText = loginSubmit?.querySelector('.cert-login-submit-text');
        const defaultSubmitText = loginSubmitText?.textContent.trim() || 'Iniciar sesión';

        // El evento submit solo se ejecuta cuando el navegador ya validó los campos obligatorios.
        // Desde ese momento se bloquea el botón para evitar que el formulario se envíe dos veces.
        loginForm?.addEventListener('submit', (event) => {
            if (loginForm.dataset.submitting === 'true') {
                event.preventDefault();
                return;
            }

            loginForm.dataset.submitting = 'true';
            loginForm.setAttribute('aria-busy', 'true');

            loginSubmit.disabled = true;
            loginSubmit.classList.add('cert-login-submit--loading');
            loginSubmit.setAttribute('aria-disabled', 'true');

            if (loginSubmitText) {
                loginSubmitText.textContent = loginSubmit.dataset.loadingText;
            }
        });

        // Algunos navegadores restauran la página desde memoria al usar el botón Atrás.
        // En ese caso se devuelve el botón a su estado normal para permitir otro intento.
        window.addEventListener('pageshow', () => {
            if (!loginForm || !loginSubmit) {
                return;
            }

            delete loginForm.dataset.submitting;
            loginForm.removeAttribute('aria-busy');
            loginSubmit.disabled = false;
            loginSubmit.classList.remove('cert-login-submit--loading');
            loginSubmit.removeAttribute('aria-disabled');

            if (loginSubmitText) {
                loginSubmitText.textContent = defaultSubmitText;
            }
        });
    })();
</script>
