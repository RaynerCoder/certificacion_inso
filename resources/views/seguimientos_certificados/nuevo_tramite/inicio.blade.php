@php
    // Usuario que registra el tramite.
    $usuarioRegistroTramite = auth()->user();
    $usuarioRegistroTramite?->loadMissing('funcionario.cargos', 'persona.empresa', 'persona.natural', 'roles');
    $funcionarioRegistroTramite = $usuarioRegistroTramite?->funcionario;
    $rolesRegistroTramite = $usuarioRegistroTramite?->roles
        ?->filter(fn ($rol) => (string) $rol->estado === '1')
        ->pluck('name')
        ->filter()
        ->unique()
        ->implode(', ');

    if ($funcionarioRegistroTramite) {
        $nombreRegistroTramite = trim(implode(' ', array_filter([
            $funcionarioRegistroTramite->nombres,
            $funcionarioRegistroTramite->apellido_paterno,
            $funcionarioRegistroTramite->apellido_materno,
        ])));

        $detalleRegistroTramite = $funcionarioRegistroTramite->cargos?->pluck('nombre')->filter()->unique()->implode(', ')
            ?: ($rolesRegistroTramite ?: 'Funcionario');
    } elseif ($usuarioRegistroTramite?->persona?->empresa) {
        $nombreRegistroTramite = $usuarioRegistroTramite->persona->empresa->razon_social ?: ($usuarioRegistroTramite->name ?? '');
        $detalleRegistroTramite = $rolesRegistroTramite ?: 'Empresa';
    } elseif ($usuarioRegistroTramite?->persona?->natural) {
        $naturalRegistroTramite = $usuarioRegistroTramite->persona->natural;
        $nombreRegistroTramite = trim(implode(' ', array_filter([
            $naturalRegistroTramite->nombres,
            $naturalRegistroTramite->apellido_paterno,
            $naturalRegistroTramite->apellido_materno,
            $naturalRegistroTramite->apellido_casado,
        ])));

        $detalleRegistroTramite = $rolesRegistroTramite ?: 'Persona natural';
    } else {
        $nombreRegistroTramite = $usuarioRegistroTramite?->name ?: 'Usuario del sistema';
        $detalleRegistroTramite = $rolesRegistroTramite ?: 'Usuario del sistema';
    }

    // Opciones iniciales para los selectores visuales del formulario.
    $opcionesBeneficiarios = collect($personasSelect ?? []);
    $opcionesTramitadores = collect($tramitadoresIniciales ?? []);
    $opcionesTiposCertificados = collect($tiposCertificadosSelect ?? []);
    $beneficiarioActual = $opcionesBeneficiarios->firstWhere('id', (int) $beneficiarioSeleccionado);
    $tramitadorActual = $opcionesTramitadores->firstWhere('id', (int) $tramitadorSeleccionado);
    $tipoCertificadoActual = $opcionesTiposCertificados->firstWhere('id', (int) $tipoSeleccionado);
@endphp

