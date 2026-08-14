@php
    // Datos que se muestran junto a la campana de notificaciones.
    $usuarioCabecera = Auth::user();
    $usuarioCabecera?->loadMissing([
        'funcionario.cargos',
        'roles',
        'persona.empresa',
        'persona.natural',
    ]);

    $funcionarioCabecera = $usuarioCabecera?->funcionario;
    $nombreFuncionarioCabecera = $funcionarioCabecera
        ? trim(implode(' ', array_filter([
            $funcionarioCabecera->nombres,
            $funcionarioCabecera->apellido_paterno,
            $funcionarioCabecera->apellido_materno,
        ])))
        : '';

    $nombreNaturalCabecera = $usuarioCabecera?->persona?->natural
        ? trim(implode(' ', array_filter([
            $usuarioCabecera->persona->natural->nombres,
            $usuarioCabecera->persona->natural->apellido_paterno,
            $usuarioCabecera->persona->natural->apellido_materno,
        ])))
        : '';

    $relacionesEmpresarialesCabecera = $usuarioCabecera?->relacionesEmpresarialesParaTramites() ?? collect();
    $empresasVinculadasCabecera = $relacionesEmpresarialesCabecera
        ->groupBy('id_empresa')
        ->map(function ($relaciones) {
            $empresa = $relaciones->first()?->empresa;
            $roles = $relaciones->pluck('rol.slug')->filter()->unique()->values();
            $rolVisible = match (true) {
                $roles->contains('solicitante') && $roles->contains('tramitador')
                    => 'Representante legal y tramitador',
                $roles->contains('solicitante') => 'Representante legal',
                default => 'Tramitador',
            };

            return $empresa ? ['empresa' => $empresa, 'rol' => $rolVisible] : null;
        })
        ->filter()
        ->sortBy(fn ($vinculo) => $vinculo['empresa']->razon_social)
        ->values();
    $empresaVinculadaCabecera = $empresasVinculadasCabecera->first()['empresa'] ?? null;

    // Conserva la presentación de cuentas empresariales creadas antes del modelo de representantes.
    if (! $empresaVinculadaCabecera && ($empresaAnteriorCabecera = $usuarioCabecera?->empresaDeAccesoActiva())) {
        $empresaVinculadaCabecera = $empresaAnteriorCabecera;
        $empresasVinculadasCabecera = collect([[
            'empresa' => $empresaAnteriorCabecera,
            'rol' => 'Cuenta empresarial',
        ]]);
    }

    $cantidadEmpresasCabecera = $empresasVinculadasCabecera->count();
    $rolesEmpresarialesCabecera = $relacionesEmpresarialesCabecera
        ->pluck('rol.slug')
        ->filter()
        ->unique();
    $esRepresentanteLegalCabecera = $rolesEmpresarialesCabecera->contains('solicitante');
    $esTramitadorCabecera = ! $esRepresentanteLegalCabecera
        && $rolesEmpresarialesCabecera->contains('tramitador');

    $tipoPersonaCabecera = match (true) {
        $esRepresentanteLegalCabecera => 'Representante legal',
        $esTramitadorCabecera => 'Tramitador',
        (bool) $empresaVinculadaCabecera => 'Cuenta empresarial',
        (bool) $usuarioCabecera?->persona?->natural => 'Persona natural',
        default => 'Usuario del sistema',
    };

    $nombrePerfilCabecera = $nombreFuncionarioCabecera !== ''
        ? $nombreFuncionarioCabecera
        : ($esRepresentanteLegalCabecera && $empresaVinculadaCabecera
            ? $empresaVinculadaCabecera->razon_social
            : ($nombreNaturalCabecera !== ''
            ? $nombreNaturalCabecera
            : ($empresaVinculadaCabecera?->razon_social ?: ($usuarioCabecera?->name ?? 'Usuario'))));

    $cargoPerfilCabecera = $funcionarioCabecera
        ? $funcionarioCabecera->cargos->pluck('nombre')->filter()->unique()->implode(', ')
        : '';
    $detallePerfilCabecera = match (true) {
        $cargoPerfilCabecera !== '' => $cargoPerfilCabecera,
        $esRepresentanteLegalCabecera && $nombreNaturalCabecera !== '' => $nombreNaturalCabecera,
        $esTramitadorCabecera => sprintf(
            'Tramitador · %d %s',
            $cantidadEmpresasCabecera,
            $cantidadEmpresasCabecera === 1 ? 'empresa' : 'empresas'
        ),
        default => $tipoPersonaCabecera,
    };
    $esDetalleRepresentacionCabecera = $esRepresentanteLegalCabecera || $esTramitadorCabecera;
    $etiquetaDetallePerfilCabecera = $esRepresentanteLegalCabecera ? 'Representante legal' : '';
    $rolesPerfilCabecera = $usuarioCabecera?->roles->pluck('name')->filter()->unique()->implode(', ') ?? '';
    $rolesPerfilCabecera = $rolesPerfilCabecera !== '' ? $rolesPerfilCabecera : 'Sin rol asignado';
    $correoPerfilCabecera = $usuarioCabecera?->email ?? 'Sin correo registrado';
    $partesNombrePerfil = preg_split('/\s+/', trim($nombrePerfilCabecera));
    $inicialesPerfilCabecera = strtoupper(substr($partesNombrePerfil[0] ?? 'U', 0, 1) . substr($partesNombrePerfil[1] ?? '', 0, 1));
