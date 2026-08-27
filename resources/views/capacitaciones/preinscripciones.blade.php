<x-admin-layout
    title="Preinscripciones | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Capacitaciones', 'href' => route('capacitaciones_index')],
        ['name' => 'Preinscripciones'],
    ]">

    @include('capacitaciones.estilos')

    <div class="cap-page">
        <section class="cap-card cap-hero">
            <div class="cap-hero-copy">
                <span class="cap-hero-icon"><i class="fa-solid fa-user-check"></i></span>
                <div>
                    <h1 class="cap-title">Preinscripciones recibidas</h1>
                    <p class="cap-description">
                        Revise la identidad antes de validar la inscripción y habilitar una cuenta de acceso.
                    </p>
                </div>
            </div>
            <div class="cap-actions">
                <a class="cap-button" href="{{ route('capacitaciones_index') }}">
                    <i class="fa-solid fa-arrow-left"></i> Volver a capacitaciones
                </a>
            </div>
        </section>

        <section class="cap-stats" aria-label="Resumen de preinscripciones">
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-inbox"></i></span>
                <div><div class="cap-stat-value">24</div><div class="cap-stat-label">Preinscripciones recibidas</div></div>
            </article>
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-clock"></i></span>
                <div><div class="cap-stat-value">8</div><div class="cap-stat-label">Pendientes de revisión</div></div>
            </article>
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-circle-check"></i></span>
                <div><div class="cap-stat-value">14</div><div class="cap-stat-label">Validadas</div></div>
            </article>
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
                <div><div class="cap-stat-value">2</div><div class="cap-stat-label">Observadas</div></div>
            </article>
        </section>

        <section class="cap-card">
            <header class="cap-section-head">
                <div>
                    <h2 class="cap-section-title">Seguridad y Salud en el Trabajo</h2>
                    <p class="cap-description">Presencial · 15 de septiembre de 2026 · 50 cupos</p>
                </div>
                <div class="cap-toolbar">
                    <select class="cap-field" aria-label="Filtrar preinscripciones">
                        <option>Pendientes</option>
                        <option>Validadas</option>
                        <option>Observadas</option>
                        <option>Todas</option>
                    </select>
                    <input class="cap-field" type="search" placeholder="Buscar por carnet o nombre..." aria-label="Buscar preinscripción">
                </div>
            </header>

            <div class="cap-table-wrap">
                <table class="cap-table">
                    <thead>
                        <tr>
                            <th>Participante</th>
                            <th>Carnet</th>
                            <th>Correo</th>
                            <th>Cuenta</th>
                            <th>Enviado</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>María Alejandra Rojas</strong><small>Persona encontrada en el sistema</small></td>
                            <td>******45 LP</td>
                            <td>maria.rojas@correo.com</td>
                            <td><span class="cap-chip cap-chip-amber">Sin cuenta</span></td>
                            <td>26/08/2026<br><small>09:42</small></td>
                            <td><span class="cap-chip cap-chip-amber">Pendiente</span></td>
                            <td>
                                <button class="cap-button cap-button-primary" type="button" data-open-review>
                                    <i class="fa-solid fa-magnifying-glass"></i> Revisar
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Carlos Fernando Lima</strong><small>Ya cuenta con acceso</small></td>
                            <td>******81 CB</td>
                            <td>carlos.lima@correo.com</td>
                            <td><span class="cap-chip cap-chip-green">Cuenta activa</span></td>
                            <td>26/08/2026<br><small>08:15</small></td>
                            <td><span class="cap-chip cap-chip-amber">Pendiente</span></td>
                            <td>
                                <button class="cap-button" type="button" data-open-review>
                                    <i class="fa-solid fa-magnifying-glass"></i> Revisar
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Andrea P. Salvatierra</strong><small>Registro nuevo</small></td>
                            <td>******12 SC</td>
                            <td>andrea.salvatierra@correo.com</td>
                            <td><span class="cap-chip cap-chip-amber">Sin cuenta</span></td>
                            <td>25/08/2026<br><small>16:30</small></td>
                            <td><span class="cap-chip cap-chip-red">Observada</span></td>
                            <td>
                                <button class="cap-button" type="button" data-open-review>
                                    <i class="fa-solid fa-eye"></i> Ver
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cap-empty-note">
                <i class="fa-solid fa-shield-halved"></i>
                Al validar se reutilizará la persona existente. La cuenta solo se habilitará si todavía no tiene una.
            </div>
        </section>
    </div>

    <div class="cap-modal-backdrop" id="review-modal" aria-hidden="true">
        <section class="cap-modal" role="dialog" aria-modal="true" aria-labelledby="review-title">
            <header class="cap-modal-head">
                <div>
                    <h2 class="cap-section-title" id="review-title">Revisar preinscripción</h2>
                    <p class="cap-description">Confirme los datos antes de habilitar el acceso.</p>
                </div>
                <button class="cap-button" type="button" data-close-review aria-label="Cerrar">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <div class="cap-modal-body">
                <div class="cap-detail-grid">
                    <div class="cap-detail"><span>Participante</span><strong>María Alejandra Rojas</strong></div>
                    <div class="cap-detail"><span>Carnet</span><strong>******45 LP</strong></div>
                    <div class="cap-detail"><span>Correo de acceso</span><strong>maria.rojas@correo.com</strong></div>
                    <div class="cap-detail"><span>Estado de cuenta</span><strong>Sin cuenta de usuario</strong></div>
                    <div class="cap-detail"><span>Capacitación</span><strong>Seguridad y Salud en el Trabajo</strong></div>
                    <div class="cap-detail"><span>Inscripciones anteriores</span><strong>1 capacitación diferente</strong></div>
                </div>

                <div class="cap-empty-note" style="margin: 16px 0 0;">
                    <i class="fa-solid fa-circle-check"></i>
                    No existe otra inscripción de esta persona para la capacitación seleccionada.
                </div>

                <label style="display:flex; gap:10px; margin-top:16px; color:#334155; font-size:13px; line-height:1.45;">
                    <input type="checkbox" style="margin-top:2px; color:#059669; border-radius:4px;">
                    <span>Confirmo que revisé el carnet, el correo y la información registrada por la persona.</span>
                </label>
            </div>

            <footer class="cap-modal-footer">
                <button class="cap-button cap-button-danger" type="button" data-close-review>
                    <i class="fa-solid fa-comment-dots"></i> Observar
                </button>
                <button class="cap-button cap-button-primary" type="button" data-prototype-action>
                    <i class="fa-solid fa-user-check"></i> Validar y habilitar acceso
                </button>
            </footer>
        </section>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('review-modal');
                const abrirModal = document.querySelectorAll('[data-open-review]');
                const cerrarModal = document.querySelectorAll('[data-close-review]');

                function cambiarModal(abierto) {
                    modal.classList.toggle('is-open', abierto);
                    modal.setAttribute('aria-hidden', abierto ? 'false' : 'true');
                    document.body.style.overflow = abierto ? 'hidden' : '';
                }

                abrirModal.forEach(boton => boton.addEventListener('click', () => cambiarModal(true)));
                cerrarModal.forEach(boton => boton.addEventListener('click', () => cambiarModal(false)));

                modal.addEventListener('click', event => {
                    if (event.target === modal) cambiarModal(false);
                });

                // La acción solo confirma el comportamiento visual del prototipo.
                document.querySelector('[data-prototype-action]').addEventListener('click', () => {
                    cambiarModal(false);
                    Swal.fire({
                        icon: 'info',
                        title: 'Vista de diseño',
                        text: 'La validación y la creación de la cuenta se conectarán en la etapa de backend.',
                        confirmButtonColor: '#059669',
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>