<div class="tramite-flow-form">
    {{-- DATOS PRINCIPALES: se guardan en certificados y definen a quien llega inicialmente el tramite. --}}
    <section class="tramite-persona-card">
        <div class="tramite-persona-head">
            <div class="tramite-persona-head-left">
                <div class="tramite-persona-icon">
                    <i class="fa-regular fa-file-lines"></i>
                </div>

                <div>
                    <h2 class="tramite-persona-title">Iniciar tr&aacute;mite</h2>
                    <p class="tramite-persona-subtitle">
                        Complete los datos principales para registrar y enviar la solicitud.
                    </p>
                </div>
            </div>

        </div>

        <div class="tramite-persona-body">
            {{-- Identifica al usuario que registra el tramite sin mezclarlo con los datos del solicitante. --}}
            <div class="tramite-registro-strip">
                <span class="tramite-registro-icon">
                    <i class="fa-regular fa-user"></i>
                </span>

                <span class="tramite-registro-label">Usuario que registra tr&aacute;mite</span>
                <strong>{{ $nombreRegistroTramite ?: 'Usuario del sistema' }}</strong>
                <span class="tramite-registro-separator"></span>
                <span class="tramite-registro-role">{{ $detalleRegistroTramite }}</span>
            </div>

            <div class="tramite-fields">
                <div class="tramite-field-6 tramite-inicio-field">
                    <div class="tramite-persona-select {{ $beneficiarioBloqueado ? 'is-locked' : '' }}"
                        data-tramite-selector
                        data-tramite-select="beneficiario"
                        data-bloqueado="{{ $beneficiarioBloqueado ? '1' : '0' }}">
                        <label class="tramite-persona-select-label" for="form_id_persona_beneficiario">Beneficiario</label>

                        {{-- Select real: Laravel recibe este campo; el control visual solo facilita la busqueda. --}}
                        <select id="form_id_persona_beneficiario" name="form_id_persona_beneficiario"
                            class="tramite-persona-native-select @error('form_id_persona_beneficiario') is-invalid @enderror"
                            data-tramite-native required>
                            <option value="">Seleccione beneficiario</option>
                            @foreach ($opcionesBeneficiarios as $opcion)
                                <option value="{{ $opcion['id'] }}" data-label="{{ $opcion['nombre'] }}"
                                    data-help="{{ $opcion['detalle'] }}" data-tipo="{{ $opcion['tipo'] ?? '' }}"
                                    @selected((string) $beneficiarioSeleccionado === (string) $opcion['id'])>
                                    {{ $opcion['nombre'] }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" class="tramite-persona-select-control" data-tramite-toggle
                            @disabled($beneficiarioBloqueado)
                            data-placeholder="Seleccione beneficiario" data-help="Busque por nombre o tipo">
                            <span class="tramite-persona-select-text">
                                <span class="tramite-persona-select-name" data-tramite-label>
                                    {{ $beneficiarioActual['nombre'] ?? 'Seleccione beneficiario' }}
                                </span>
                                <span class="tramite-persona-select-help" data-tramite-help>
                                    {{ $beneficiarioActual['detalle'] ?? 'Busque por nombre o tipo' }}
                                </span>
                            </span>

                            <i class="fa-solid fa-chevron-down tramite-persona-select-chevron"></i>
                        </button>

                        <div class="tramite-persona-select-dropdown" data-tramite-menu hidden>
                            <div class="tramite-persona-select-search">
                                <input type="search" data-tramite-search placeholder="Buscar beneficiario">
                            </div>

                            <div class="tramite-persona-select-options" data-tramite-options>
                                @foreach ($opcionesBeneficiarios as $opcion)
                                    <button type="button" class="tramite-persona-select-option" data-tramite-option
                                        data-value="{{ $opcion['id'] }}" data-label="{{ $opcion['nombre'] }}"
                                        data-help="{{ $opcion['detalle'] }}" data-tipo="{{ $opcion['tipo'] ?? '' }}"
                                        data-search="{{ \Illuminate\Support\Str::lower(($opcion['nombre'] ?? '') . ' ' . ($opcion['detalle'] ?? '')) }}">
                                        <span class="tramite-persona-select-option-main">
                                            <strong>{{ $opcion['nombre'] }}</strong>
                                            <small>{{ $opcion['detalle'] }}</small>
                                        </span>
                                    </button>
                                @endforeach

                                <div class="tramite-persona-select-empty is-hidden" data-tramite-empty>
                                    No se encontraron registros.
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-input-error for="form_id_persona_beneficiario" class="mt-2" />
                </div>

                <div class="tramite-field-6 tramite-inicio-field">
                    <div class="tramite-persona-select" data-tramite-selector data-tramite-select="tramitador">
                        <label class="tramite-persona-select-label" for="form_id_persona_tramitador">Tramitador</label>

                        {{-- Select real: se actualiza desde JS cuando cambia el beneficiario. --}}
                        <select id="form_id_persona_tramitador" name="form_id_persona_tramitador"
                            class="tramite-persona-native-select @error('form_id_persona_tramitador') is-invalid @enderror"
                            data-tramite-native required>
                            <option value="">Seleccione tramitador</option>
                            @foreach ($opcionesTramitadores as $opcion)
                                <option value="{{ $opcion['id'] }}" data-label="{{ $opcion['nombre'] }}"
                                    data-help="{{ $opcion['detalle'] }}" data-tipo="{{ $opcion['tipo'] ?? '' }}"
                                    @selected((string) $tramitadorSeleccionado === (string) $opcion['id'])>
                                    {{ $opcion['nombre'] }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" class="tramite-persona-select-control" data-tramite-toggle
                            data-placeholder="Seleccione tramitador" data-help="Busque por nombre o tipo">
                            <span class="tramite-persona-select-text">
                                <span class="tramite-persona-select-name" data-tramite-label>
                                    {{ $tramitadorActual['nombre'] ?? 'Seleccione tramitador' }}
                                </span>
                                <span class="tramite-persona-select-help" data-tramite-help>
                                    {{ $tramitadorActual['detalle'] ?? 'Busque por nombre o tipo' }}
                                </span>
                            </span>

                            <i class="fa-solid fa-chevron-down tramite-persona-select-chevron"></i>
                        </button>

                        <div class="tramite-persona-select-dropdown" data-tramite-menu hidden>
                            <div class="tramite-persona-select-search">
                                <input type="search" data-tramite-search placeholder="Buscar tramitador">
                            </div>

                            <div class="tramite-persona-select-options" data-tramite-options>
                                @foreach ($opcionesTramitadores as $opcion)
                                    <button type="button" class="tramite-persona-select-option" data-tramite-option
                                        data-value="{{ $opcion['id'] }}" data-label="{{ $opcion['nombre'] }}"
                                        data-help="{{ $opcion['detalle'] }}" data-tipo="{{ $opcion['tipo'] ?? '' }}"
                                        data-search="{{ \Illuminate\Support\Str::lower(($opcion['nombre'] ?? '') . ' ' . ($opcion['detalle'] ?? '')) }}">
                                        <span class="tramite-persona-select-option-main">
                                            <strong>{{ $opcion['nombre'] }}</strong>
                                            <small>{{ $opcion['detalle'] }}</small>
                                        </span>
                                    </button>
                                @endforeach

                                <div class="tramite-persona-select-empty is-hidden" data-tramite-empty>
                                    No se encontraron registros.
                                </div>
                            </div>
                        </div>
                    </div>

                    <label class="tramite-mini-check">
                        <input type="checkbox" id="mismoBeneficiario">
                        <span>Beneficiario y tramitador son la misma persona</span>
                    </label>

                    <x-input-error for="form_id_persona_tramitador" class="mt-2" />
                </div>

                <div class="tramite-field-12 tramite-inicio-field">
                    <div class="tramite-persona-select is-single-line" data-tramite-selector data-tramite-select="tipo-certificado">
                        <label class="tramite-persona-select-label" for="form_id_tipo_certificado">Tipo de certificado</label>

                        {{-- Campo que se envia al guardar; tambien sirve para cargar los requisitos del tramite. --}}
                        <select id="form_id_tipo_certificado" name="form_id_tipo_certificado"
                            class="tramite-persona-native-select @error('form_id_tipo_certificado') is-invalid @enderror"
                            data-tramite-native required>
                            <option value="">Seleccione tipo de certificado</option>
                            @foreach ($opcionesTiposCertificados as $opcion)
                                <option value="{{ $opcion['id'] }}" data-label="{{ $opcion['nombre'] }}"
                                    data-help=""
                                    @selected((string) $tipoSeleccionado === (string) $opcion['id'])>
                                    {{ $opcion['nombre'] }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" class="tramite-persona-select-control" data-tramite-toggle
                            data-placeholder="Seleccione tipo de certificado" data-help="Busque por nombre del certificado">
                            <span class="tramite-persona-select-text">
                                <span class="tramite-persona-select-name" data-tramite-label>
                                    {{ $tipoCertificadoActual['nombre'] ?? 'Seleccione tipo de certificado' }}
                                </span>
                                <span class="tramite-persona-select-help" data-tramite-help>
                                    Busque por nombre del certificado
                                </span>
                            </span>

                            <i class="fa-solid fa-chevron-down tramite-persona-select-chevron"></i>
                        </button>

                        <div class="tramite-persona-select-dropdown" data-tramite-menu hidden>
                            <div class="tramite-persona-select-search">
                                <input type="search" data-tramite-search placeholder="Buscar tipo de certificado">
                            </div>

                            <div class="tramite-persona-select-options" data-tramite-options>
                                @foreach ($opcionesTiposCertificados as $opcion)
                                    <button type="button" class="tramite-persona-select-option" data-tramite-option
                                        data-value="{{ $opcion['id'] }}" data-label="{{ $opcion['nombre'] }}"
                                        data-help=""
                                        data-search="{{ \Illuminate\Support\Str::lower($opcion['nombre'] ?? '') }}">
                                        <span class="tramite-persona-select-option-main">
                                            <strong>{{ $opcion['nombre'] }}</strong>
                                        </span>
                                    </button>
                                @endforeach

                                <div class="tramite-persona-select-empty is-hidden" data-tramite-empty>
                                    No se encontraron registros.
                                </div>
                            </div>
                        </div>
                    </div>

                    <x-input-error for="form_id_tipo_certificado" class="mt-2" />
                </div>
            </div>
        </div>
    </section>

    {{-- REQUISITOS: se guardan como pendientes hasta que el funcionario los revise. --}}
    <section class="tramite-persona-card">
        <div class="tramite-persona-head is-documents">
            <div class="tramite-persona-head-left">
                <div class="tramite-persona-icon is-documents">
                    <i class="fa-regular fa-file-pdf"></i>
                </div>

                <div>
                    <h2 class="tramite-persona-title">Documentos requeridos</h2>
                    <p class="tramite-persona-subtitle">
                        Adjunte la evidencia disponible. El funcionario asignado revisar&aacute; si cumple.
                    </p>
                </div>
            </div>
        </div>

        <div class="tramite-persona-body">
            <div class="tramite-table-wrap">
                <table class="tramite-table">
                    <thead>
                        <tr>
                            <th style="width: 56px;">N&deg;</th>
                            <th>Requisito</th>
                            <th style="width: 190px;">Tipo de evidencia</th>
                            <th style="width: 280px;">Descripción evidencia</th>
                            <th style="width: 420px;">Subir evidencia</th>
                        </tr>
                    </thead>
                    <tbody id="tablaDocumentosTramite">
                        <tr>
                            <td colspan="5" class="text-center text-slate-500">
                                Seleccione un tipo de certificado para cargar los requisitos.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>
