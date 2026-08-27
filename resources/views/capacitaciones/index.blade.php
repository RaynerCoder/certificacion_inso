<x-admin-layout
    title="Capacitaciones | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Capacitaciones'],
    ]">

    @include('capacitaciones.estilos')

    <div class="cap-page">
        <section class="cap-card cap-hero">
            <div class="cap-hero-copy">
                <span class="cap-hero-icon"><i class="fa-solid fa-chalkboard-user"></i></span>
                <div>
                    <h1 class="cap-title">Gestión de capacitaciones</h1>
                    <p class="cap-description">
                        Organice cursos presenciales o virtuales y publique su formulario de preinscripción.
                    </p>
                </div>
            </div>

            <div class="cap-actions">
                <a class="cap-button" href="{{ route('capacitaciones_preinscripcion_publica') }}" target="_blank">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    Ver formulario público
                </a>
                <a class="cap-button cap-button-primary" href="{{ route('capacitaciones_create') }}">
                    <i class="fa-solid fa-plus"></i>
                    Nueva capacitación
                </a>
            </div>
        </section>

        <section class="cap-stats" aria-label="Resumen de capacitaciones">
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-calendar-check"></i></span>
                <div><div class="cap-stat-value">3</div><div class="cap-stat-label">Capacitaciones activas</div></div>
            </article>
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-user-clock"></i></span>
                <div><div class="cap-stat-value">18</div><div class="cap-stat-label">Pendientes de validación</div></div>
            </article>
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-user-check"></i></span>
                <div><div class="cap-stat-value">42</div><div class="cap-stat-label">Inscripciones validadas</div></div>
            </article>
            <article class="cap-card cap-stat">
                <span class="cap-stat-icon"><i class="fa-solid fa-certificate"></i></span>
                <div><div class="cap-stat-value">0</div><div class="cap-stat-label">Certificados emitidos</div></div>
            </article>
        </section>

        <section class="cap-card">
            <header class="cap-section-head">
                <div>
                    <h2 class="cap-section-title">Capacitaciones registradas</h2>
                    <p class="cap-description">Vista preliminar del listado y sus accesos principales.</p>
                </div>
                <div class="cap-toolbar">
                    <select class="cap-field" aria-label="Filtrar por estado">
                        <option>Todas las modalidades</option>
                        <option>Presencial</option>
                        <option>Virtual</option>
                    </select>
                    <input class="cap-field" type="search" placeholder="Buscar capacitación..." aria-label="Buscar capacitación">
                </div>
            </header>

            <div class="cap-table-wrap">
                <table class="cap-table">
                    <thead>
                        <tr>
                            <th>Capacitación</th>
                            <th>Modalidad</th>
                            <th>Fecha</th>
                            <th>Cupos</th>
                            <th>Preinscritos</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>Seguridad y Salud en el Trabajo</strong><small>Responsable: Unidad de Capacitación</small></td>
                            <td><span class="cap-chip cap-chip-green"><i class="fa-solid fa-building"></i> Presencial</span></td>
                            <td>15/09/2026</td>
                            <td>50</td>
                            <td><strong>24</strong><small>8 pendientes</small></td>
                            <td><span class="cap-chip cap-chip-green">Publicada</span></td>
                            <td>
                                <div class="cap-actions">
                                    <a class="cap-button" href="{{ route('capacitaciones_preinscripciones_index') }}">
                                        <i class="fa-solid fa-users"></i> Preinscritos
                                    </a>
                                    <a class="cap-button" href="{{ route('capacitaciones_preinscripcion_publica') }}" target="_blank" title="Abrir enlace público">
                                        <i class="fa-solid fa-link"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Prevención de Riesgos Laborales</strong><small>Responsable: Área Técnica</small></td>
                            <td><span class="cap-chip cap-chip-slate"><i class="fa-solid fa-video"></i> Virtual</span></td>
                            <td>28/09/2026</td>
                            <td>100</td>
                            <td><strong>36</strong><small>10 pendientes</small></td>
                            <td><span class="cap-chip cap-chip-green">Publicada</span></td>
                            <td>
                                <div class="cap-actions">
                                    <a class="cap-button" href="{{ route('capacitaciones_preinscripciones_index') }}">
                                        <i class="fa-solid fa-users"></i> Preinscritos
                                    </a>
                                    <button class="cap-button" type="button" title="Enlace público"><i class="fa-solid fa-link"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Primeros Auxilios en el Trabajo</strong><small>Responsable: Unidad de Salud</small></td>
                            <td><span class="cap-chip cap-chip-green"><i class="fa-solid fa-building"></i> Presencial</span></td>
                            <td>10/10/2026</td>
                            <td>40</td>
                            <td><strong>0</strong><small>Sin registros</small></td>
                            <td><span class="cap-chip cap-chip-amber">Borrador</span></td>
                            <td>
                                <button class="cap-button" type="button"><i class="fa-solid fa-pen"></i> Editar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cap-empty-note">
                <i class="fa-solid fa-circle-info"></i>
                Los datos mostrados son demostrativos. Esta etapa incorpora solamente el diseño del módulo.
            </div>
        </section>
    </div>
</x-admin-layout>
