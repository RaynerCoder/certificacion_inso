<x-guest-layout>
    <x-slot name="title">Acceso al sistema | CERTIFICADOR</x-slot>

    @include('auth.login.styles')

    <div class="cert-login-page">
        <main class="cert-login-main">
            <section class="cert-login-brand" aria-labelledby="certificador-name">
                <svg class="cert-brand-pattern cert-brand-pattern--one" viewBox="0 0 120 150" aria-hidden="true">
                    <path d="M18 7h58l27 27v106H18zM76 7v27h27M35 55h50M35 70h50M35 85h37" />
                    <circle cx="82" cy="111" r="17" />
                    <path d="m72 127-5 18 15-8 14 8-4-18" />
                </svg>
                <svg class="cert-brand-pattern cert-brand-pattern--two" viewBox="0 0 120 150" aria-hidden="true">
                    <path d="M18 7h58l27 27v106H18zM76 7v27h27M35 55h50M35 70h50M35 85h37" />
                    <circle cx="82" cy="111" r="17" />
                    <path d="m72 127-5 18 15-8 14 8-4-18" />
                </svg>
                <svg class="cert-brand-pattern cert-brand-pattern--three" viewBox="0 0 120 150" aria-hidden="true">
                    <path d="M18 7h58l27 27v106H18zM76 7v27h27M35 55h50M35 70h50M35 85h37" />
                    <circle cx="82" cy="111" r="17" />
                    <path d="m72 127-5 18 15-8 14 8-4-18" />
                </svg>

                <div class="cert-brand-content">
                    {{-- El recorte muestra el emblema; el nombre institucional queda como texto adaptable. --}}
                    <div class="cert-brand-logo-crop">
                        <img
                            src="{{ asset('images/branding/inso-logo.png') }}"
                            alt="Logotipo del Instituto Nacional de Salud Ocupacional"
                        >
                    </div>

                    <div class="cert-brand-copy">
                        <p class="cert-brand-institution">
                            Instituto Nacional de<br>Salud Ocupacional
                        </p>
                        <span class="cert-brand-divider" aria-hidden="true"></span>
                        <p class="cert-brand-description">Sistema de Gestión y Consulta de Certificados</p>
                        <h1 id="certificador-name" class="cert-brand-system-name">CERTIFICADOR</h1>
                    </div>
                </div>
            </section>

            <section class="cert-login-access" aria-labelledby="login-title">
                <div class="cert-login-card">
                    <img
                        src="{{ asset('images/branding/certificador-icon.svg') }}"
                        class="cert-login-icon"
                        alt="Ícono de Certificador"
                    >

                    <header class="cert-login-header">
                        <h2 id="login-title">Acceso al sistema</h2>
                        <p>Ingrese sus credenciales institucionales</p>
                    </header>

                    @session('status')
                        <div class="cert-login-status" role="status">
                            {{ $value }}
                        </div>
                    @endsession

                    <form id="loginForm" method="POST" action="{{ route('login') }}" class="cert-login-form">
                        @csrf

                        <div class="cert-form-field">
                            <label for="email">Correo o usuario</label>
                            <div class="cert-input-wrap @error('email') cert-input-wrap--error @enderror">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M3.75 5.75h16.5v12.5H3.75zM4.5 6.5 12 12l7.5-5.5" />
                                </svg>
                                <input
                                    id="email"
                                    type="text"
                                    name="email"
                                    value="{{ old('email') }}"
                                    placeholder="correo@institucion.gob.bo"
                                    required
                                    autofocus
                                    autocomplete="username"
                                >
                            </div>
                            @error('email')
                                <p class="cert-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cert-form-field">
                            <label for="password">Contraseña</label>
                            <div class="cert-input-wrap">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <rect x="5" y="10" width="14" height="10" rx="2" />
                                    <path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2" />
                                </svg>
                                <input
                                    id="password"
                                    type="password"
                                    name="password"
                                    placeholder="Ingrese su contraseña"
                                    required
                                    autocomplete="current-password"
                                >
                                <button
                                    id="togglePassword"
                                    type="button"
                                    class="cert-input-action"
                                    aria-label="Mostrar contraseña"
                                    aria-pressed="false"
                                >
                                    <svg data-eye-open viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M2.5 12s3.5-5 9.5-5 9.5 5 9.5 5-3.5 5-9.5 5-9.5-5-9.5-5Z" />
                                        <circle cx="12" cy="12" r="2.5" />
                                    </svg>
                                    <svg data-eye-closed class="hidden" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="m3 3 18 18M10.6 7.1A9.5 9.5 0 0 1 12 7c6 0 9.5 5 9.5 5a15 15 0 0 1-2.4 2.7M6.2 6.2C3.8 8 2.5 12 2.5 12s3.5 5 9.5 5c1.5 0 2.8-.3 4-.8M9.8 9.8a3 3 0 0 0 4.4 4.4" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="cert-field-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="cert-login-options">
                            <label for="remember_me" class="cert-remember-option">
                                <input id="remember_me" type="checkbox" name="remember">
                                <span>Recordarme</span>
                            </label>

                            {{--
                                Recuperación de contraseña pendiente.
                                Cuando el proceso esté habilitado, se puede volver a mostrar este bloque.

                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}">¿Olvidó su contraseña?</a>
                                @endif
                            --}}
                        </div>

                        <fieldset class="cert-captcha-box @error('captcha') cert-captcha-box--error @enderror">
                            <legend>Código de seguridad</legend>

                            <div class="cert-captcha-row">
                                <img
                                    id="loginCaptchaImage"
                                    src="{{ route('captcha.login') }}"
                                    data-source="{{ route('captcha.login') }}"
                                    width="400"
                                    height="80"
                                    alt="Código de seguridad. Si no puede leerlo, use el botón de recarga."
                                >
                                <button
                                    id="refreshLoginCaptcha"
                                    type="button"
                                    class="cert-captcha-refresh"
                                    aria-label="Generar otro código de seguridad"
                                    title="Cambiar código"
                                >
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M20 11a8 8 0 0 0-15.5-3" />
                                        <path d="M4 4v4h4" />
                                        <path d="M4 13a8 8 0 0 0 15.5 3" />
                                        <path d="M20 20v-4h-4" />
                                    </svg>
                                </button>
                            </div>

                            <input
                                id="captcha"
                                type="text"
                                name="captcha"
                                class="cert-captcha-input"
                                placeholder="Escriba el código"
                                maxlength="6"
                                required
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                            >

                            @error('captcha')
                                <p class="cert-field-error">{{ $message }}</p>
                            @enderror
                        </fieldset>

                        <button
                            id="loginSubmit"
                            type="submit"
                            class="cert-login-submit"
                            data-loading-text="Ingresando al sistema…"
                        >
                            <span class="cert-login-spinner" aria-hidden="true"></span>
                            <span class="cert-login-submit-text">Iniciar sesión</span>
                        </button>
                    </form>
                </div>
            </section>
        </main>

        <footer class="cert-login-footer">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m12 3 7 3v5c0 4.6-2.8 8-7 10-4.2-2-7-5.4-7-10V6zM9 12l2 2 4-4" />
            </svg>
            <span>Instituto Nacional de Salud Ocupacional</span>
        </footer>
    </div>

    @include('auth.login.scripts')
</x-guest-layout>
