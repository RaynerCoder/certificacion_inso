@php
    // Nombre de la ruta actual. Se usa para pintar como activo el modulo donde esta el usuario.
    $rutaActual = Route::currentRouteName();

    // Crea una URL solo si la ruta existe; si no existe, deja "#" para no romper el menu.
    $href = fn(string $route, string $fallback = '#') => Route::has($route) ? route($route) : $fallback;

    // Marca activo un grupo de rutas que comparten prefijo, por ejemplo productos_* o roles_*.
    $activo = fn(array $prefixes) => collect($prefixes)->contains(
        fn($prefix) => $rutaActual && str_starts_with($rutaActual, $prefix),
    );

    // Bandeja actual del modulo Tramites:
    // - enviadas: tramites creados por el solicitante.
    // - recibidas: tramites que llegaron para atender.
    // - todos: consulta general de seguimiento.
    // En detalle o historial este dato puede llegar por query string (?bandeja=recibidas).
    $bandejaTramiteActual = request('bandeja');

    // Si la URL no trae ?bandeja=..., se deduce por la ruta para mantener activo el submenu correcto.
    if (!$bandejaTramiteActual && request()->routeIs('seguimientos_mis_solicitudes')) {
        $bandejaTramiteActual = 'enviadas';
    }

    if (!$bandejaTramiteActual && request()->routeIs('seguimientos_todos')) {
        $bandejaTramiteActual = 'todos';
    }

    if (
        !$bandejaTramiteActual &&
        request()->routeIs('seguimientos_index', 'seguimientos_show', 'seguimientos_tramite_historial')
    ) {
        $bandejaTramiteActual = 'recibidas';
    }

    // Rutas propias del modulo Tramites. Si el usuario esta en una de ellas, el menu queda resaltado.
    $estaEnRutaDeTramites = request()->routeIs(
        'seguimientos_create',
        'seguimientos_mis_solicitudes',
        'seguimientos_index',
        'seguimientos_todos',
        'seguimientos_show',
        'seguimientos_tramite_historial',
    );

    // Algunas pantallas de detalle usan certificados_show, pero siguen perteneciendo al flujo de Tramites.
    $estaEnDetalleDeTramite =
        request()->routeIs('certificados_show') &&
        in_array($bandejaTramiteActual, ['enviadas', 'recibidas', 'todos'], true);

    // Resultado final usado por el item principal "Tramites" del sidebar.
    $rutaTramiteActiva = $estaEnRutaDeTramites || $estaEnDetalleDeTramite;

    // Usuario autenticado usado para decidir que opciones del menu puede ver.
    $usuarioMenu = auth()->user();

    // Helper visual del menu: usa permisos dinamicos guardados en la base de datos.
    $puedeVerModulo = fn(string|array $permisos) => $usuarioMenu?->puede($permisos) ?? false;

    // Permisos granulares del modulo Tramites.
    // Cambiando estos permisos en roles/permisos cambia el menu sin tocar la vista.
    $permisosMenuTramites = [
        'seguimientos_tramite.iniciar',
        'seguimientos_tramite.enviados',
        'seguimientos_tramite.atender',
        'seguimientos_tramite.consulta_general',
    ];

    // Estructura del menú: Menú va primero, luego Configuración y después los módulos.
    $links = [
        [
            'name' => 'Menú',
            'description' => 'Panel principal',
            'icon' => 'fa-solid fa-chart-line',
            'href' => $href('admin_dashboard'),
            'active' => request()->routeIs('admin_dashboard'),
            'permission' => 'dashboard.ver',
        ],
        [
            'name' => 'Configuracion',
            'description' => 'Seguridad y ubicacion',
            'icon' => 'fa-solid fa-sliders',
            'active' => $activo(['territorios_', 'usuarios_', 'users_', 'roles_', 'permisos_', 'areas_', 'cargos_']),
            'permission' => [
                'territorios.ver',
                'usuarios.ver',
                'roles.gestionar',
                'permisos.ver',
                'areas.ver',
                'cargos.ver',
            ],
            'submenu' => [
                [
                    'name' => 'Territorios',
                    'icon' => 'fa-solid fa-location-dot',
                    'href' => $href('territorios_index'),
                    'active' => $activo(['territorios_']),
                    'permission' => 'territorios.ver',
                ],
                [
                    'name' => 'Usuarios',
                    'icon' => 'fa-solid fa-user-gear',
                    'href' => $href('usuarios_index'),
                    'active' => $activo(['usuarios_']),
                    'permission' => 'usuarios.ver',
                ],
                [
                    'name' => 'Roles',
                    'icon' => 'fa-solid fa-user-shield',
                    'href' => $href('roles_index'),
                    'active' => $activo(['roles_']),
                    'permission' => 'roles.gestionar',
                ],
                [
                    'name' => 'Permisos',
                    'icon' => 'fa-solid fa-key',
                    'href' => $href('permisos_index'),
                    'active' => $activo(['permisos_']),
                    'permission' => 'permisos.ver',
                ],
                [
                    'name' => 'Areas',
                    'icon' => 'fa-solid fa-sitemap',
                    'href' => $href('areas_index'),
                    'active' => $activo(['areas_']),
                    'permission' => 'areas.ver',
                ],
                [
                    'name' => 'Cargos',
                    'icon' => 'fa-solid fa-id-badge',
                    'href' => $href('cargos_index'),
                    'active' => $activo(['cargos_']),
                    'permission' => 'cargos.ver',
                ],
            ],
        ],
        [
            'name' => 'Personas y Empresas',
            'description' => 'Naturales, Jurídicas y Responsables',
            'icon' => 'fa-solid fa-users',
            'active' => $activo(['personas_', 'personas_naturales_', 'responsables_', 'tipos_empresas_']),
            'permission' => ['personas.ver', 'responsables.ver', 'tipos_empresas.ver'],
            'submenu' => [
                [
                    'name' => 'Solicitantes',
                    'icon' => 'fa-solid fa-list',
                    'href' => $href('personas_index'), // Enlace para entrar a Personas
                    'active' => $activo(['personas_index', 'persona_create', 'personas_show', 'personas_edit']),
                    'permission' => 'personas.ver',
                ],
                [
                    'name' => 'Responsables de Empresas',
                    'icon' => 'fa-solid fa-user-tie',
                    'href' => $href('responsables_index'),
                    'active' => $activo(['responsables_']),
                    'permission' => 'responsables.ver',
                ],
                [
                    'name' => 'Tipos de Empresa',
                    'icon' => 'fa-solid fa-building',
                    'href' => $href('tipos_empresas_index'),
                    'active' => $activo(['tipos_empresas_']),
                    'permission' => 'tipos_empresas.ver',
                ],
            ],
        ],
        [
            'name' => 'Productos',
            'description' => 'Productos registrados',
            'icon' => 'fa-solid fa-box',
            'href' => $href('productos_index'),
            'active' => $activo(['productos_']),
            'permission' => 'productos.ver',
        ],
        [
            // Modulo operativo: separa claramente lo que el usuario envia y lo que debe atender.
            'name' => 'Trámites',
            'description' => 'Enviar, revisar y atender',
            'icon' => 'fa-solid fa-file-lines',
            'active' => $rutaTramiteActiva,
            'permission' => $permisosMenuTramites,
            'submenu' => [
                [
                    'name' => 'Nuevo trámite',
                    'icon' => 'fa-solid fa-file-circle-plus',
                    'href' => $href('seguimientos_create'),
                    'active' => request()->routeIs('seguimientos_create'),
                    'permission' => 'seguimientos_tramite.iniciar',
                ],
                [
                    'name' => 'Mis trámites',
                    'icon' => 'fa-solid fa-paper-plane',
                    'href' => $href('seguimientos_mis_solicitudes'),
                    'active' =>
                        request()->routeIs('seguimientos_mis_solicitudes') ||
                        (request()->routeIs(
                            'certificados_show',
                            'seguimientos_show',
                            'seguimientos_tramite_historial',
                        ) &&
                            $bandejaTramiteActual === 'enviadas'),
                    'permission' => 'seguimientos_tramite.enviados',
                ],
                [
                    'name' => 'Trámites para atender',
                    'icon' => 'fa-solid fa-inbox',
                    'href' => $href('seguimientos_index'),
                    'active' =>
                        request()->routeIs('seguimientos_index') ||
                        (request()->routeIs(
                            'certificados_show',
                            'seguimientos_show',
                            'seguimientos_tramite_historial',
                        ) &&
                            $bandejaTramiteActual === 'recibidas'),
                    'permission' => 'seguimientos_tramite.atender',
                ],
                [
                    'name' => 'Seguimiento de Trámites',
                    'icon' => 'fa-solid fa-magnifying-glass-chart',
                    'href' => $href('seguimientos_todos'),
                    'active' =>
                        request()->routeIs('seguimientos_todos') ||
                        (request()->routeIs(
                            'certificados_show',
                            'seguimientos_show',
                            'seguimientos_tramite_historial',
                        ) &&
                            $bandejaTramiteActual === 'todos'),
                    'permission' => 'seguimientos_tramite.consulta_general',
                ],
            ],
        ],

        [
            'name' => 'Catálogos de Certificados',
            'description' => 'Tipos y requisitos',
            'icon' => 'fa-solid fa-file-signature',
            'active' => $activo(['tipos_certificados_', 'requisitos_', 'tipos_evidencias_']),
            'permission' => ['tipos_certificados.ver', 'requisitos.ver', 'tipos_evidencias.ver'],
            'submenu' => [
                [
                    'name' => 'Tipos de Certificado',
                    'icon' => 'fa-solid fa-certificate',
                    'href' => $href('tipos_certificados_index'),
                    'active' => $activo(['tipos_certificados_']),
                    'permission' => 'tipos_certificados.ver',
                ],
                [
                    'name' => 'Requisitos',
                    'icon' => 'fa-solid fa-list-check',
                    'href' => $href('requisitos_index'),
                    'active' => $activo(['requisitos_']),
                    'permission' => 'requisitos.ver',
                ],
                [
                    'name' => 'Tipos de Evidencia',
                    'icon' => 'fa-solid fa-file-circle-check',
                    'href' => $href('tipos_evidencias_index'),
                    'active' => $activo(['tipos_evidencias_']),
                    'permission' => 'tipos_evidencias.ver',
                ],
                // [
                //     'name' => 'Verificacion QR',
                //     'icon' => 'fa-solid fa-qrcode',
                //     'href' => '#',
                //     'disabled' => true,
                // ],
            ],
        ],
        [
            'name' => 'Gestión de Productos',
            'description' => 'Registros y catálogos',
            'icon' => 'fa-solid fa-boxes-stacked',
            'active' => $activo([
                'productos_',
                'tipos_productos_',
                'fabricantes_',
                'ingredientes_',
                'presentaciones_',
                'registros_',
                'aduanas_',
            ]),
            'permission' => [
                'productos.ver',
                'tipos_productos.ver',
                'fabricantes.ver',
                'ingredientes.ver',
                'presentaciones.ver',
                'registros.ver',
            ],
            'submenu' => [
                [
                    'name' => 'Productos',
                    'icon' => 'fa-solid fa-box',
                    'href' => $href('productos_index'),
                    'active' => $activo(['productos_']),
                    'permission' => 'productos.ver',
                ],
                [
                    'name' => 'Tipos de Producto',
                    'icon' => 'fa-solid fa-tags',
                    'href' => $href('tipos_productos_index'),
                    'active' => $activo(['tipos_productos_']),
                    'permission' => 'tipos_productos.ver',
                ],
                [
                    'name' => 'Fabricantes',
                    'icon' => 'fa-solid fa-industry',
                    'href' => $href('fabricantes_index'),
                    'active' => $activo(['fabricantes_']),
                    'permission' => 'fabricantes.ver',
                ],
                [
                    'name' => 'Ingredientes',
                    'icon' => 'fa-solid fa-flask',
                    'href' => '#',
                    'active' => $activo(['ingredientes_']),
                    'permission' => 'ingredientes.ver',
                    'disabled' => true,
                ],
                [
                    'name' => 'Presentaciones',
                    'icon' => 'fa-solid fa-box-open',
                    'href' => '#',
                    'active' => $activo(['presentaciones_']),
                    'permission' => 'presentaciones.ver',
                    'disabled' => true,
                ],
                [
                    'name' => 'Registros',
                    'icon' => 'fa-solid fa-clipboard-list',
                    'href' => '#',
                    'active' => $activo(['registros_']),
                    'permission' => 'registros.ver',
                    'disabled' => true,
                ],
                [
                    'name' => 'Aduanas',
                    'icon' => 'fa-solid fa-warehouse',
                    'href' => '#',
                    'active' => $activo(['aduanas_']),
                    'disabled' => true,
                ],
            ],
        ],
    ];