@endphp

<nav class="fixed top-0 z-50 w-full bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700">
    <div class="px-3 py-3 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
                <button data-drawer-target="logo-sidebar" data-drawer-toggle="logo-sidebar" aria-controls="logo-sidebar"
                    type="button"
                    class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg sm:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                    <span class="sr-only">Abrir menú lateral</span>
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                        </path>
                    </svg>
                </button>
                <a href="/" class="flex ms-2 md:me-24">
                    <img src="https://flowbite.com/docs/images/logo.svg" class="h-8 me-3" alt="FlowBite Logo" />
                    <span
                        class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap dark:text-white">CERTIFICADOR - INSO</span>
                </a>
            </div>
            <div class="cert-topbar-actions">
                @php
                    // Datos iniciales para que la campana aparezca con contenido desde la primera carga.
                    $tablaNotificacionesLista = \Illuminate\Support\Facades\Schema::hasTable('notificaciones_tramites');
                    $consultaNotificacionesTramites = $tablaNotificacionesLista
                        ? \App\Models\NotificacionTramite::query()
                            ->with(
                                'usuarioEmisor.funcionario.cargos',
                                'usuarioEmisor.persona.empresa',
                                'usuarioEmisor.persona.natural',
                                'certificado.tipoCertificado',
                                'certificado.beneficiario.natural',
                                'certificado.beneficiario.empresa',
                                'certificado.beneficiario.empresa.responsables.persona.natural',
                                'certificado.tramitador.natural',
                                'certificado.tramitador.empresa'
                            )
                            // El historial conserva las ultimas cinco, aunque ya hayan sido vistas.
                            ->where('id_usuario_destino', Auth::id())
                        : null;
                    $notificacionesTramites = $consultaNotificacionesTramites
                        ? (clone $consultaNotificacionesTramites)->latest()->take(5)->get()
                        : collect();
                    $totalNotificacionesTramites = $consultaNotificacionesTramites
                        ? (clone $consultaNotificacionesTramites)
                            ->whereNull('fecha_visto')
                            ->where('estado', 'ACTIVO')
                            ->count()
                        : 0;

                @endphp

                <div id="tramiteNotificationBox" class="relative"
                    data-url="{{ route('notificaciones_tramites') }}"
                    data-read-url="{{ route('notificaciones_tramites_leer', ['notificacion' => '__ID__']) }}"
                    data-read-all-url="{{ route('notificaciones_tramites_leer_todas') }}"
                    data-index-url="{{ route('seguimientos_index') }}">
                    <button type="button" id="btnTramiteNotifications"
                        class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-sm text-gray-600 transition hover:bg-gray-50 hover:text-emerald-700">
                        <i class="fa-regular fa-bell text-base"></i>
                        <span id="tramiteNotificationBadge"
                            class="{{ $totalNotificacionesTramites > 0 ? '' : 'hidden' }} absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-[10px] font-black leading-none text-white">
                            {{ $totalNotificacionesTramites }}
                        </span>
                    </button>

                    <div id="tramiteNotificationPanel"
                        class="cert-notification-panel hidden absolute right-0 z-50 mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                        <div class="cert-notification-header flex items-start justify-between gap-3 border-b border-gray-100 px-4 py-3">
                            <div class="cert-notification-heading">
                                <strong class="block text-sm font-black text-slate-800">Notificaciones</strong>
                                <span class="text-xs font-semibold text-slate-500">Últimas 5 notificaciones</span>
                                <div class="mt-1 flex flex-wrap gap-2 text-[10px] font-bold">
                                    <span class="rounded-full bg-red-100 px-2 py-0.5 text-red-700">Trámite</span>
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-blue-700">Tramitador</span>
                                </div>
                            </div>
                            <div class="cert-notification-actions flex shrink-0 items-center gap-2">
                                <button type="button" id="btnLeerTodasTramites"
                                    class="text-xs font-bold text-emerald-700 hover:text-emerald-900">
                                    Marcar vistas
                                </button>
                                <button type="button" id="btnCerrarNotificaciones"
                                    class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                                    aria-label="Cerrar notificaciones" title="Cerrar">
                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div id="tramiteNotificationList" class="cert-notification-list overflow-y-auto">
                            @forelse ($notificacionesTramites as $notificacion)
                                @php
                                    $certificadoNotificacion = $notificacion->certificado;
                                    $esValidacionTramitador = ! $certificadoNotificacion;
                                    $presentacionNotificacion = $notificacion->datosPresentacion();

                                    // El boton abre el detalle correcto segun quien recibe la notificacion.
                                    // Solicitante/tramitador no debe ir a la bandeja interna de atencion.
                                    $esNotificacionSolicitante =
                                        $certificadoNotificacion
                                        && (
                                            (int) $certificadoNotificacion->beneficiario?->id_usuario === (int) Auth::id()
                                            || (int) $certificadoNotificacion->tramitador?->id_usuario === (int) Auth::id()
                                        );
                                    $urlNotificacionTramite = $esValidacionTramitador
                                        ? route('tramitadores_index')
                                        : ($certificadoNotificacion
                                        ? route('certificados_show', [
                                            'certificado' => $certificadoNotificacion,
                                            'bandeja' => $esNotificacionSolicitante ? 'enviadas' : 'recibidas',
                                        ])
                                        : ($esNotificacionSolicitante ? route('seguimientos_mis_tramites_beneficiario') : route('seguimientos_index')));
                                    $fechaNotificacion = $notificacion->created_at?->format('d/m/Y H:i') ?? 'Sin fecha';
                                    $claseNotificacion = $esValidacionTramitador
                                        ? 'notificacion-tramitador'
                                        : 'border-l-4 border-l-red-500 bg-red-50/70';
                                    $claseBotonNotificacion = $esValidacionTramitador
                                        ? 'bg-blue-600 hover:bg-blue-700'
                                        : 'bg-red-600 hover:bg-red-700';
                                @endphp
                                <div class="tramite-notification-item border-b border-gray-100 px-4 py-3 {{ $claseNotificacion }}"
                                    data-id="{{ $notificacion->id }}"
                                    data-url="{{ $urlNotificacionTramite }}">
                                    <strong class="block text-sm font-black text-slate-800">
                                        {{ $notificacion->titulo }}
                                    </strong>
                                    @if ($esValidacionTramitador)
                                        <p class="mt-1 text-xs font-semibold text-slate-600">Validación de tramitador</p>
                                        <p class="text-xs text-slate-500">{{ $notificacion->mensaje }}</p>
                                    @else
                                        <p class="mt-1 text-xs font-semibold text-slate-600">
                                            {{ $certificadoNotificacion?->codigo ?? '' }} -
                                            {{ $certificadoNotificacion?->tipoCertificado?->nombre ?? 'Trámite' }}
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            Tipo: <span class="font-semibold text-slate-700">{{ $presentacionNotificacion['tipo_solicitante'] }}</span>
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            Solicitante: <span class="font-semibold text-slate-700">{{ $presentacionNotificacion['solicitante'] }}</span>
                                        </p>
                                    @endif
                                    <p class="mt-1 text-xs font-semibold text-slate-600">
                                        Fecha: {{ $fechaNotificacion }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Envía: <span class="font-semibold text-slate-700">{{ $presentacionNotificacion['enviado_por'] }}</span>
                                        <span class="block">Actúa como: {{ $presentacionNotificacion['actua_como'] }}</span>
                                    </p>
                                    <button type="button"
                                        class="tramite-notification-open mt-2 inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-black text-white {{ $claseBotonNotificacion }}">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        {{ $esValidacionTramitador ? 'Ver tramitadores' : 'Atender solicitud' }}
                                    </button>
                                </div>
                            @empty
                                <div class="px-4 py-5 text-center text-sm font-semibold text-slate-500">
                                    Sin notificaciones.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <x-dropdown align="right" width="60" dropdownClasses="cert-topbar-profile-menu">
                        <x-slot name="trigger">
                            <button type="button" class="cert-topbar-profile-trigger">
                                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                    <img class="cert-topbar-avatar"
                                        src="{{ $usuarioCabecera->profile_photo_url }}"
                                        alt="{{ $nombrePerfilCabecera }}" />
                                @else
                                    <span class="cert-topbar-avatar cert-topbar-avatar-initials">
                                        {{ $inicialesPerfilCabecera }}
                                    </span>
                                @endif

                                <span class="cert-topbar-profile-text">
                                    <span class="cert-topbar-profile-name" title="{{ $nombrePerfilCabecera }}">
                                        {{ $nombrePerfilCabecera }}
                                    </span>
                                    <span class="cert-topbar-profile-detail-line" title="{{ $detallePerfilCabecera }}">
                                        @if ($etiquetaDetallePerfilCabecera !== '')
                                            <span class="cert-topbar-profile-detail-label">
                                                {{ $etiquetaDetallePerfilCabecera }}:
                                            </span>
                                        @endif
                                        <span class="cert-topbar-profile-detail {{ $esDetalleRepresentacionCabecera ? 'is-representation' : '' }}">
                                            {{ $detallePerfilCabecera }}
                                        </span>
                                    </span>
                                    <span class="cert-topbar-profile-role" title="{{ $rolesPerfilCabecera }}">
                                        {{ $rolesPerfilCabecera }}
                                    </span>
                                </span>

                                <i class="fa-solid fa-chevron-down cert-topbar-profile-chevron"></i>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            {{-- Resumen visible para identificar rapidamente la cuenta activa. --}}
                            <div class="border-b border-gray-100 px-4 py-3">
                                <strong class="block text-sm font-black text-slate-800">
                                    {{ $nombrePerfilCabecera }}
                                </strong>
                                <span class="mt-1 block break-words text-xs font-bold text-slate-700">
                                    @if ($etiquetaDetallePerfilCabecera !== '')
                                        <span class="text-blue-700">{{ $etiquetaDetallePerfilCabecera }}:</span>
                                    @endif
                                    {{ $detallePerfilCabecera }}
                                </span>
                                <span class="mt-1 block text-xs font-semibold text-emerald-700">
                                    {{ $rolesPerfilCabecera }}
                                </span>
                                <span class="mt-1 block truncate text-xs font-semibold text-slate-500">
                                    {{ $correoPerfilCabecera }}
                                </span>
                            </div>

                            @if ($esTramitadorCabecera && $empresasVinculadasCabecera->isNotEmpty())
                                <div class="cert-topbar-companies">
                                    <span class="cert-topbar-companies-title">Empresas vinculadas</span>

                                    <div class="cert-topbar-companies-list">
                                        @foreach ($empresasVinculadasCabecera as $vinculoEmpresaCabecera)
                                            @php($empresaCabecera = $vinculoEmpresaCabecera['empresa'])
                                            <div class="cert-topbar-company-item">
                                                <span class="cert-topbar-company-icon">
                                                    <i class="fa-regular fa-building"></i>
                                                </span>
                                                <span class="cert-topbar-company-text">
                                                    <strong title="{{ $empresaCabecera->razon_social }}">
                                                        {{ $empresaCabecera->razon_social }}
                                                    </strong>
                                                    <small>{{ $vinculoEmpresaCabecera['rol'] }}</small>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="block px-4 py-2 text-xs font-black uppercase tracking-wide text-gray-400">
                                Cuenta del sistema
                            </div>

                            <x-dropdown-link href="{{ route('profile.show') }}">
                                <span class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-user text-slate-400"></i>
                                    Mi perfil
                                </span>
                            </x-dropdown-link>

                            @if (Laravel\Jetstream\Jetstream::hasApiFeatures())
                                <x-dropdown-link href="{{ route('api-tokens.index') }}">
                                    <span class="inline-flex items-center gap-2">
                                        <i class="fa-solid fa-key text-slate-400"></i>
                                        Tokens de API
                                    </span>
                                </x-dropdown-link>
                            @endif

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}" x-data>
                                @csrf

                                <x-dropdown-link href="{{ route('logout') }}"
                                         @click.prevent="$root.submit();">
                                    <span class="inline-flex items-center gap-2 text-red-600">
                                        <i class="fa-solid fa-right-from-bracket"></i>
                                        Cerrar sesión
                                    </span>
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const caja = document.getElementById('tramiteNotificationBox');

        if (!caja) {
            return;
        }

        const boton = document.getElementById('btnTramiteNotifications');
        const panel = document.getElementById('tramiteNotificationPanel');
        const lista = document.getElementById('tramiteNotificationList');
        const badge = document.getElementById('tramiteNotificationBadge');
        const botonLeerTodas = document.getElementById('btnLeerTodasTramites');
        const botonCerrar = document.getElementById('btnCerrarNotificaciones');
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        let totalAnterior = Number(badge?.textContent || 0);

        function escaparHtml(valor) {
            return String(valor ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Renderiza una notificacion individual con boton directo a la bandeja de solicitudes.
        function plantillaNotificacion(notificacion) {
            const referencia = [notificacion.codigo, notificacion.tipo].filter(Boolean).join(' - ');
            const esValidacionTramitador = notificacion.categoria === 'tramitador';
            const claseNotificacion = esValidacionTramitador
                ? 'notificacion-tramitador'
                : 'border-l-4 border-l-red-500 bg-red-50/70';
            const claseBoton = esValidacionTramitador
                ? 'bg-blue-600 hover:bg-blue-700'
                : 'bg-red-600 hover:bg-red-700';
            const datosSolicitante = esValidacionTramitador
                ? `<p class="text-xs text-slate-500">${escaparHtml(notificacion.etiqueta_relacion || 'Solicitud')}: ${escaparHtml(notificacion.beneficiario || 'Sin dato')}</p>`
                : `
                    <p class="text-xs text-slate-500">Tipo: <span class="font-semibold text-slate-700">${escaparHtml(notificacion.tipo_solicitante || 'Sin dato')}</span></p>
                    <p class="text-xs text-slate-500">Solicitante: <span class="font-semibold text-slate-700">${escaparHtml(notificacion.beneficiario || 'Sin dato')}</span></p>
                `;

            return `
                <div class="tramite-notification-item border-b border-gray-100 px-4 py-3 ${claseNotificacion}"
                    data-id="${escaparHtml(notificacion.id)}" data-url="${escaparHtml(notificacion.url || caja.dataset.indexUrl)}">
                    <strong class="block text-sm font-black text-slate-800">${escaparHtml(notificacion.titulo)}</strong>
                    <p class="mt-1 text-xs font-semibold text-slate-600">${escaparHtml(referencia || 'Notificación')}</p>
                    ${datosSolicitante}
                    <p class="mt-1 text-xs font-semibold text-slate-600">Fecha: ${escaparHtml(notificacion.fecha || 'Sin fecha')}</p>
                    <p class="text-xs text-slate-500">
                        Envía: <span class="font-semibold text-slate-700">${escaparHtml(notificacion.quien_envia || 'Sin remitente')}</span>
                        <span class="block">Actúa como: ${escaparHtml(notificacion.quien_envia_detalle || 'Sin dato')}</span>
                    </p>
                    <button type="button"
                        class="tramite-notification-open mt-2 inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-xs font-black text-white ${claseBoton}">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        ${escaparHtml(notificacion.accion || 'Atender solicitud')}
                    </button>
                </div>
            `;
        }

        // Consulta Laravel cada pocos segundos para simular tiempo real sin WebSockets.
        async function cargarNotificaciones() {
            const respuesta = await fetch(caja.dataset.url, {
                headers: {
                    'Accept': 'application/json'
                }
            });

            if (!respuesta.ok) {
                return;
            }

            const datos = await respuesta.json();
            badge.textContent = datos.total;
            badge.classList.toggle('hidden', datos.total === 0);

            lista.innerHTML = datos.notificaciones.length
                ? datos.notificaciones.map(plantillaNotificacion).join('')
                : '<div class="px-4 py-5 text-center text-sm font-semibold text-slate-500">Sin notificaciones.</div>';

            if (datos.total > totalAnterior && window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 3500,
                    showConfirmButton: false,
                    icon: 'info',
                    title: 'Nueva notificación'
                });
            }

            totalAnterior = datos.total;
        }

        // Marca una notificacion como vista antes de llevar al usuario a Solicitudes.
        async function marcarVista(id) {
            const url = caja.dataset.readUrl.replace('__ID__', id);

            await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            });
        }

        boton.addEventListener('click', () => panel.classList.toggle('hidden'));
        botonCerrar?.addEventListener('click', () => panel.classList.add('hidden'));

        lista.addEventListener('click', async (event) => {
            const botonAtender = event.target.closest('.tramite-notification-open');

            if (!botonAtender) {
                return;
            }

            const item = botonAtender.closest('.tramite-notification-item');
            await marcarVista(item.dataset.id);
            window.location.href = item.dataset.url || caja.dataset.indexUrl;
        });

        botonLeerTodas.addEventListener('click', async () => {
            await fetch(caja.dataset.readAllUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                }
            });

            cargarNotificaciones();
        });

        setInterval(cargarNotificaciones, 10000);
    });
</script>
