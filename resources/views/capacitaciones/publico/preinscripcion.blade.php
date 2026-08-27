<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Preinscripción | CERTIFICADOR - INSO</title>
    <link rel="icon" type="image/png" href="{{ asset('images/branding/certificador-icon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('capacitaciones.publico.estilos')
</head>
<body>
    <header class="public-header">
        <a class="public-brand" href="{{ url('/') }}" style="color:inherit; text-decoration:none;">
            <img src="{{ asset('images/branding/certificador-icon.svg') }}" alt="Icono del Sistema Certificador">
            <strong>CERTIFICADOR - INSO</strong>
        </a>
        <div class="public-header-help">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Portal público seguro</span>
            <span aria-hidden="true">|</span>
            <i class="fa-regular fa-circle-question"></i>
            <span>Información y ayuda</span>
        </div>
    </header>

    <main class="public-main">
        <section class="public-intro">
            <div>
                <p class="public-kicker">Preinscripción pública</p>
                <h1 class="public-title">Preinscríbase a la capacitación</h1>
                <p class="public-subtitle">
                    Verifique su identidad y confirme su participación. El carnet permite reutilizar sus datos
                    sin crear personas ni cuentas duplicadas.
                </p>
            </div>

            <article class="course-summary">
                <span class="course-summary-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                <div>
                    <h2>Seguridad y Salud en el Trabajo</h2>
                    <div class="course-meta">
                        <span><i class="fa-solid fa-building"></i> Presencial</span>
                        <span><i class="fa-regular fa-calendar"></i> 15 de septiembre de 2026</span>
                        <span><i class="fa-solid fa-location-dot"></i> Instalaciones del INSO</span>
                    </div>
                </div>
            </article>
        </section>

        <div class="registration-layout">
            <section class="steps-area" aria-label="Formulario de preinscripción">
                <div class="steps-track" aria-hidden="true">
                    <div class="track-step is-active" data-track-step="1"><span class="track-number">1</span><span>Verificar identidad</span></div>
                    <div class="track-step" data-track-step="2"><span class="track-number">2</span><span>Datos del participante</span></div>
                    <div class="track-step" data-track-step="3"><span class="track-number">3</span><span>Confirmar inscripción</span></div>
                </div>

                <form id="prototype-form" novalidate>
                    <div class="step-cards">
                        <article class="form-step is-active" data-form-step="1">
                            <h3>1. Verificar identidad</h3>
                            <p class="step-help">Ingrese los datos exactamente como aparecen en su documento.</p>

                            <div class="public-feedback" id="identity-error" role="alert">
                                Ingrese su número de carnet para continuar.
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="form_ci">Carnet de identidad</label>
                                <input class="public-input" id="form_ci" name="form_ci" type="text" inputmode="numeric"
                                    autocomplete="off" placeholder="Ej.: 1234567" maxlength="15">
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="form_complemento">Complemento <span style="font-weight:500;">(opcional)</span></label>
                                <input class="public-input" id="form_complemento" name="form_complemento" type="text"
                                    autocomplete="off" placeholder="Ej.: 1A" maxlength="5">
                                <span class="field-note">Complete este campo solamente si forma parte de su carnet.</span>
                            </div>

                            <div class="field-group">
                                <label class="field-label" for="form_expedido">Expedido <span style="font-weight:500;">(opcional)</span></label>
                                <input class="public-input" id="form_expedido" name="form_expedido" type="text"
                                    autocomplete="off" placeholder="Ej.: LP" maxlength="10">
                            </div>

                            <button class="public-button public-button-primary" type="button" id="verify-button">
                                <i class="fa-regular fa-id-card"></i>
                                Verificar carnet
                            </button>
                        </article>

                        <article class="form-step is-locked" data-form-step="2">
                            <h3>2. Datos del participante</h3>
                            <p class="step-help">El sistema reutilizará el registro relacionado con su carnet.</p>

                            <div class="person-result">
                                <span class="result-icon"><i class="fa-solid fa-check"></i></span>
                                <div>
                                    <strong>Persona encontrada</strong>
                                    <span id="masked-document">Carnet pendiente de verificación</span>
                                    <span class="account-status">Sin cuenta de usuario</span>
                                </div>
                            </div>

                            <div class="participant-fields">
                                <div class="field-group field-group-wide">
                                    <label class="field-label" for="form_nombres">Nombres</label>
                                    <input class="public-input" id="form_nombres" name="form_nombres" type="text"
                                        maxlength="100" data-participant-field disabled>
                                </div>
                                <div class="field-group">
                                    <label class="field-label" for="form_apellido_paterno">Apellido paterno</label>
                                    <input class="public-input" id="form_apellido_paterno" name="form_apellido_paterno"
                                        type="text" maxlength="100" data-participant-field disabled>
                                </div>
                                <div class="field-group">
                                    <label class="field-label" for="form_apellido_materno">Apellido materno</label>
                                    <input class="public-input" id="form_apellido_materno" name="form_apellido_materno"
                                        type="text" maxlength="100" data-participant-field disabled>
                                </div>
                                <div class="field-group">
                                    <label class="field-label" for="form_fecha_nacimiento">Fecha de nacimiento</label>
                                    <input class="public-input" id="form_fecha_nacimiento" name="form_fecha_nacimiento"
                                        type="date" data-participant-field disabled>
                                </div>
                                <div class="field-group">
                                    <label class="field-label" for="form_genero">Género</label>
                                    <select class="public-input" id="form_genero" name="form_genero" data-participant-field disabled>
                                        <option value="">Seleccione</option>
                                        <option value="1">Masculino</option>
                                        <option value="0">Femenino</option>
                                    </select>
                                </div>
                                <div class="field-group field-group-wide">
                                    <label class="field-label" for="form_correo">Correo</label>
                                    <input class="public-input" id="form_correo" name="form_correo" type="email"
                                        maxlength="50" data-participant-field disabled>
                                </div>
                                <div class="field-group field-group-wide">
                                    <label class="field-label" for="form_domicilio">Domicilio</label>
                                    <input class="public-input" id="form_domicilio" name="form_domicilio" type="text"
                                        maxlength="255" data-participant-field disabled>
                                </div>
                                <div class="field-group field-group-wide">
                                    <label class="field-label" for="form_id_territorio">Territorio</label>
                                    <select class="public-input" id="form_id_territorio" name="form_id_territorio"
                                        data-participant-field disabled>
                                        <option value="">Seleccione un territorio registrado</option>
                                        @foreach ($territorios as $territorio)
                                            <option value="{{ $territorio->id }}">{{ $territorio->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <p class="step-message">
                                Puede continuar con su preinscripción. La creación de la cuenta se realizará
                                únicamente después de la validación del INSO.
                            </p>

                            <button class="public-button public-button-primary" type="button" id="continue-button" disabled>
                                <i class="fa-solid fa-arrow-right"></i>
                                Continuar preinscripción
                            </button>
                        </article>

                        <article class="form-step is-locked" data-form-step="3">
                            <h3>3. Confirmar inscripción</h3>
                            <p class="step-help">Revise la capacitación y confirme el envío del formulario.</p>

                            <div class="summary-box">
                                <strong>Resumen de la preinscripción</strong>
                                <div><b>Capacitación:</b> Seguridad y Salud en el Trabajo</div>
                                <div><b>Modalidad:</b> Presencial</div>
                                <div><b>Fecha:</b> 15 de septiembre de 2026</div>
                            </div>

                            <label class="consent">
                                <input id="consent" type="checkbox" disabled>
                                <span>Autorizo el uso de mis datos personales para la gestión de esta capacitación.</span>
                            </label>

                            <div class="public-feedback" id="consent-error" role="alert">
                                Debe aceptar el tratamiento de datos para enviar la preinscripción.
                            </div>

                            <button class="public-button public-button-primary" type="submit" id="submit-button" disabled>
                                <i class="fa-regular fa-paper-plane"></i>
                                Enviar preinscripción
                            </button>
                        </article>
                    </div>
                </form>
            </section>

            <aside class="identity-guide">
                <h3>¿Qué ocurre con mi carnet?</h3>
                <p>El sistema identifica a la persona y decide cómo continuar sin duplicar información.</p>

                <div class="guide-case">
                    <span class="guide-case-icon"><i class="fa-solid fa-user-shield"></i></span>
                    <div><strong>Ya tiene una cuenta</strong><span>Se conserva la misma cuenta y se registra la nueva capacitación.</span></div>
                </div>
                <div class="guide-case">
                    <span class="guide-case-icon"><i class="fa-solid fa-user-check"></i></span>
                    <div><strong>Existe sin cuenta</strong><span>Se reutiliza su registro. El INSO habilitará el acceso al validar.</span></div>
                </div>
                <div class="guide-case">
                    <span class="guide-case-icon"><i class="fa-solid fa-user-plus"></i></span>
                    <div><strong>No está registrado</strong><span>Se solicitarán sus datos personales antes de confirmar.</span></div>
                </div>

                <div class="safety-note">
                    <i class="fa-solid fa-shield-halved"></i>
                    Nunca se crea una segunda cuenta con el mismo carnet o correo.
                </div>
            </aside>
        </div>
    </main>

    <footer class="public-footer">
        <div><strong>Instituto Nacional de Salud Ocupacional</strong>Portal público del Sistema CERTIFICADOR</div>
        <div><i class="fa-solid fa-lock"></i> Conexión segura · Sus datos están protegidos</div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formulario = document.getElementById('prototype-form');
            const campoCi = document.getElementById('form_ci');
            const botonVerificar = document.getElementById('verify-button');
            const botonContinuar = document.getElementById('continue-button');
            const botonEnviar = document.getElementById('submit-button');
            const consentimiento = document.getElementById('consent');
            const errorIdentidad = document.getElementById('identity-error');
            const errorConsentimiento = document.getElementById('consent-error');
            const camposParticipante = document.querySelectorAll('[data-participant-field]');

            function activarPaso(numero) {
                document.querySelectorAll('[data-form-step]').forEach(tarjeta => {
                    const paso = Number(tarjeta.dataset.formStep);
                    tarjeta.classList.toggle('is-active', paso === numero);
                    tarjeta.classList.toggle('is-locked', paso > numero);
                });

                document.querySelectorAll('[data-track-step]').forEach(indicador => {
                    const paso = Number(indicador.dataset.trackStep);
                    indicador.classList.toggle('is-active', paso === numero);
                    indicador.classList.toggle('is-complete', paso < numero);
                });

                if (window.innerWidth < 900) {
                    document.querySelector(`[data-form-step="${numero}"]`).scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }

            function ocultarCarnet(carnet) {
                const ultimos = carnet.slice(-2);
                return `${'*'.repeat(Math.max(carnet.length - 2, 4))}${ultimos}`;
            }

            botonVerificar.addEventListener('click', () => {
                const carnet = campoCi.value.trim();

                if (!carnet) {
                    errorIdentidad.classList.add('is-visible');
                    campoCi.focus();
                    return;
                }

                errorIdentidad.classList.remove('is-visible');
                document.getElementById('masked-document').textContent = `Carnet ${ocultarCarnet(carnet)} listo para continuar`;
                camposParticipante.forEach(campo => campo.disabled = false);
                botonContinuar.disabled = false;
                activarPaso(2);
            });

            botonContinuar.addEventListener('click', () => {
                consentimiento.disabled = false;
                botonEnviar.disabled = false;
                activarPaso(3);
            });

            formulario.addEventListener('submit', event => {
                event.preventDefault();

                if (!consentimiento.checked) {
                    errorConsentimiento.classList.add('is-visible');
                    consentimiento.focus();
                    return;
                }

                errorConsentimiento.classList.remove('is-visible');
                botonEnviar.disabled = true;
                botonEnviar.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando...';

                window.setTimeout(() => {
                    alert('Vista de diseño: todavía no se guardaron datos.');
                    botonEnviar.disabled = false;
                    botonEnviar.innerHTML = '<i class="fa-regular fa-paper-plane"></i> Enviar preinscripción';
                }, 700);
            });
        });
    </script>
</body>
</html>
