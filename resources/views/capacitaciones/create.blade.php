<x-admin-layout
    title="Nueva capacitación | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Capacitaciones', 'href' => route('capacitaciones_index')],
        ['name' => 'Nueva capacitación'],
    ]">

    @include('capacitaciones.estilos')

    <div class="cap-page">
        <section class="cap-card cap-hero">
            <div class="cap-hero-copy">
                <span class="cap-hero-icon"><i class="fa-solid fa-calendar-plus"></i></span>
                <div>
                    <h1 class="cap-title">Nueva capacitación</h1>
                    <p class="cap-description">
                        Esta pantalla corresponde a la creación de una capacitación. No es el formulario público de preinscripción.
                    </p>
                </div>
            </div>

            <a class="cap-button" href="{{ route('capacitaciones_index') }}">
                <i class="fa-solid fa-arrow-left"></i>
                Volver
            </a>
        </section>

        <section class="cap-card">
            <header class="cap-section-head">
                <div>
                    <h2 class="cap-section-title">Datos de la capacitación</h2>
                    <p class="cap-description">Vista del formulario interno para registrar la actividad.</p>
                </div>
            </header>

            {{--
                Los controles todavía no tienen name ni action porque la base de datos actual
                no contiene una tabla de capacitaciones. Así evitamos enviar campos inventados.
            --}}
            <div class="cap-form-body">
                <div class="cap-form-grid">
                    <label class="cap-form-group cap-form-group-wide">
                        <span class="cap-form-label">Nombre de la capacitación</span>
                        <input class="cap-field" type="text" placeholder="Ingrese el nombre">
                    </label>

                    <label class="cap-form-group">
                        <span class="cap-form-label">Modalidad</span>
                        <select class="cap-field">
                            <option value="">Seleccione</option>
                            <option>Presencial</option>
                            <option>Virtual</option>
                        </select>
                    </label>

                    <label class="cap-form-group">
                        <span class="cap-form-label">Fecha</span>
                        <input class="cap-field" type="date">
                    </label>

                    <label class="cap-form-group">
                        <span class="cap-form-label">Cupos</span>
                        <input class="cap-field" type="number" min="1" placeholder="Ej.: 50">
                    </label>

                    <label class="cap-form-group">
                        <span class="cap-form-label">Lugar o enlace</span>
                        <input class="cap-field" type="text" placeholder="Indique el lugar o enlace de acceso">
                    </label>

                    <label class="cap-form-group cap-form-group-wide">
                        <span class="cap-form-label">Descripción</span>
                        <textarea class="cap-field" rows="4" placeholder="Ingrese una descripción breve"></textarea>
                    </label>
                </div>

                <div class="cap-empty-note" style="margin: 18px 0 0;">
                    <i class="fa-solid fa-database"></i>
                    El guardado permanecerá deshabilitado hasta definir las tablas reales y sus relaciones.
                </div>
            </div>

            <footer class="cap-form-footer">
                <a class="cap-button" href="{{ route('capacitaciones_index') }}">Cancelar</a>
                <button class="cap-button cap-button-primary" type="button" disabled>
                    <i class="fa-solid fa-floppy-disk"></i>
                    Guardar capacitación
                </button>
            </footer>
        </section>

        <section class="cap-card">
            <header class="cap-section-head">
                <div>
                    <h2 class="cap-section-title">Formulario público de preinscripción</h2>
                    <p class="cap-description">
                        Configuración visual basada únicamente en columnas que ya existen en personas y naturals.
                    </p>
                </div>
                <a class="cap-button" href="{{ route('capacitaciones_preinscripcion_publica') }}" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    Abrir vista previa
                </a>
            </header>

            <div class="cap-config-grid">
                <div class="cap-config-panel">
                    <div class="cap-config-panel-head">
                        <h3 class="cap-section-title">Campos utilizados</h3>
                        <p class="cap-description">El prefijo form_ identifica la entrada; después se guarda en la columna indicada.</p>
                    </div>

                    <div class="cap-config-panel-body">
                        <div class="cap-field-list">
                            <div class="cap-field-item">
                                <strong>Carnet de identidad</strong>
                                <span class="cap-field-source">form_ci → naturals.ci</span>
                                <span class="cap-chip cap-chip-green">Obligatorio</span>
                            </div>
                            <div class="cap-field-item">
                                <strong>Complemento</strong>
                                <span class="cap-field-source">form_complemento → naturals.complemento</span>
                                <span class="cap-chip cap-chip-slate">Opcional</span>
                            </div>
                            <div class="cap-field-item">
                                <strong>Expedido</strong>
                                <span class="cap-field-source">form_expedido → naturals.expedido</span>
                                <span class="cap-chip cap-chip-slate">Si es nuevo</span>
                            </div>
                            <div class="cap-field-item">
                                <strong>Nombres y apellidos</strong>
                                <span class="cap-field-source">naturals.nombres / apellido_paterno / apellido_materno</span>
                                <span class="cap-chip cap-chip-slate">Si es nuevo</span>
                            </div>
                            <div class="cap-field-item">
                                <strong>Datos personales</strong>
                                <span class="cap-field-source">naturals.fecha_nacimiento / genero</span>
                                <span class="cap-chip cap-chip-slate">Si es nuevo</span>
                            </div>
                            <div class="cap-field-item">
                                <strong>Contacto y ubicación</strong>
                                <span class="cap-field-source">personas.correo / domicilio / id_territorio</span>
                                <span class="cap-chip cap-chip-slate">Si es nuevo</span>
                            </div>
                            <div class="cap-field-item">
                                <strong>Cuenta de acceso</strong>
                                <span class="cap-field-source">personas.id_usuario → users.id</span>
                                <span class="cap-chip cap-chip-amber">Al validar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cap-config-panel">
                    <div class="cap-config-panel-head">
                        <h3 class="cap-section-title">Enlace y funcionamiento</h3>
                        <p class="cap-description">Este enlace abre la vista pública actual del frontend.</p>
                    </div>

                    <div class="cap-config-panel-body">
                        <div class="cap-url-row">
                            <input class="cap-field" id="cap-public-url" type="text"
                                value="{{ route('capacitaciones_preinscripcion_publica') }}" readonly>
                            <button class="cap-button" id="cap-copy-url" type="button" title="Copiar enlace">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                            <a class="cap-button" href="{{ route('capacitaciones_preinscripcion_publica') }}" target="_blank">
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </div>

                        <div class="cap-preview-flow">
                            <div class="cap-preview-step">
                                <span class="cap-preview-step-number">1</span>
                                <div><strong>Verificar identidad</strong><span>Busca por ci y complemento en naturals.</span></div>
                            </div>
                            <div class="cap-preview-step">
                                <span class="cap-preview-step-number">2</span>
                                <div><strong>Completar datos faltantes</strong><span>Solo solicita información cuando la persona no existe.</span></div>
                            </div>
                            <div class="cap-preview-step">
                                <span class="cap-preview-step-number">3</span>
                                <div><strong>Enviar preinscripción</strong><span>La cuenta se relaciona mediante personas.id_usuario al validar.</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const botonCopiar = document.getElementById('cap-copy-url');
                const campoEnlace = document.getElementById('cap-public-url');

                botonCopiar?.addEventListener('click', async () => {
                    await navigator.clipboard.writeText(campoEnlace.value);
                    botonCopiar.innerHTML = '<i class="fa-solid fa-check"></i>';
                    window.setTimeout(() => {
                        botonCopiar.innerHTML = '<i class="fa-regular fa-copy"></i>';
                    }, 1200);
                });
            });
        </script>
    @endpush
</x-admin-layout>
