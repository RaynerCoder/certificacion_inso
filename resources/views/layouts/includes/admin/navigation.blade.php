@php
    // Datos que se muestran junto a la campana de notificaciones.
    $usuarioCabecera = Auth::user();
    $usuarioCabecera?->loadMissing(['funcionario.cargos', 'roles', 'persona.empresa', 'persona.natural']);

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

    $tipoPersonaCabecera = match (true) {
        (bool) $usuarioCabecera?->persona?->empresa => 'Empresa',
        (bool) $usuarioCabecera?->persona?->natural => 'Persona natural',
        default => 'Usuario del sistema',
    };

    $nombrePerfilCabecera = $nombreFuncionarioCabecera !== ''
        ? $nombreFuncionarioCabecera
        : ($usuarioCabecera?->persona?->empresa?->razon_social
            ?: ($nombreNaturalCabecera !== '' ? $nombreNaturalCabecera : ($usuarioCabecera?->name ?? 'Usuario')));
    $cargoPerfilCabecera = $funcionarioCabecera
        ? $funcionarioCabecera->cargos->pluck('nombre')->filter()->unique()->implode(', ')
        : '';
    $detallePerfilCabecera = $cargoPerfilCabecera !== ''
        ? $cargoPerfilCabecera
        : $tipoPersonaCabecera;
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
                                'certificado.tramitador.natural',
                                'certificado.tramitador.empresa'
                            )
                            ->where('id_usuario_destino', Auth::id())
                            ->whereNull('fecha_visto')
                            ->where('estado', 'ACTIVO')
                        : null;
                    $notificacionesTramites = $consultaNotificacionesTramites
                        ? (clone $consultaNotificacionesTramites)->latest()->take(8)->get()
                        : collect();
                    $totalNotificacionesTramites = $consultaNotificacionesTramites ? $consultaNotificacionesTramites->count() : 0;

                    // Remitente visible en la campana: funcionario con cargo o solicitante como empresa/persona natural.
                    $datosRemitenteNotificacion = function ($usuario) {
                        if (!$usuario) {
                            return [
                                'nombre' => 'Sin remitente',
                                'detalle' => 'Sin dato',
                            ];
                        }

                        $usuario->loadMissing('funcionario.cargos', 'persona.empresa', 'persona.natural');

                        if ($usuario->funcionario) {
                            $nombreFuncionario = trim(implode(' ', array_filter([
                                $usuario->funcionario->nombres,
                                $usuario->funcionario->apellido_paterno,
                                $usuario->funcionario->apellido_materno,
                            ])));

                            return [
                                'nombre' => $nombreFuncionario ?: ($usuario->name ?: 'Sin funcionario'),
                                'detalle' => $usuario->funcionario->cargos?->pluck('nombre')->filter()->implode(', ') ?: 'Sin cargo',
                            ];
                        }

                        if ($usuario->persona?->empresa) {
                            return [
                                'nombre' => $usuario->persona->empresa->razon_social ?: 'Empresa sin razon social',
                                'detalle' => 'Empresa',
                            ];
                        }

                        if ($usuario->persona?->natural) {
                            $nombreNatural = trim(implode(' ', array_filter([
                                $usuario->persona->natural->nombres,
                                $usuario->persona->natural->apellido_paterno,
                                $usuario->persona->natural->apellido_materno,
                            ])));

                            return [
                                'nombre' => $nombreNatural ?: 'Persona natural sin nombre',
                                'detalle' => 'Persona natural',
                            ];
                        }

                        return [
                            'nombre' => $usuario->name ?: 'Sin remitente',
                            'detalle' => 'Sin persona vinculada',
                        ];
                    };
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
                        class="hidden absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <div>
                                <strong class="block text-sm font-black text-slate-800">Notificaciones</strong>
                                <span class="text-xs font-semibold text-slate-500">Trámites pendientes de atención</span>
                            </div>
                            <button type="button" id="btnLeerTodasTramites"
                                class="text-xs font-bold text-emerald-700 hover:text-emerald-900">
                                Marcar vistas
                            </button>
                        </div>

                        <div id="tramiteNotificationList" class="max-h-96 overflow-y-auto">
                            @forelse ($notificacionesTramites as $notificacion)
                                @php
                                    $certificadoNotificacion = $notificacion->certificado;
                                    $beneficiarioNotificacion = $certificadoNotificacion?->beneficiario;
                                    $nombreBeneficiarioNotificacion = 'Sin beneficiario';

                                    if ($beneficiarioNotificacion?->empresa) {
                                        $nombreBeneficiarioNotificacion = $beneficiarioNotificacion->empresa->razon_social;
                                    } elseif ($beneficiarioNotificacion?->natural) {
                                        $nombreBeneficiarioNotificacion = trim(implode(' ', array_filter([
                                            $beneficiarioNotificacion->natural->nombres,
                                            $beneficiarioNotificacion->natural->apellido_paterno,
                                            $beneficiarioNotificacion->natural->apellido_materno,
                                        ])));
                                    }

                                    // El boton abre el detalle correcto segun quien recibe la notificacion.
                                    // Solicitante/tramitador no debe ir a la bandeja interna de atencion.
                                    $esNotificacionSolicitante =
                                        $certificadoNotificacion
                                        && (
                                            (int) $certificadoNotificacion->beneficiario?->id_usuario === (int) Auth::id()
                                            || (int) $certificadoNotificacion->tramitador?->id_usuario === (int) Auth::id()
                                        );
                                    $urlNotificacionTramite = $certificadoNotificacion
                                        ? route('certificados_show', [
                                            'certificado' => $certificadoNotificacion,
                                            'bandeja' => $esNotificacionSolicitante ? 'enviadas' : 'recibidas',
                                        ])
                                        : ($esNotificacionSolicitante ? route('seguimientos_mis_solicitudes') : route('seguimientos_index'));
                                    $remitenteNotificacion = $datosRemitenteNotificacion($notificacion->usuarioEmisor);
                                    $fechaNotificacion = $notificacion->created_at?->format('d/m/Y H:i') ?? 'Sin fecha';
                                @endphp
                                <div class="tramite-notification-item border-b border-gray-100 px-4 py-3"
                                    data-id="{{ $notificacion->id }}"
                                    data-url="{{ $urlNotificacionTramite }}">
                                    <strong class="block text-sm font-black text-slate-800">
                                        {{ $notificacion->titulo }}
                                    </strong>
                                    <p class="mt-1 text-xs font-semibold text-slate-600">
                                        {{ $certificadoNotificacion?->codigo ?? '' }} -
                                        {{ $certificadoNotificacion?->tipoCertificado?->nombre ?? 'Tramite' }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Beneficiario: {{ $nombreBeneficiarioNotificacion }}
                                    </p>
                                    <p class="mt-1 text-xs font-semibold text-slate-600">
                                        Fecha: {{ $fechaNotificacion }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Envía: <span class="font-semibold text-slate-700">{{ $remitenteNotificacion['nombre'] }}</span>
                                        <span class="block">{{ $remitenteNotificacion['detalle'] }}</span>
                                    </p>
                                    <button type="button"
                                        class="tramite-notification-open mt-2 inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-700">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        Atender solicitud
                                    </button>
                                </div>
                            @empty
                                <div class="px-4 py-5 text-center text-sm font-semibold text-slate-500">
                                    Sin notificaciones nuevas.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <x-dropdown align="right" width="60">
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
                                    <span class="cert-topbar-profile-detail" title="{{ $detallePerfilCabecera }}">
                                        {{ $detallePerfilCabecera }}
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
                                <span class="mt-1 block text-xs font-bold text-emerald-700">
                                    {{ $detallePerfilCabecera }}
                                </span>
                                <span class="mt-1 block text-xs font-semibold text-slate-600">
                                    {{ $rolesPerfilCabecera }}
                                </span>
                                <span class="mt-1 block truncate text-xs font-semibold text-slate-500">
                                    {{ $correoPerfilCabecera }}
                                </span>
                            </div>

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
            return `
                <div class="tramite-notification-item border-b border-gray-100 px-4 py-3"
                    data-id="${escaparHtml(notificacion.id)}" data-url="${escaparHtml(notificacion.url || caja.dataset.indexUrl)}">
                    <strong class="block text-sm font-black text-slate-800">${escaparHtml(notificacion.titulo)}</strong>
                    <p class="mt-1 text-xs font-semibold text-slate-600">${escaparHtml(notificacion.codigo || '')} - ${escaparHtml(notificacion.tipo || 'Trámite')}</p>
                    <p class="text-xs text-slate-500">Beneficiario: ${escaparHtml(notificacion.beneficiario || 'Sin beneficiario')}</p>
                    <p class="mt-1 text-xs font-semibold text-slate-600">Fecha: ${escaparHtml(notificacion.fecha || 'Sin fecha')}</p>
                    <p class="text-xs text-slate-500">
                        Envía: <span class="font-semibold text-slate-700">${escaparHtml(notificacion.quien_envia || 'Sin remitente')}</span>
                        <span class="block">${escaparHtml(notificacion.quien_envia_detalle || 'Sin dato')}</span>
                    </p>
                    <button type="button"
                        class="tramite-notification-open mt-2 inline-flex items-center gap-2 rounded-md bg-emerald-600 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-700">
                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                        Atender solicitud
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
                : '<div class="px-4 py-5 text-center text-sm font-semibold text-slate-500">Sin notificaciones nuevas.</div>';

            if (datos.total > totalAnterior && window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    timer: 3500,
                    showConfirmButton: false,
                    icon: 'info',
                    title: 'Nueva solicitud de trámite'
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
