<x-admin-layout
    title="Configurar capacitación | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Capacitaciones', 'href' => route('capacitaciones_index')],
        ['name' => 'Configurar formulario'],
    ]">

    @include('capacitaciones.estilos')

    <div class="cap-page">
        <section class="cap-card cap-hero">
            <div class="cap-hero-copy">
                <span class="cap-hero-icon"><i class="fa-solid fa-list-check"></i></span>
                <div>
                    <h1 class="cap-title">Configurar capacitación y formulario</h1>
                    <p class="cap-description">Defina los pasos y las preguntas que verá el participante.</p>
                </div>
            </div>
            <div class="cap-actions">
                <a class="cap-button" href="{{ route('capacitaciones_index') }}"><i class="fa-solid fa-arrow-left"></i> Volver</a>
                <a class="cap-button" href="{{ route('capacitaciones_preinscripcion_publica') }}" target="_blank"><i class="fa-regular fa-eye"></i> Vista previa</a>
            </div>
        </section>

        <section class="cap-card">
            <header class="cap-section-head">
                <div>
                    <h2 class="cap-section-title">1. Datos de la capacitación</h2>
                    <p class="cap-description">Información demostrativa utilizada en la vista pública.</p>
                </div>
                <span class="cap-chip cap-chip-amber">Solo frontend</span>
            </header>
            <div class="cap-form-body">
                <div class="cap-form-grid">
                    <label class="cap-form-group cap-form-group-wide">
                        <span class="cap-form-label">Nombre de la capacitación</span>
                        <input class="cap-field" name="form_nombre_capacitacion" type="text" value="Seguridad y Salud en el Trabajo">
                    </label>
                    <label class="cap-form-group">
                        <span class="cap-form-label">Modalidad</span>
                        <select class="cap-field" name="form_modalidad"><option>Presencial</option><option>Virtual</option></select>
                    </label>
                    <label class="cap-form-group">
                        <span class="cap-form-label">Fecha</span>
                        <input class="cap-field" name="form_fecha" type="date" value="2026-09-15">
                    </label>
                    <label class="cap-form-group">
                        <span class="cap-form-label">Cupos</span>
                        <input class="cap-field" name="form_cupos" type="number" min="1" value="50">
                    </label>
                    <label class="cap-form-group">
                        <span class="cap-form-label">Lugar o enlace</span>
                        <input class="cap-field" name="form_lugar" type="text" value="Instalaciones del INSO">
                    </label>
                </div>
            </div>
        </section>

        <section class="cap-card cap-builder-card">
            <header class="cap-section-head">
                <div>
                    <h2 class="cap-section-title">2. Configurador del formulario público</h2>
                    <p class="cap-description">Los pasos también son configurables: puede agregarlos, renombrarlos y ordenarlos.</p>
                </div>
                <span class="cap-chip cap-chip-green"><i class="fa-solid fa-shoe-prints"></i> Formulario por pasos</span>
            </header>

            <div class="cap-builder-stepbar">
                <div class="cap-builder-steps" id="cap-builder-steps" aria-label="Pasos configurados"></div>
                <button class="cap-button cap-add-step" id="cap-add-step" type="button"><i class="fa-solid fa-plus"></i> Agregar paso</button>
            </div>

            <div class="cap-builder-layout">
                <aside class="cap-builder-palette">
                    <div class="cap-builder-panel-title"><strong>Campos del sistema</strong><small>Reutilizan información existente; no crean columnas nuevas</small></div>
                    <div class="cap-system-fields">
                        <button type="button" data-system-field="ci"><strong>Carnet de identidad</strong><small>naturals.ci</small></button>
                        <button type="button" data-system-field="complemento"><strong>Complemento</strong><small>naturals.complemento</small></button>
                        <button type="button" data-system-field="expedido"><strong>Expedido</strong><small>naturals.expedido</small></button>
                        <button type="button" data-system-field="nombres"><strong>Nombres</strong><small>naturals.nombres</small></button>
                        <button type="button" data-system-field="apellido_paterno"><strong>Apellido paterno</strong><small>naturals.apellido_paterno</small></button>
                        <button type="button" data-system-field="apellido_materno"><strong>Apellido materno</strong><small>naturals.apellido_materno</small></button>
                        <button type="button" data-system-field="apellido_casado"><strong>Apellido de casado</strong><small>naturals.apellido_casado</small></button>
                        <button type="button" data-system-field="fecha_nacimiento"><strong>Fecha de nacimiento</strong><small>naturals.fecha_nacimiento</small></button>
                        <button type="button" data-system-field="genero"><strong>Género</strong><small>naturals.genero</small></button>
                        <button type="button" data-system-field="ocupacion"><strong>Ocupación</strong><small>ocupaciones_cob.descripcion_ocupacion</small></button>
                        <button type="button" data-system-field="correo"><strong>Correo electrónico</strong><small>personas.correo</small></button>
                        <button type="button" data-system-field="domicilio"><strong>Domicilio</strong><small>personas.domicilio</small></button>
                        <button type="button" data-system-field="territorio"><strong>Territorio</strong><small>personas.id_territorio</small></button>
                        <button type="button" data-system-field="telefono"><strong>Teléfono</strong><small>telefonos.numero</small></button>
                    </div>

                    <div class="cap-builder-panel-title cap-custom-fields-title"><strong>Campos personalizados</strong><small>Preguntas propias de esta capacitación</small></div>
                    <div class="cap-builder-tools">
                        <button type="button" data-add-type="text"><i class="fa-solid fa-font"></i><span>Texto corto</span></button>
                        <button type="button" data-add-type="textarea"><i class="fa-solid fa-align-left"></i><span>Párrafo</span></button>
                        <button type="button" data-add-type="radio"><i class="fa-regular fa-circle-dot"></i><span>Opción múltiple</span></button>
                        <button type="button" data-add-type="checkbox"><i class="fa-regular fa-square-check"></i><span>Casillas</span></button>
                        <button type="button" data-add-type="select"><i class="fa-solid fa-list"></i><span>Lista</span></button>
                        <button type="button" data-add-type="number"><i class="fa-solid fa-hashtag"></i><span>Número</span></button>
                        <button type="button" data-add-type="date"><i class="fa-regular fa-calendar"></i><span>Fecha</span></button>
                        <button type="button" data-add-type="note"><i class="fa-regular fa-note-sticky"></i><span>Información</span></button>
                    </div>
                    <div class="cap-builder-tip"><i class="fa-regular fa-lightbulb"></i>El formulario debe conservar al menos un paso. Todo lo demás puede configurarse.</div>
                </aside>

                <section class="cap-builder-canvas">
                    <div class="cap-canvas-head">
                        <div>
                            <span class="cap-canvas-kicker" id="cap-canvas-kicker"></span>
                            <h3 id="cap-canvas-title"></h3>
                            <p id="cap-canvas-description"></p>
                        </div>
                        <div class="cap-actions">
                            <button class="cap-button" id="cap-rename-step" type="button"><i class="fa-solid fa-pen"></i> Renombrar</button>
                            <button class="cap-button" id="cap-move-step-left" type="button" title="Mover a la izquierda"><i class="fa-solid fa-arrow-left"></i></button>
                            <button class="cap-button" id="cap-move-step-right" type="button" title="Mover a la derecha"><i class="fa-solid fa-arrow-right"></i></button>
                            <button class="cap-button cap-button-danger" id="cap-delete-step" type="button" title="Eliminar paso"><i class="fa-regular fa-trash-can"></i></button>
                        </div>
                    </div>
                    <div class="cap-canvas-fields" id="cap-canvas-fields"></div>
                </section>

                <aside class="cap-builder-properties">
                    <div class="cap-builder-panel-title"><strong>Propiedades</strong><small id="cap-properties-help">Seleccione una pregunta</small></div>
                    <div class="cap-properties-empty" id="cap-properties-empty"><i class="fa-solid fa-arrow-pointer"></i><span>Seleccione una pregunta del lienzo.</span></div>
                    <div class="cap-properties-form" id="cap-properties-form" hidden>
                        <label class="cap-form-group"><span class="cap-form-label">Título</span><input class="cap-field" id="cap-property-label" name="form_config_titulo" type="text"></label>
                        <label class="cap-form-group"><span class="cap-form-label">Texto de ayuda</span><input class="cap-field" id="cap-property-help" name="form_config_ayuda" type="text"></label>
                        <label class="cap-form-group">
                            <span class="cap-form-label">Tipo de respuesta</span>
                            <select class="cap-field" id="cap-property-type" name="form_config_tipo">
                                <option value="text">Texto corto</option><option value="textarea">Párrafo</option>
                                <option value="radio">Opción múltiple</option><option value="checkbox">Casillas</option>
                                <option value="select">Lista</option><option value="number">Número</option>
                                <option value="date">Fecha</option><option value="note">Texto informativo</option>
                            </select>
                        </label>
                        <label class="cap-form-group" id="cap-property-options-group" hidden>
                            <span class="cap-form-label">Opciones <small>(una por línea)</small></span>
                            <textarea class="cap-field" id="cap-property-options" name="form_config_opciones" rows="4"></textarea>
                        </label>
                        <label class="cap-property-check">
                            <input id="cap-property-required" name="form_config_obligatorio" type="checkbox">
                            <span><strong>Respuesta obligatoria</strong><small>El participante debe completar el campo.</small></span>
                        </label>
                        <div class="cap-property-actions">
                            <button class="cap-button" id="cap-duplicate-field" type="button"><i class="fa-regular fa-copy"></i> Duplicar</button>
                            <button class="cap-button cap-button-danger" id="cap-delete-field" type="button"><i class="fa-regular fa-trash-can"></i> Quitar</button>
                        </div>
                    </div>
                </aside>
            </div>

            <footer class="cap-builder-footer">
                <div class="cap-url-row cap-builder-url">
                    <input class="cap-field" id="cap-public-url" type="text" value="{{ route('capacitaciones_preinscripcion_publica') }}" readonly>
                    <button class="cap-button" id="cap-copy-url" type="button"><i class="fa-regular fa-copy"></i></button>
                </div>
                <div class="cap-actions">
                    <a class="cap-button" href="{{ route('capacitaciones_preinscripcion_publica') }}" target="_blank"><i class="fa-regular fa-eye"></i> Abrir formulario</a>
                    <button class="cap-button cap-button-primary" id="cap-save-prototype" type="button"><i class="fa-regular fa-floppy-disk"></i> Guardar diseño</button>
                </div>
            </footer>
        </section>
        <div class="cap-prototype-message" id="cap-prototype-message" role="status" aria-live="polite"></div>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const tiposConOpciones = ['radio', 'checkbox', 'select'];
                const nombresTipo = { text: 'Texto corto', textarea: 'Párrafo', radio: 'Opción múltiple', checkbox: 'Casillas', select: 'Lista', number: 'Número', date: 'Fecha', note: 'Información' };
                const camposSistema = {
                    ci: { label: 'Carnet de identidad', source: 'naturals.ci', formName: 'form_ci', type: 'text', required: true },
                    complemento: { label: 'Complemento', source: 'naturals.complemento', formName: 'form_complemento', type: 'text', required: false },
                    expedido: { label: 'Expedido', source: 'naturals.expedido', formName: 'form_expedido', type: 'select', required: false, options: ['La Paz', 'Cochabamba', 'Santa Cruz', 'Oruro', 'Potosí', 'Chuquisaca', 'Tarija', 'Beni', 'Pando', 'Extranjero'], optionValues: ['LP', 'CB', 'SC', 'OR', 'PT', 'CH', 'TJ', 'BN', 'PD', 'EX'] },
                    nombres: { label: 'Nombres', source: 'naturals.nombres', formName: 'form_nombres', type: 'text', required: true },
                    apellido_paterno: { label: 'Apellido paterno', source: 'naturals.apellido_paterno', formName: 'form_apellido_paterno', type: 'text', required: true },
                    apellido_materno: { label: 'Apellido materno', source: 'naturals.apellido_materno', formName: 'form_apellido_materno', type: 'text', required: false },
                    apellido_casado: { label: 'Apellido de casado', source: 'naturals.apellido_casado', formName: 'form_apellido_casado', type: 'text', required: false },
                    fecha_nacimiento: { label: 'Fecha de nacimiento', source: 'naturals.fecha_nacimiento', formName: 'form_fecha_nacimiento', type: 'date', required: false },
                    genero: { label: 'Género', source: 'naturals.genero', formName: 'form_genero', type: 'select', required: true, options: ['Masculino', 'Femenino'], optionValues: ['1', '0'] },
                    ocupacion: { label: 'Ocupación', source: 'ocupaciones_cob.descripcion_ocupacion', formName: 'form_id_ocupacion', type: 'select', required: false, options: ['Seleccione una ocupación'] },
                    correo: { label: 'Correo electrónico', source: 'personas.correo', formName: 'form_correo', type: 'text', required: true },
                    domicilio: { label: 'Domicilio', source: 'personas.domicilio', formName: 'form_domicilio', type: 'text', required: false },
                    territorio: { label: 'Territorio', source: 'personas.id_territorio', formName: 'form_id_territorio', type: 'select', required: false, options: ['Seleccione un territorio'] },
                    telefono: { label: 'Teléfono', source: 'telefonos.numero', formName: 'form_telefono', type: 'text', required: false },
                };
                const etapas = {
                    100: {
                        id: 100,
                        titulo: 'Paso 1',
                        descripcion: 'Agregue las preguntas que necesita para este formulario.',
                        tipoSistema: 'personalizado',
                        protegida: false,
                        campos: [],
                    },
                };

                let ordenPasos = [100];
                let pasoActivo = 100;
                let campoSeleccionado = null;
                let siguienteCampoId = 1;
                let siguientePasoId = 101;

                const lienzo = document.getElementById('cap-canvas-fields');
                const formularioPropiedades = document.getElementById('cap-properties-form');
                const propiedadesVacias = document.getElementById('cap-properties-empty');
                const entradaTitulo = document.getElementById('cap-property-label');
                const entradaAyuda = document.getElementById('cap-property-help');
                const selectorTipo = document.getElementById('cap-property-type');
                const casillaObligatoria = document.getElementById('cap-property-required');
                const grupoOpciones = document.getElementById('cap-property-options-group');
                const entradaOpciones = document.getElementById('cap-property-options');

                function escaparHtml(texto) {
                    const elemento = document.createElement('div');
                    elemento.textContent = texto ?? '';
                    return elemento.innerHTML;
                }

                function mostrarMensaje(texto) {
                    const mensaje = document.getElementById('cap-prototype-message');
                    mensaje.textContent = texto;
                    mensaje.classList.add('is-visible');
                    window.setTimeout(() => mensaje.classList.remove('is-visible'), 3200);
                }

                function cargarDiseño() {
                    try {
                        const guardado = JSON.parse(localStorage.getItem('certificador_formulario_capacitacion_demo'));
                        if (guardado?.version === 3 && guardado.etapas && Array.isArray(guardado.ordenPasos)) {
                            Object.keys(etapas).forEach(id => delete etapas[id]);
                            Object.keys(guardado.etapas).forEach(id => etapas[id] = guardado.etapas[id]);
                            ordenPasos = guardado.ordenPasos.map(Number).filter(id => etapas[id]);
                        }
                        siguientePasoId = Math.max(100, ...Object.keys(etapas).map(Number)) + 1;
                        siguienteCampoId = Math.max(0, ...Object.values(etapas).flatMap(etapa => etapa.campos.map(campo => Number(campo.id) || 0))) + 1;
                    } catch (error) {
                        localStorage.removeItem('certificador_formulario_capacitacion_demo');
                    }
                }

                function ocultarPropiedades() {
                    campoSeleccionado = null;
                    formularioPropiedades.hidden = true;
                    propiedadesVacias.hidden = false;
                    document.getElementById('cap-properties-help').textContent = 'Seleccione una pregunta';
                }

                function renderizarPasos() {
                    const contenedor = document.getElementById('cap-builder-steps');
                    contenedor.innerHTML = ordenPasos.map((id, indice) => {
                        const etapa = etapas[id];
                        const total = etapa.campos.length;
                        return `<button class="cap-builder-step ${id === pasoActivo ? 'is-active' : ''}" type="button" data-step="${id}">
                            <span>${indice + 1}</span><strong>${escaparHtml(etapa.titulo)}</strong><small>${total} ${total === 1 ? 'campo' : 'campos'}</small></button>`;
                    }).join('');
                    contenedor.querySelectorAll('[data-step]').forEach(boton => boton.addEventListener('click', () => seleccionarPaso(Number(boton.dataset.step))));
                }

                function vistaCampo(campo) {
                    if (campo.type === 'textarea') return '<div class="cap-preview-control is-textarea"></div>';
                    if (tiposConOpciones.includes(campo.type)) {
                        const opciones = campo.options?.length ? campo.options : ['Opción 1', 'Opción 2'];
                        if (campo.type === 'select') return `<div class="cap-preview-control">${escaparHtml(opciones[0])}</div>`;
                        const clase = campo.type === 'checkbox' ? ' class="is-square"' : '';
                        return `<div class="cap-preview-options">${opciones.map(opcion => `<span${clase}></span> ${escaparHtml(opcion)}`).join(' ')}</div>`;
                    }
                    if (campo.type === 'note') return `<div class="cap-preview-note">${escaparHtml(campo.help || campo.label)}</div>`;
                    return `<div class="cap-preview-control">${nombresTipo[campo.type]}</div>`;
                }

                function renderizarLienzo() {
                    const etapa = etapas[pasoActivo];
                    const posicion = ordenPasos.indexOf(pasoActivo);
                    document.getElementById('cap-canvas-kicker').textContent = `Paso ${posicion + 1} de ${ordenPasos.length}`;
                    document.getElementById('cap-canvas-title').textContent = etapa.titulo;
                    document.getElementById('cap-canvas-description').textContent = etapa.descripcion;
                    document.getElementById('cap-move-step-left').disabled = posicion <= 0;
                    document.getElementById('cap-move-step-right').disabled = posicion >= ordenPasos.length - 1;
                    document.getElementById('cap-delete-step').disabled = ordenPasos.length === 1;

                    if (!etapa.campos.length) {
                        lienzo.innerHTML = '<div class="cap-canvas-empty"><i class="fa-regular fa-rectangle-list"></i><strong>Este paso está vacío</strong><span>Agregue un elemento desde el panel izquierdo.</span></div>';
                        return;
                    }
                    lienzo.innerHTML = etapa.campos.map(campo => `<button class="cap-canvas-field ${campo.id === campoSeleccionado ? 'is-selected' : ''}" type="button" data-field-id="${campo.id}">
                        <span class="cap-field-drag"><i class="fa-solid fa-grip-vertical"></i></span>
                        <span class="cap-canvas-field-body"><span class="cap-canvas-label">${escaparHtml(campo.label)}${campo.required ? ' <b>*</b>' : ''}</span>
                        ${campo.help ? `<span class="cap-canvas-help">${escaparHtml(campo.help)}</span>` : ''}${vistaCampo(campo)}</span>
                        <span class="cap-field-badge ${campo.locked ? 'is-locked' : ''}"><i class="fa-solid ${campo.locked ? 'fa-lock' : 'fa-pen'}"></i>${campo.locked ? 'Sistema' : 'Editable'}</span>
                    </button>`).join('');
                    lienzo.querySelectorAll('[data-field-id]').forEach(boton => boton.addEventListener('click', () => seleccionarCampo(Number(boton.dataset.fieldId))));
                }

                function seleccionarPaso(id) {
                    if (!etapas[id]) return;
                    pasoActivo = id;
                    ocultarPropiedades();
                    renderizarPasos();
                    renderizarLienzo();
                }

                function seleccionarCampo(id) {
                    campoSeleccionado = id;
                    const campo = etapas[pasoActivo].campos.find(item => item.id === id);
                    if (!campo) return;
                    propiedadesVacias.hidden = true;
                    formularioPropiedades.hidden = false;
                    entradaTitulo.value = campo.label;
                    entradaAyuda.value = campo.help || '';
                    selectorTipo.value = campo.type;
                    casillaObligatoria.checked = campo.required;
                    entradaOpciones.value = (campo.options || []).join('\n');
                    grupoOpciones.hidden = !tiposConOpciones.includes(campo.type);
                    entradaTitulo.disabled = campo.locked;
                    selectorTipo.disabled = campo.locked;
                    entradaOpciones.disabled = campo.locked;
                    entradaAyuda.disabled = false;
                    casillaObligatoria.disabled = false;
                    document.getElementById('cap-delete-field').disabled = false;
                    document.getElementById('cap-duplicate-field').disabled = campo.locked;
                    document.getElementById('cap-properties-help').textContent = campo.locked
                        ? `Dato reutilizado desde ${campo.source}`
                        : 'Campo personalizado editable';
                    renderizarLienzo();
                }

                function campoActual() {
                    return etapas[pasoActivo].campos.find(item => item.id === campoSeleccionado);
                }

                function agregarCampo(tipo) {
                    const campo = { id: siguienteCampoId++, label: tipo === 'note' ? 'Nueva indicación' : 'Nueva pregunta', help: '', type: tipo, required: false, locked: false, options: tiposConOpciones.includes(tipo) ? ['Opción 1', 'Opción 2'] : [] };
                    etapas[pasoActivo].campos.push(campo);
                    renderizarPasos();
                    seleccionarCampo(campo.id);
                }

                function agregarCampoSistema(clave) {
                    const definicion = camposSistema[clave];
                    if (!definicion) return;

                    const repetido = Object.values(etapas).some(etapa => etapa.campos.some(campo => campo.source === definicion.source));
                    if (repetido) {
                        mostrarMensaje('Ese campo del sistema ya fue agregado al formulario.');
                        return;
                    }

                    const campo = {
                        ...definicion,
                        id: siguienteCampoId++,
                        help: '',
                        locked: true,
                        system: true,
                        options: [...(definicion.options || [])],
                        optionValues: [...(definicion.optionValues || [])],
                    };
                    etapas[pasoActivo].campos.push(campo);
                    renderizarPasos();
                    seleccionarCampo(campo.id);
                }

                function moverPaso(direccion) {
                    const origen = ordenPasos.indexOf(pasoActivo);
                    const destino = origen + direccion;
                    if (destino < 0 || destino >= ordenPasos.length) return;
                    [ordenPasos[origen], ordenPasos[destino]] = [ordenPasos[destino], ordenPasos[origen]];
                    renderizarPasos();
                    renderizarLienzo();
                }

                document.querySelectorAll('[data-add-type]').forEach(boton => boton.addEventListener('click', () => agregarCampo(boton.dataset.addType)));
                document.querySelectorAll('[data-system-field]').forEach(boton => boton.addEventListener('click', () => agregarCampoSistema(boton.dataset.systemField)));
                document.getElementById('cap-add-step').addEventListener('click', () => {
                    const id = siguientePasoId++;
                    etapas[id] = { id, titulo: `Paso ${ordenPasos.length + 1}`, descripcion: 'Agregue las preguntas de esta etapa.', tipoSistema: 'personalizado', protegida: false, campos: [] };
                    ordenPasos.push(id);
                    seleccionarPaso(id);
                });
                document.getElementById('cap-rename-step').addEventListener('click', () => {
                    const nombre = window.prompt('Nombre del paso', etapas[pasoActivo].titulo);
                    if (!nombre?.trim()) return;
                    etapas[pasoActivo].titulo = nombre.trim();
                    renderizarPasos(); renderizarLienzo();
                });
                document.getElementById('cap-move-step-left').addEventListener('click', () => moverPaso(-1));
                document.getElementById('cap-move-step-right').addEventListener('click', () => moverPaso(1));
                document.getElementById('cap-delete-step').addEventListener('click', () => {
                    if (ordenPasos.length === 1) {
                        mostrarMensaje('El formulario debe conservar al menos un paso.');
                        return;
                    }
                    const indice = ordenPasos.indexOf(pasoActivo);
                    ordenPasos = ordenPasos.filter(id => id !== pasoActivo);
                    delete etapas[pasoActivo];
                    seleccionarPaso(ordenPasos[Math.max(0, indice - 1)]);
                });

                entradaTitulo.addEventListener('input', event => { const campo = campoActual(); if (campo && !campo.locked) { campo.label = event.target.value || 'Pregunta sin título'; renderizarLienzo(); } });
                entradaAyuda.addEventListener('input', event => { const campo = campoActual(); if (campo) { campo.help = event.target.value; renderizarLienzo(); } });
                selectorTipo.addEventListener('change', event => { const campo = campoActual(); if (!campo || campo.locked) return; campo.type = event.target.value; if (tiposConOpciones.includes(campo.type) && !campo.options?.length) campo.options = ['Opción 1', 'Opción 2']; grupoOpciones.hidden = !tiposConOpciones.includes(campo.type); entradaOpciones.value = (campo.options || []).join('\n'); renderizarLienzo(); });
                entradaOpciones.addEventListener('input', event => { const campo = campoActual(); if (campo && !campo.locked) { campo.options = event.target.value.split('\n').map(item => item.trim()).filter(Boolean); renderizarLienzo(); } });
                casillaObligatoria.addEventListener('change', event => { const campo = campoActual(); if (campo) { campo.required = event.target.checked; renderizarLienzo(); } });
                document.getElementById('cap-duplicate-field').addEventListener('click', () => { const campo = campoActual(); if (!campo || campo.locked) return; const copia = { ...campo, id: siguienteCampoId++, label: `${campo.label} (copia)`, locked: false, source: null, options: [...(campo.options || [])] }; etapas[pasoActivo].campos.push(copia); renderizarPasos(); seleccionarCampo(copia.id); });
                document.getElementById('cap-delete-field').addEventListener('click', () => { const campo = campoActual(); if (!campo) return; etapas[pasoActivo].campos = etapas[pasoActivo].campos.filter(item => item.id !== campo.id); ocultarPropiedades(); renderizarPasos(); renderizarLienzo(); });
                document.getElementById('cap-copy-url').addEventListener('click', async () => { try { await navigator.clipboard.writeText(document.getElementById('cap-public-url').value); mostrarMensaje('Enlace público copiado.'); } catch (error) { mostrarMensaje('Seleccione y copie el enlace manualmente.'); } });
                document.getElementById('cap-save-prototype').addEventListener('click', () => { localStorage.setItem('certificador_formulario_capacitacion_demo', JSON.stringify({ version: 3, ordenPasos, etapas })); mostrarMensaje('Pasos y preguntas guardados en este navegador.'); });

                cargarDiseño();
                renderizarPasos();
                renderizarLienzo();
            });
        </script>
    @endpush
</x-admin-layout>
