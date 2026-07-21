<x-admin-layout
    title="Menú | Sistema Certificador"
    :breadcrumbs="[
        ['name' => 'Menú', 'href' => route('admin_dashboard')],
        ['name' => 'Inicio'],
    ]">

    @php
        $resumenInicio = $resumenInicio ?? [
            'es_usuario_externo' => false,
            'titulo' => 'Resumen institucional',
            'detalle' => 'Vista general de los trámites registrados en el sistema.',
            'total' => 0,
            'en_revision' => 0,
            'observados' => 0,
            'finalizados' => 0,
        ];

        $ruta = fn (string $nombre) => \Illuminate\Support\Facades\Route::has($nombre) ? route($nombre) : '#';

        $modulosPrincipales = [
            [
                'titulo' => 'Nuevo trámite',
                'detalle' => 'Registrar una solicitud y enviar sus requisitos iniciales.',
                'icono' => 'fa-regular fa-file-lines',
                'color' => 'verde',
                'permiso' => 'seguimientos_tramite.iniciar',
                'ruta' => $ruta('seguimientos_create'),
            ],
            [
                'titulo' => 'Mis trámites',
                'detalle' => 'Consultar solicitudes enviadas, observaciones y seguimiento.',
                'icono' => 'fa-solid fa-paper-plane',
                'color' => 'azul',
                'permiso' => 'seguimientos_tramite.enviados',
                'ruta' => $ruta('seguimientos_mis_tramites_beneficiario'),
            ],
            [
                'titulo' => 'Trámites por atender',
                'detalle' => 'Revisar solicitudes recibidas, derivar o guardar revisión técnica.',
                'icono' => 'fa-solid fa-inbox',
                'color' => 'ambar',
                'permiso' => 'seguimientos_tramite.atender',
                'ruta' => $ruta('seguimientos_index'),
            ],
            [
                'titulo' => 'Seguimiento general',
                'detalle' => 'Consultar el estado y ubicación de los trámites institucionales.',
                'icono' => 'fa-solid fa-route',
                'color' => 'morado',
                'permiso' => 'seguimientos_tramite.consulta_general',
                'ruta' => $ruta('seguimientos_todos'),
            ],
        ];

        $catalogos = [
            ['titulo' => 'Personas y empresas', 'permiso' => 'personas.ver', 'ruta' => $ruta('personas_index')],
            ['titulo' => 'Productos', 'permiso' => 'productos.ver', 'ruta' => $ruta('productos_index')],
            ['titulo' => 'Tipos de certificado', 'permiso' => 'tipos_certificados.ver', 'ruta' => $ruta('tipos_certificados_index')],
            ['titulo' => 'Requisitos', 'permiso' => 'requisitos.ver', 'ruta' => $ruta('requisitos_index')],
            ['titulo' => 'Plantillas', 'permiso' => 'plantillas_certificados.ver', 'ruta' => $ruta('certificados_plantillas_index')],
            ['titulo' => 'Pagos', 'permiso' => 'pagos.ver', 'ruta' => $ruta('pagos_index')],
        ];
    @endphp

    <style>
        .inicio-panel {
            display: grid;
            gap: 16px;
        }

        .inicio-hero {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr) auto;
            padding: 18px;
        }

        .inicio-kicker {
            color: #047857;
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 4px;
            text-transform: uppercase;
        }

        .inicio-title {
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
            margin: 0;
        }

        .inicio-text {
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
            margin: 6px 0 0;
            max-width: 760px;
        }

        .inicio-user-box {
            align-self: center;
            background: #ecfdf5;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            color: #065f46;
            font-size: 13px;
            font-weight: 800;
            min-width: 210px;
            padding: 12px 14px;
        }

        .inicio-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .inicio-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            color: inherit;
            display: block;
            min-height: 142px;
            padding: 16px;
            text-decoration: none;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
        }

        .inicio-card:hover {
            border-color: #94a3b8;
            box-shadow: 0 10px 22px rgba(15, 23, 42, .08);
            transform: translateY(-1px);
        }

        .inicio-card-icon {
            align-items: center;
            border-radius: 9px;
            display: inline-flex;
            height: 38px;
            justify-content: center;
            margin-bottom: 12px;
            width: 38px;
        }

        .inicio-card.is-verde .inicio-card-icon { background: #d1fae5; color: #047857; }
        .inicio-card.is-azul .inicio-card-icon { background: #dbeafe; color: #1d4ed8; }
        .inicio-card.is-ambar .inicio-card-icon { background: #fef3c7; color: #b45309; }
        .inicio-card.is-morado .inicio-card-icon { background: #ede9fe; color: #6d28d9; }

        .inicio-card-title {
            color: #0f172a;
            font-size: 15px;
            font-weight: 900;
            margin: 0;
        }

        .inicio-card-text {
            color: #64748b;
            font-size: 12px;
            line-height: 1.45;
            margin: 5px 0 0;
        }

        .inicio-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
        }

        .inicio-section-head {
            align-items: center;
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .inicio-section-title {
            color: #0f172a;
            font-size: 16px;
            font-weight: 900;
            margin: 0;
        }

        .inicio-section-note {
            color: #64748b;
            font-size: 12px;
            margin: 2px 0 0;
        }

        .inicio-link-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .inicio-link {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            color: #0f172a;
            display: flex;
            font-size: 13px;
            font-weight: 800;
            justify-content: space-between;
            padding: 11px 12px;
            text-decoration: none;
        }

        .inicio-link:hover {
            background: #ecfdf5;
            border-color: #86efac;
            color: #065f46;
        }

        .inicio-stats {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .inicio-stat {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 12px;
        }

        .inicio-stat-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 800;
            margin: 0 0 5px;
        }

        .inicio-stat-value {
            color: #0f172a;
            font-size: 24px;
            font-weight: 900;
            line-height: 1;
        }

        @media (max-width: 1180px) {
            .inicio-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .inicio-link-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .inicio-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 720px) {
            .inicio-hero { grid-template-columns: 1fr; }
            .inicio-grid,
            .inicio-link-grid { grid-template-columns: 1fr; }
            .inicio-stats { grid-template-columns: 1fr; }
        }
    </style>

    <section class="inicio-panel">
        <div class="inicio-hero">
            <div>
                <p class="inicio-kicker">Sistema certificador INSO</p>
                <h1 class="inicio-title">Menú principal</h1>
                <p class="inicio-text">
                    Ingrese rápido a las tareas principales del sistema: iniciar trámites, revisar solicitudes,
                    consultar seguimiento y mantener los catálogos necesarios para la certificación.
                </p>
            </div>
            <div class="inicio-user-box">
                {{ auth()->user()?->name ?? 'Usuario del sistema' }}
            </div>
        </div>

        <div class="inicio-section">
            <div class="inicio-section-head">
                <div>
                    <h2 class="inicio-section-title">{{ $resumenInicio['titulo'] }}</h2>
                    <p class="inicio-section-note">{{ $resumenInicio['detalle'] }}</p>
                </div>
            </div>

            <div class="inicio-stats">
                <div class="inicio-stat">
                    <p class="inicio-stat-label">Total de trámites</p>
                    <div class="inicio-stat-value">{{ $resumenInicio['total'] }}</div>
                </div>
                <div class="inicio-stat">
                    <p class="inicio-stat-label">En revisión</p>
                    <div class="inicio-stat-value">{{ $resumenInicio['en_revision'] }}</div>
                </div>
                <div class="inicio-stat">
                    <p class="inicio-stat-label">Observados</p>
                    <div class="inicio-stat-value">{{ $resumenInicio['observados'] }}</div>
                </div>
                <div class="inicio-stat">
                    <p class="inicio-stat-label">Finalizados</p>
                    <div class="inicio-stat-value">{{ $resumenInicio['finalizados'] }}</div>
                </div>
            </div>
        </div>

        <div class="inicio-grid">
            @foreach ($modulosPrincipales as $modulo)
                @permiso($modulo['permiso'])
                    <a class="inicio-card is-{{ $modulo['color'] }}" href="{{ $modulo['ruta'] }}">
                        <span class="inicio-card-icon"><i class="{{ $modulo['icono'] }}"></i></span>
                        <h2 class="inicio-card-title">{{ $modulo['titulo'] }}</h2>
                        <p class="inicio-card-text">{{ $modulo['detalle'] }}</p>
                    </a>
                @endpermiso
            @endforeach
        </div>

        <div class="inicio-section">
            <div class="inicio-section-head">
                <div>
                    <h2 class="inicio-section-title">Accesos de trabajo</h2>
                    <p class="inicio-section-note">Se muestran según los permisos del usuario.</p>
                </div>
            </div>

            <div class="inicio-link-grid">
                @foreach ($catalogos as $catalogo)
                    @permiso($catalogo['permiso'])
                        <a class="inicio-link" href="{{ $catalogo['ruta'] }}">
                            <span>{{ $catalogo['titulo'] }}</span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    @endpermiso
                @endforeach
            </div>
        </div>
    </section>
</x-admin-layout>
