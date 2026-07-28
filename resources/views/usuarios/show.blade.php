<x-admin-layout title="Detalle del usuario | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Usuarios',
        'href' => route('usuarios_index'),
    ],
    [
        'name' => 'Detalle',
    ],
]">

    @include('seguridad.estilos')

    @php
        $funcionario = $usuario->funcionario;
        $nombreCompleto = $funcionario
            ? collect([
                $funcionario->nombres,
                $funcionario->apellido_paterno,
                $funcionario->apellido_materno,
            ])->filter()->join(' ')
            : $usuario->name;

        $iniciales = collect(preg_split('/\s+/', trim($nombreCompleto)))
            ->filter()
            ->take(2)
            ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
            ->join('');
    @endphp

    <style>
        .usuario-detalle {
            display: grid;
            gap: 16px;
        }

        .usuario-detalle-toolbar,
        .usuario-detalle-actions,
        .usuario-identidad,
        .usuario-identidad-dato,
        .usuario-seccion-titulo,
        .usuario-rol-fila {
            display: flex;
            align-items: center;
        }

        .usuario-detalle-toolbar {
            justify-content: space-between;
            gap: 16px;
        }

        .usuario-detalle-titulo {
            margin: 0;
            color: #0f172a;
            font-size: 22px;
            font-weight: 800;
        }

        .usuario-detalle-actions {
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 8px;
        }

        .usuario-identidad {
            min-width: 0;
            flex-wrap: wrap;
            gap: 0;
            border-top: 1px solid #dbe3ee;
            border-bottom: 1px solid #dbe3ee;
            background: #ffffff;
            padding: 16px 18px;
        }

        .usuario-identidad-avatar {
            display: inline-flex;
            width: 48px;
            height: 48px;
            flex: 0 0 auto;
            align-items: center;
            justify-content: center;
            border: 1px solid #dbeafe;
            border-radius: 50%;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 17px;
            font-weight: 800;
        }

        .usuario-identidad-dato {
            min-height: 34px;
            min-width: 0;
            gap: 8px;
            border-left: 1px solid #dbe3ee;
            margin-left: 18px;
            padding-left: 18px;
        }

        .usuario-identidad-nombre {
            max-width: 330px;
            color: #172033;
            font-size: 16px;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .usuario-identidad-texto {
            max-width: 300px;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .usuario-contenido {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(360px, .92fr);
            border-bottom: 1px solid #dbe3ee;
            background: #ffffff;
        }

        .usuario-seccion {
            min-width: 0;
            padding: 18px;
        }

        .usuario-seccion + .usuario-seccion {
            border-left: 1px solid #dbe3ee;
        }

        .usuario-seccion-titulo {
            gap: 9px;
            margin: 0 0 16px;
            color: #172033;
            font-size: 15px;
            font-weight: 800;
        }

        .usuario-seccion-titulo i {
            width: 18px;
            color: #0f766e;
            text-align: center;
        }

        .usuario-datos {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 26px;
        }

        .usuario-dato {
            display: grid;
            grid-template-columns: minmax(115px, .8fr) minmax(0, 1.2fr);
            gap: 12px;
            min-width: 0;
            border-bottom: 1px solid #edf1f6;
            padding: 9px 0;
        }

        .usuario-dato-label {
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
        }

        .usuario-dato-valor {
            min-width: 0;
            color: #172033;
            font-size: 13px;
            font-weight: 600;
            line-height: 1.45;
            overflow-wrap: anywhere;
        }

        .usuario-bloque-subtitulo {
            margin: 17px 0 4px;
            color: #334155;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .usuario-vacio {
            margin: 0;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
            color: #64748b;
            padding: 12px 13px;
            font-size: 13px;
            font-weight: 600;
        }

        .usuario-roles {
            display: grid;
            gap: 9px;
        }

        .usuario-rol-fila {
            min-width: 0;
            justify-content: space-between;
            gap: 12px;
            border: 1px solid #dbe3ee;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .usuario-rol-info {
            min-width: 0;
        }

        .usuario-rol-nombre,
        .usuario-rol-slug {
            display: block;
            overflow-wrap: anywhere;
        }

        .usuario-rol-nombre {
            color: #172033;
            font-size: 13px;
            font-weight: 800;
        }

        .usuario-rol-slug {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .usuario-rol-meta {
            display: flex;
            flex: 0 0 auto;
            align-items: center;
            gap: 8px;
        }

        .usuario-permisos-directos {
            margin-top: 18px;
            border-top: 1px solid #dbe3ee;
            padding-top: 15px;
        }

        .usuario-permisos-directos h3 {
            margin: 0 0 9px;
            color: #334155;
            font-size: 13px;
            font-weight: 800;
        }

        .usuario-cargos {
            min-width: 0;
            border-bottom: 1px solid #dbe3ee;
            background: #ffffff;
            padding: 18px;
        }

        .usuario-tabla-contenedor {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #dbe3ee;
            border-radius: 6px;
            -webkit-overflow-scrolling: touch;
        }

        .usuario-tabla {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .usuario-tabla th {
            background: #f8fafc;
            color: #475569;
            padding: 10px 12px;
            text-align: left;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .usuario-tabla td {
            border-top: 1px solid #e2e8f0;
            color: #334155;
            padding: 11px 12px;
            font-size: 13px;
            line-height: 1.45;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        .usuario-tabla th:last-child,
        .usuario-tabla td:last-child {
            width: 120px;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .usuario-contenido {
                grid-template-columns: 1fr;
            }

            .usuario-seccion + .usuario-seccion {
                border-top: 1px solid #dbe3ee;
                border-left: 0;
            }
        }

        @media (max-width: 760px) {
            .usuario-detalle-toolbar {
                align-items: stretch;
                flex-direction: column;
            }

            .usuario-detalle-actions {
                justify-content: stretch;
            }

            .usuario-detalle-actions > * {
                flex: 1 1 0;
                justify-content: center;
            }

            .usuario-identidad {
                align-items: flex-start;
                flex-direction: column;
                gap: 12px;
            }

            .usuario-identidad-dato {
                width: 100%;
                min-height: auto;
                border-left: 0;
                margin-left: 0;
                padding-left: 0;
            }

            .usuario-identidad-dato + .usuario-identidad-dato {
                border-top: 1px solid #edf1f6;
                padding-top: 10px;
            }

            .usuario-datos {
                grid-template-columns: 1fr;
            }

            .usuario-rol-fila {
                align-items: flex-start;
                flex-direction: column;
            }

            .usuario-rol-meta {
                width: 100%;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 430px) {
            .usuario-detalle-titulo {
                font-size: 19px;
            }

            .usuario-seccion,
            .usuario-cargos {
                padding: 14px;
            }

            .usuario-dato {
                grid-template-columns: 1fr;
                gap: 3px;
            }
        }
    </style>

    <div class="usuario-detalle">
        <div class="usuario-detalle-toolbar">
            <h1 class="usuario-detalle-titulo">Detalle del usuario</h1>

            <div class="usuario-detalle-actions">
                <x-wire-button href="{{ route('usuarios_index') }}" secondary>
                    <i class="fa-solid fa-arrow-left"></i>
                    Volver
                </x-wire-button>

                <x-wire-button href="{{ route('usuarios_edit', $usuario) }}" blue>
                    <i class="fa-solid fa-pen"></i>
                    Editar usuario
                </x-wire-button>
            </div>
        </div>

        <section class="usuario-identidad">
            <span class="usuario-identidad-avatar">{{ $iniciales ?: 'U' }}</span>

            <div class="usuario-identidad-dato">
                <span class="usuario-identidad-nombre">{{ $nombreCompleto }}</span>
            </div>

            <div class="usuario-identidad-dato">
                <span class="usuario-identidad-texto">{{ $usuario->email }}</span>
            </div>

            <div class="usuario-identidad-dato">
                @include('tablas.chip_estado', ['estado' => $usuario->estado])
            </div>

            <div class="usuario-identidad-dato">
                <span class="usuario-identidad-texto">ID: {{ $usuario->id }}</span>
            </div>
        </section>

        <div class="usuario-contenido">
            <section class="usuario-seccion">
                <h2 class="usuario-seccion-titulo">
                    <i class="fa-regular fa-address-card"></i>
                    Cuenta y datos personales
                </h2>

                <div class="usuario-datos">
                    <div class="usuario-dato">
                        <span class="usuario-dato-label">Usuario</span>
                        <span class="usuario-dato-valor">{{ $usuario->name }}</span>
                    </div>

                    <div class="usuario-dato">
                        <span class="usuario-dato-label">Correo</span>
                        <span class="usuario-dato-valor">{{ $usuario->email }}</span>
                    </div>

                    <div class="usuario-dato">
                        <span class="usuario-dato-label">Fecha de registro</span>
                        <span class="usuario-dato-valor">
                            {{ $usuario->created_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </span>
                    </div>

                    <div class="usuario-dato">
                        <span class="usuario-dato-label">Actualización</span>
                        <span class="usuario-dato-valor">
                            {{ $usuario->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}
                        </span>
                    </div>
                </div>

                <h3 class="usuario-bloque-subtitulo">Datos personales</h3>

                @if ($funcionario)
                    <div class="usuario-datos">
                        <div class="usuario-dato">
                            <span class="usuario-dato-label">Nombres</span>
                            <span class="usuario-dato-valor">{{ $funcionario->nombres }}</span>
                        </div>

                        <div class="usuario-dato">
                            <span class="usuario-dato-label">Apellido paterno</span>
                            <span class="usuario-dato-valor">{{ $funcionario->apellido_paterno }}</span>
                        </div>

                        <div class="usuario-dato">
                            <span class="usuario-dato-label">Apellido materno</span>
                            <span class="usuario-dato-valor">{{ $funcionario->apellido_materno ?: 'Sin dato' }}</span>
                        </div>

                        <div class="usuario-dato">
                            <span class="usuario-dato-label">Carnet</span>
                            <span class="usuario-dato-valor">{{ $funcionario->carnet }}</span>
                        </div>

                        <div class="usuario-dato">
                            <span class="usuario-dato-label">Teléfono</span>
                            <span class="usuario-dato-valor">{{ $funcionario->telefono ?: 'Sin dato' }}</span>
                        </div>

                        <div class="usuario-dato">
                            <span class="usuario-dato-label">Género</span>
                            <span class="usuario-dato-valor">
                                @if ($funcionario->genero === null)
                                    Sin dato
                                @else
                                    {{ (string) $funcionario->genero === '1' ? 'Masculino' : 'Femenino' }}
                                @endif
                            </span>
                        </div>
                    </div>
                @else
                    <p class="usuario-vacio">No tiene datos personales vinculados.</p>
                @endif
            </section>

            <section class="usuario-seccion">
                <h2 class="usuario-seccion-titulo">
                    <i class="fa-solid fa-shield-halved"></i>
                    Acceso al sistema
                </h2>

                @if ($usuario->roles->isNotEmpty())
                    <div class="usuario-roles">
                        @foreach ($usuario->roles as $rol)
                            <div class="usuario-rol-fila">
                                <div class="usuario-rol-info">
                                    <span class="usuario-rol-nombre">{{ $rol->name }}</span>
                                    <span class="usuario-rol-slug">{{ $rol->slug }}</span>
                                </div>

                                <div class="usuario-rol-meta">
                                    @include('tablas.chip_estado', ['estado' => $rol->estado])

                                    @include('seguridad.chips-tabla', [
                                        'items' => $rol->permisos,
                                        'campo' => 'nombre',
                                        'vacio' => 'Sin permisos',
                                        'soloResumen' => true,
                                        'tituloModal' => 'Permisos del rol ' . $rol->name,
                                    ])
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="usuario-vacio">Sin roles asignados.</p>
                @endif

                <div class="usuario-permisos-directos">
                    <h3>Permisos directos</h3>

                    @include('seguridad.chips-tabla', [
                        'items' => $usuario->permisosDirectos,
                        'campo' => 'nombre',
                        'vacio' => 'Sin permisos directos',
                        'soloResumen' => true,
                        'tituloModal' => 'Permisos directos del usuario',
                    ])
                </div>
            </section>
        </div>

        <section class="usuario-cargos">
            <h2 class="usuario-seccion-titulo">
                <i class="fa-solid fa-briefcase"></i>
                Cargos asignados ({{ $funcionario?->cargos->count() ?? 0 }})
            </h2>

            @if ($funcionario?->cargos->isNotEmpty())
                <div class="usuario-tabla-contenedor">
                    <table class="usuario-tabla">
                        <thead>
                            <tr>
                                <th>Cargo</th>
                                <th>Área</th>
                                <th>Descripción</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($funcionario->cargos as $cargo)
                                <tr>
                                    <td>{{ $cargo->nombre }}</td>
                                    <td>{{ $cargo->area?->nombre ?? 'Sin área' }}</td>
                                    <td>{{ $cargo->descripcion ?: 'Sin descripción' }}</td>
                                    <td>@include('tablas.chip_estado', ['estado' => $cargo->estado])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="usuario-vacio">Sin cargos asignados.</p>
            @endif
        </section>
    </div>

</x-admin-layout>