@endphp

{{-- Estilos locales: mejoran la lectura del menu sin cambiar el layout general. --}}
@include('layouts.includes.admin.estilo')

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform -translate-x-full sm:translate-x-0 cert-sidebar"
    data-sidebar-theme="dark" aria-label="Sidebar">
    <div class="cert-sidebar-scroll">
        <ul class="space-y-1 cert-sidebar-menu">
            @foreach ($links as $link)
                @continue(isset($link['permission']) && !$puedeVerModulo($link['permission']))

                <li class="cert-sidebar-section">
                    @if (isset($link['submenu']))
                        @php
                            // Cada submenu tiene un id unico para que Flowbite lo pueda abrir/cerrar.
                            $submenuId = 'submenu-' . Str::slug($link['name']);
                        @endphp

                        <button type="button" class="cert-menu-button {{ $link['active'] ? 'is-active' : '' }}"
                            aria-controls="{{ $submenuId }}" data-collapse-toggle="{{ $submenuId }}">
                            <span class="cert-menu-icon">
                                <i class="{{ $link['icon'] }}"></i>
                            </span>

                            <span class="cert-menu-text">
                                <span class="cert-menu-title">{{ $link['name'] }}</span>
                                <span class="cert-menu-description">{{ $link['description'] }}</span>
                            </span>

                            <i class="fa-solid fa-chevron-down cert-menu-chevron"></i>
                        </button>

                        <ul id="{{ $submenuId }}" class="cert-submenu {{ $link['active'] ? '' : 'hidden' }}">
                            @foreach ($link['submenu'] as $item)
                                @continue(isset($item['permission']) && !$puedeVerModulo($item['permission']))

                                <li>
                                    <a href="{{ $item['href'] }}"
                                        class="cert-submenu-link {{ !empty($item['active']) ? 'is-active' : '' }} {{ !empty($item['disabled']) ? 'is-disabled' : '' }}"
                                        @if (!empty($item['disabled'])) aria-disabled="true" onclick="return false;" @endif>
                                        <span class="cert-submenu-icon">
                                            <i class="{{ $item['icon'] }}"></i>
                                        </span>

                                        <span class="flex-1">{{ $item['name'] }}</span>

                                        @if (!empty($item['disabled']))
                                            <span class="cert-module-pill">Pronto</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a href="{{ $link['href'] }}"
                            class="cert-menu-link {{ $link['active'] ? 'is-active' : '' }} {{ !empty($link['disabled']) ? 'is-disabled' : '' }}"
                            @if (!empty($link['disabled'])) aria-disabled="true" onclick="return false;" @endif>
                            <span class="cert-menu-icon">
                                <i class="{{ $link['icon'] }}"></i>
                            </span>

                            <span class="cert-menu-text">
                                <span class="cert-menu-title">{{ $link['name'] }}</span>
                                <span class="cert-menu-description">{{ $link['description'] }}</span>
                            </span>

                            @if (!empty($link['disabled']))
                                <span class="cert-module-pill">Pronto</span>
                            @endif
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>

        {{-- Selector compacto de tema: queda abajo para no ocupar espacio del menu principal. --}}
        <div class="cert-sidebar-theme-panel">
            <div class="cert-sidebar-theme-title">
                <strong>Tema</strong>
                <span>Menu</span>
            </div>

            <div class="cert-sidebar-theme-options">
                <button type="button" class="cert-theme-option" data-sidebar-theme-option="dark" title="Tema oscuro">
                    <i class="fa-solid fa-moon"></i>
                    Oscuro
                </button>

                <button type="button" class="cert-theme-option" data-sidebar-theme-option="light" title="Tema claro">
                    <i class="fa-solid fa-sun"></i>
                    Claro
                </button>
            </div>
        </div>
    </div>
</aside>

{{-- Tema del menu: guarda la eleccion en el navegador y la restaura al volver. --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('logo-sidebar');
        const botonesTema = Array.from(document.querySelectorAll('[data-sidebar-theme-option]'));
        const claveTema = 'certificador.sidebar.theme';

        function aplicarTemaSidebar(tema) {
            const temaSeguro = tema === 'light' ? 'light' : 'dark';

            sidebar.dataset.sidebarTheme = temaSeguro;
            localStorage.setItem(claveTema, temaSeguro);

            botonesTema.forEach(boton => {
                boton.classList.toggle('is-selected', boton.dataset.sidebarThemeOption === temaSeguro);
            });
        }

        botonesTema.forEach(boton => {
            boton.addEventListener('click', () => aplicarTemaSidebar(boton.dataset.sidebarThemeOption));
        });

        aplicarTemaSidebar(localStorage.getItem(claveTema) || 'dark');
    });
</script>
