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
        <a class="public-brand" href="{{ url('/') }}">
            <img src="{{ asset('images/branding/certificador-icon.svg') }}" alt="Icono del Sistema Certificador">
            <span><strong>CERTIFICADOR</strong><small>Instituto Nacional de Salud Ocupacional</small></span>
        </a>
        <div class="public-header-help">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Formulario público seguro</span>
        </div>
    </header>

    <main class="public-main">
        <section class="public-course">
            <div class="public-course-copy">
                <span class="public-kicker">Preinscripción a capacitación</span>
                <h1>Seguridad y Salud en el Trabajo</h1>
                <p>Complete el formulario para solicitar su participación.</p>
            </div>
            <div class="course-meta">
                <span><i class="fa-solid fa-building"></i><b>Modalidad</b>Presencial</span>
                <span><i class="fa-regular fa-calendar"></i><b>Fecha</b>15 de septiembre de 2026</span>
                <span><i class="fa-solid fa-location-dot"></i><b>Lugar</b>Instalaciones del INSO</span>
            </div>
        </section>

        <section class="public-form-shell" aria-label="Formulario de preinscripción">
            <div class="public-progress-head">
                <div>
                    <span id="public-step-label">Paso 1 de 4</span>
                    <strong id="public-step-title">Identidad</strong>
                </div>
                <span id="public-progress-percent">25%</span>
            </div>
            <div class="public-progress" aria-hidden="true">
                <span id="public-progress-bar" style="width: 25%"></span>
            </div>

            <nav class="public-stepper" aria-label="Pasos del formulario">
                <button class="public-step is-active" type="button" data-step-button="1" aria-current="step">
                    <span>1</span><b>Identidad</b>
                </button>
                <button class="public-step" type="button" data-step-button="2" disabled>
                    <span>2</span><b>Datos personales</b>
                </button>
                <button class="public-step" type="button" data-step-button="3" disabled>
                    <span>3</span><b>Cuestionario</b>
                </button>
                <button class="public-step" type="button" data-step-button="4" disabled>
                    <span>4</span><b>Confirmación</b>
                </button>
            </nav>

            {{-- El formulario es demostrativo: no tiene action y no realiza escrituras en la base de datos. --}}
            <form id="public-registration-form" novalidate>
                <section class="public-panel is-active" data-step-panel="1">
                    <header class="public-panel-head">
                        <span class="public-panel-icon"><i class="fa-regular fa-id-card"></i></span>
                        <div>
                            <h2>Verifique su identidad</h2>
                            <p>Ingrese los datos como aparecen en su carnet. Así evitamos registros duplicados.</p>
                        </div>
                    </header>

                    <div class="public-grid public-grid-three">
                        <label class="public-field">
                            <span>Carnet de identidad <b>*</b></span>
                            <input id="form_ci" name="form_ci" type="text" inputmode="numeric"
                                maxlength="15" placeholder="Ej.: 1234567" required>
                        </label>
                        <label class="public-field">
                            <span>Complemento <small>(opcional)</small></span>
                            <input id="form_complemento" name="form_complemento" type="text"
                                maxlength="5" placeholder="Ej.: 1A">
                        </label>
                        <label class="public-field">
                            <span>Expedido <small>(opcional)</small></span>
                            <select id="form_expedido" name="form_expedido">
                                <option value="">Seleccione</option>
                                <option value="LP">La Paz</option>
                                <option value="CB">Cochabamba</option>
                                <option value="SC">Santa Cruz</option>
                                <option value="OR">Oruro</option>
                                <option value="PT">Potosí</option>
                                <option value="CH">Chuquisaca</option>
                                <option value="TJ">Tarija</option>
                                <option value="BE">Beni</option>
                                <option value="PD">Pando</option>
                            </select>
                        </label>
                    </div>

                    <div class="public-question-list public-extra-fields" data-custom-fields="1"></div>

                    <div class="public-identity-result" id="public-identity-result" hidden>
                        <i class="fa-solid fa-circle-check"></i>
                        <div><strong>Identidad lista para continuar</strong><span>En esta vista frontend no se consulta todavía la base de datos.</span></div>
                    </div>

                    <div class="public-panel-actions">
                        <span class="public-required-note"><b>*</b> Campo obligatorio</span>
                        <button class="public-button public-button-primary" id="public-verify-button" type="button">
                            Verificar y continuar <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </section>

                <section class="public-panel" data-step-panel="2" hidden>
                    <header class="public-panel-head">
                        <span class="public-panel-icon"><i class="fa-regular fa-user"></i></span>
                        <div>
                            <h2>Datos personales y de contacto</h2>
                            <p>Complete sus datos. Los campos de trabajo no crean ni representan una empresa en el sistema.</p>
                        </div>
                    </header>

                    <div class="public-section-label">Información personal</div>
                    <div class="public-grid public-grid-two">
                        <label class="public-field public-field-wide">
                            <span>Nombres <b>*</b></span>
                            <input id="form_nombres" name="form_nombres" type="text" maxlength="100" required>
                        </label>
                        <label class="public-field">
                            <span>Apellido paterno <b>*</b></span>
                            <input id="form_apellido_paterno" name="form_apellido_paterno" type="text" maxlength="100" required>
                        </label>
                        <label class="public-field">
                            <span>Apellido materno</span>
                            <input id="form_apellido_materno" name="form_apellido_materno" type="text" maxlength="100">
                        </label>
                        <label class="public-field">
                            <span>Fecha de nacimiento</span>
                            <input id="form_fecha_nacimiento" name="form_fecha_nacimiento" type="date">
                        </label>
                        <label class="public-field">
                            <span>Género</span>
                            <select id="form_genero" name="form_genero">
                                <option value="">Seleccione</option>
                                <option value="1">Masculino</option>
                                <option value="0">Femenino</option>
                            </select>
                        </label>
                    </div>

                    <div class="public-question-list public-extra-fields" data-custom-fields="2"></div>

                    <div class="public-section-label">Contacto</div>
                    <div class="public-grid public-grid-two">
                        <label class="public-field">
                            <span>Correo electrónico <b>*</b></span>
                            <input id="form_correo" name="form_correo" type="email" maxlength="50"
                                placeholder="nombre@correo.com" required>
                        </label>
                        <label class="public-field">
                            <span>Teléfono o celular</span>
                            <input id="form_telefono" name="form_telefono" type="tel" maxlength="20">
                        </label>
                        <label class="public-field public-field-wide">
                            <span>Domicilio</span>
                            <input id="form_domicilio" name="form_domicilio" type="text" maxlength="255">
                        </label>
                        <label class="public-field public-field-wide">
                            <span>Territorio</span>
                            <select id="form_id_territorio" name="form_id_territorio">
                                <option value="">Seleccione un territorio registrado</option>
                                @foreach ($territorios as $territorio)
                                    <option value="{{ $territorio->id }}">{{ $territorio->nombre }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="public-section-label">Información laboral <small>(opcional)</small></div>
                    <div class="public-grid public-grid-two">
                        <label class="public-field public-field-wide">
                            <span>Institución o empresa donde trabaja</span>
                            <input id="form_institucion" name="form_institucion" type="text" maxlength="150"
                                placeholder="Este dato no registra una empresa ni solicita representante legal">
                        </label>
                        <label class="public-field">
                            <span>Cargo</span>
                            <input id="form_cargo" name="form_cargo" type="text" maxlength="100">
                        </label>
                        <label class="public-field">
                            <span>Área de trabajo</span>
                            <input id="form_area_trabajo" name="form_area_trabajo" type="text" maxlength="100">
                        </label>
                    </div>

                    <div class="public-panel-actions">
                        <button class="public-button" type="button" data-previous-step="1">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </button>
                        <button class="public-button public-button-primary" type="button" data-next-step="3">
                            Continuar <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </section>

                <section class="public-panel" data-step-panel="3" hidden>
                    <header class="public-panel-head">
                        <span class="public-panel-icon"><i class="fa-regular fa-rectangle-list"></i></span>
                        <div>
                            <h2>Cuestionario de la capacitación</h2>
                            <p>Estas preguntas son configurables desde el módulo interno.</p>
                        </div>
                    </header>

                    <div class="public-question-list" data-custom-fields="3"></div>

                    <div class="public-panel-actions">
                        <button class="public-button" type="button" data-previous-step="2">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </button>
                        <button class="public-button public-button-primary" type="button" data-next-step="4">
                            Revisar y confirmar <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </section>

                <section class="public-panel" data-step-panel="4" hidden>
                    <header class="public-panel-head">
                        <span class="public-panel-icon"><i class="fa-regular fa-circle-check"></i></span>
                        <div>
                            <h2>Confirme su preinscripción</h2>
                            <p>Revise que haya completado correctamente los pasos anteriores.</p>
                        </div>
                    </header>

                    <div class="public-confirmation">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        <div>
                            <strong>Seguridad y Salud en el Trabajo</strong>
                            <span id="public-confirmation-person">Participante pendiente de completar</span>
                        </div>
                    </div>

                    <div class="public-question-list public-extra-fields" data-custom-fields="4"></div>

                    <label class="public-consent">
                        <input id="form_autorizacion" name="form_autorizacion" type="checkbox" required>
                        <span><strong>Autorizo el uso de mis datos personales para gestionar esta capacitación.</strong>
                            <small>La información será utilizada únicamente para la preinscripción, validación y comunicación relacionadas con esta actividad.</small>
                        </span>
                    </label>

                    <div class="public-prototype-note">
                        <i class="fa-solid fa-circle-info"></i>
                        Esta es una demostración frontend. Al enviar no se registrará información en la base de datos.
                    </div>

                    <div class="public-panel-actions">
                        <button class="public-button" type="button" data-previous-step="3">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </button>
                        <button class="public-button public-button-primary" id="public-submit-button" type="submit">
                            <i class="fa-regular fa-paper-plane"></i> Enviar preinscripción
                        </button>
                    </div>
                </section>
            </form>
        </section>
    </main>

    <footer class="public-footer">
        <span><strong>INSO</strong> · Sistema de Gestión y Consulta de Certificados</span>
        <span><i class="fa-solid fa-lock"></i> Conexión segura</span>
    </footer>

    <div class="public-toast" id="public-toast" role="status" aria-live="polite"></div>

    @php
        $territoriosFormulario = $territorios
            ->map(fn ($territorio) => ['value' => $territorio->id, 'label' => $territorio->nombre])
            ->values();
    @endphp

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const formulario = document.getElementById('public-registration-form');
            const territoriosSistema = @json($territoriosFormulario);
            const predeterminada = {
                version: 3,
                ordenPasos: [100],
                etapas: {
                    100: {
                        id: 100,
                        titulo: 'Paso 1',
                        descripcion: 'Este formulario todavía no tiene preguntas configuradas.',
                        tipoSistema: 'personalizado',
                        campos: [],
                    },
                },
            };

            let configuracion = predeterminada;
            let ordenPasos = [...predeterminada.ordenPasos];
            let indiceActual = 0;
            let indiceHabilitado = 0;

            function escaparHtml(texto) {
                const contenedor = document.createElement('div');
                contenedor.textContent = texto ?? '';
                return contenedor.innerHTML;
            }

            function cargarConfiguracion() {
                try {
                    const guardada = JSON.parse(localStorage.getItem('certificador_formulario_capacitacion_demo'));
                    if (guardada?.version === 3 && guardada.etapas && Array.isArray(guardada.ordenPasos)) {
                        configuracion = guardada;
                        ordenPasos = guardada.ordenPasos.map(Number).filter(id => guardada.etapas[id]);
                    }
                } catch (error) {
                    localStorage.removeItem('certificador_formulario_capacitacion_demo');
                }
            }

            function dibujarCampo(campo) {
                const nombre = campo.formName || `form_pregunta_${Number(campo.id) || 'nueva'}`;
                const requerido = campo.required ? ' required' : '';
                const marca = campo.required ? ' <b>*</b>' : '';
                const ayuda = campo.help ? `<small class="public-field-help">${escaparHtml(campo.help)}</small>` : '';
                const opciones = campo.options?.length ? campo.options : ['Opción 1', 'Opción 2'];

                if (campo.type === 'note') {
                    return `<div class="public-prototype-note"><i class="fa-solid fa-circle-info"></i>${escaparHtml(campo.help || campo.label)}</div>`;
                }

                if (campo.type === 'radio' || campo.type === 'checkbox') {
                    const sufijo = campo.type === 'checkbox' ? '[]' : '';
                    const grupoRequerido = campo.type === 'checkbox' && campo.required ? ' data-required-checkbox' : '';
                    const items = opciones.map((opcion, indice) => `
                        <label><input name="${nombre}${sufijo}" type="${campo.type}" value="${escaparHtml(opcion)}"${campo.type === 'radio' && campo.required && indice === 0 ? ' required' : ''}>
                        <span>${escaparHtml(opcion)}</span></label>`).join('');
                    return `<fieldset class="public-choice-group"${grupoRequerido}><legend>${escaparHtml(campo.label)}${marca}</legend>${ayuda}${items}</fieldset>`;
                }

                if (campo.type === 'select') {
                    const opcionesSistema = campo.source === 'personas.id_territorio'
                        ? territoriosSistema
                        : opciones.map((opcion, indice) => ({ value: campo.optionValues?.[indice] ?? opcion, label: opcion }));
                    const items = opcionesSistema.map(opcion => `<option value="${escaparHtml(opcion.value)}">${escaparHtml(opcion.label)}</option>`).join('');
                    return `<label class="public-field"><span>${escaparHtml(campo.label)}${marca}</span>${ayuda}<select name="${nombre}"${requerido}><option value="">Seleccione</option>${items}</select></label>`;
                }
                if (campo.type === 'textarea') {
                    return `<label class="public-field"><span>${escaparHtml(campo.label)}${marca}</span>${ayuda}<textarea name="${nombre}" rows="4"${requerido}></textarea></label>`;
                }

                const tipo = campo.source === 'personas.correo'
                    ? 'email'
                    : campo.source === 'telefonos.numero'
                        ? 'tel'
                        : ['number', 'date'].includes(campo.type) ? campo.type : 'text';
                return `<label class="public-field"><span>${escaparHtml(campo.label)}${marca}</span>${ayuda}<input name="${nombre}" type="${tipo}"${requerido}></label>`;
            }

            function crearPanelesPersonalizados() {
                ordenPasos.forEach(id => {
                    const etapa = configuracion.etapas[id];
                    const existente = document.querySelector(`[data-step-panel="${id}"]`);
                    if (existente) {
                        const titulo = existente.querySelector('.public-panel-head h2');
                        const descripcion = existente.querySelector('.public-panel-head p');
                        if (titulo) titulo.textContent = etapa.titulo;
                        if (descripcion && etapa.descripcion) descripcion.textContent = etapa.descripcion;
                        return;
                    }
                    const indice = ordenPasos.indexOf(id);
                    const esPrimero = indice === 0;
                    const esUltimo = indice === ordenPasos.length - 1;
                    const botonAnterior = esPrimero
                        ? ''
                        : '<button class="public-button" type="button" data-dynamic-previous><i class="fa-solid fa-arrow-left"></i> Volver</button>';
                    const botonSiguiente = esUltimo
                        ? '<button class="public-button public-button-primary" type="submit"><i class="fa-regular fa-paper-plane"></i> Enviar preinscripción</button>'
                        : '<button class="public-button public-button-primary" type="button" data-dynamic-next>Continuar <i class="fa-solid fa-arrow-right"></i></button>';
                    const panel = document.createElement('section');
                    panel.className = 'public-panel';
                    panel.dataset.stepPanel = id;
                    panel.hidden = true;
                    panel.innerHTML = `
                        <header class="public-panel-head">
                            <span class="public-panel-icon"><i class="fa-regular fa-rectangle-list"></i></span>
                            <div><h2>${escaparHtml(etapa.titulo)}</h2><p>${escaparHtml(etapa.descripcion || 'Complete los siguientes campos.')}</p></div>
                        </header>
                        <div class="public-question-list" data-custom-fields="${id}"></div>
                        <div class="public-panel-actions">${botonAnterior}${botonSiguiente}</div>`;
                    formulario.appendChild(panel);
                });
                ordenPasos.forEach(id => {
                    const panel = document.querySelector(`[data-step-panel="${id}"]`);
                    if (panel) formulario.appendChild(panel);
                });
            }

            function cargarPreguntas() {
                document.querySelectorAll('[data-custom-fields]').forEach(contenedor => {
                    const id = Number(contenedor.dataset.customFields);
                    const etapa = configuracion.etapas[id];
                    let campos = Array.isArray(etapa?.campos) ? etapa.campos : [];
                    if (!['cuestionario', 'personalizado'].includes(etapa?.tipoSistema)) {
                        campos = campos.filter(campo => !campo.locked);
                    }
                    contenedor.innerHTML = campos.map(dibujarCampo).join('');
                    contenedor.hidden = campos.length === 0;
                });
            }

            function renderizarNavegacion() {
                const navegador = document.querySelector('.public-stepper');
                navegador.style.gridTemplateColumns = `repeat(${ordenPasos.length}, minmax(0, 1fr))`;
                navegador.innerHTML = ordenPasos.map((id, indice) => `
                    <button class="public-step ${indice === indiceActual ? 'is-active' : ''}" type="button" data-step-index="${indice}" ${indice > indiceHabilitado ? 'disabled' : ''}>
                        <span>${indice + 1}</span><b>${escaparHtml(configuracion.etapas[id].titulo)}</b>
                    </button>`).join('');
                navegador.querySelectorAll('[data-step-index]').forEach(boton => {
                    boton.addEventListener('click', () => abrirPaso(Number(boton.dataset.stepIndex)));
                });
            }

            function mostrarMensaje(texto) {
                const mensaje = document.getElementById('public-toast');
                mensaje.textContent = texto;
                mensaje.classList.add('is-visible');
                window.setTimeout(() => mensaje.classList.remove('is-visible'), 3400);
            }

            function validarPaso(indice) {
                const panel = document.querySelector(`[data-step-panel="${ordenPasos[indice]}"]`);
                const grupoIncompleto = [...panel.querySelectorAll('[data-required-checkbox]')]
                    .find(grupo => !grupo.querySelector('input:checked'));
                if (grupoIncompleto) {
                    const casilla = grupoIncompleto.querySelector('input');
                    casilla.setCustomValidity('Seleccione al menos una opción.');
                    casilla.reportValidity();
                    casilla.focus();
                    return false;
                }
                const invalido = [...panel.querySelectorAll('input, select, textarea')].find(campo => !campo.checkValidity());
                if (!invalido) return true;
                invalido.reportValidity();
                invalido.focus();
                return false;
            }

            function actualizarConfirmacion() {
                const destino = document.getElementById('public-confirmation-person');
                if (!destino) return;
                const nombres = document.getElementById('form_nombres')?.value.trim() || 'Participante';
                const paterno = document.getElementById('form_apellido_paterno')?.value.trim() || '';
                const carnet = document.getElementById('form_ci')?.value.trim() || '';
                destino.textContent = `${[nombres, paterno].filter(Boolean).join(' ')} · Carnet ${carnet}`;
            }

            function abrirPaso(indice) {
                if (indice < 0 || indice >= ordenPasos.length || indice > indiceHabilitado) return;
                indiceActual = indice;
                const idActivo = ordenPasos[indice];
                document.querySelectorAll('[data-step-panel]').forEach(panel => {
                    const activo = Number(panel.dataset.stepPanel) === idActivo;
                    panel.hidden = !activo;
                    panel.classList.toggle('is-active', activo);
                });

                const etapa = configuracion.etapas[idActivo];
                const porcentaje = Math.round(((indice + 1) / ordenPasos.length) * 100);
                document.getElementById('public-step-label').textContent = `Paso ${indice + 1} de ${ordenPasos.length}`;
                document.getElementById('public-step-title').textContent = etapa.titulo;
                document.getElementById('public-progress-percent').textContent = `${porcentaje}%`;
                document.getElementById('public-progress-bar').style.width = `${porcentaje}%`;
                if (etapa.tipoSistema === 'confirmacion') actualizarConfirmacion();
                renderizarNavegacion();
                document.querySelector('.public-form-shell').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function continuar() {
                if (!validarPaso(indiceActual)) return;
                indiceHabilitado = Math.max(indiceHabilitado, indiceActual + 1);
                abrirPaso(indiceActual + 1);
            }

            cargarConfiguracion();
            crearPanelesPersonalizados();
            cargarPreguntas();
            abrirPaso(0);

            document.getElementById('public-verify-button').addEventListener('click', () => {
                if (!validarPaso(indiceActual)) return;
                document.getElementById('public-identity-result').hidden = false;
                window.setTimeout(continuar, 250);
            });
            document.querySelectorAll('[data-next-step], [data-dynamic-next]').forEach(boton => boton.addEventListener('click', continuar));
            document.querySelectorAll('[data-previous-step], [data-dynamic-previous]').forEach(boton => boton.addEventListener('click', () => abrirPaso(indiceActual - 1)));

            formulario.addEventListener('change', event => {
                if (event.target.matches('[data-required-checkbox] input')) {
                    event.target.closest('[data-required-checkbox]').querySelectorAll('input').forEach(casilla => casilla.setCustomValidity(''));
                }
            });

            formulario.addEventListener('submit', event => {
                event.preventDefault();
                if (!validarPaso(indiceActual)) return;
                const panelActivo = document.querySelector(`[data-step-panel="${ordenPasos[indiceActual]}"]`);
                const boton = panelActivo.querySelector('button[type="submit"]');
                boton.disabled = true;
                boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando...';
                window.setTimeout(() => {
                    mostrarMensaje('Formulario revisado. El guardado se habilitará cuando se implemente el backend.');
                    boton.disabled = false;
                    boton.innerHTML = '<i class="fa-regular fa-paper-plane"></i> Enviar preinscripción';
                }, 700);
            });
        });
    </script>
</body>
</html>
