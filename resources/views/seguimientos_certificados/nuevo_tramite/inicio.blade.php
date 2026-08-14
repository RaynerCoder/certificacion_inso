@php
    // Opciones iniciales para los selectores visuales del formulario.
    $opcionesBeneficiarios = collect($personasSelect ?? []);
    $opcionesTramitadores = collect($tramitadoresIniciales ?? []);
    $opcionesTiposCertificados = collect($tiposCertificadosSelect ?? []);
    $beneficiarioActual = $opcionesBeneficiarios->firstWhere('id', (int) $beneficiarioSeleccionado);
    $tramitadorActual = $opcionesTramitadores->firstWhere('id', (int) $tramitadorSeleccionado)
        ?: ($tramitadorAutomatico ?? null);
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
                    <h2 class="tramite-persona-title">Iniciar trámite</h2>
                    <p class="tramite-persona-subtitle">
                        Complete los datos principales para registrar y enviar la solicitud.
                    </p>
                </div>
            </div>

        </div>

        <div class="tramite-persona-body">
            <div class="tramite-fields">
                @unless ($mostrarTramitador)
                    <input type="hidden" name="form_id_persona_tramitador" value="{{ $tramitadorSeleccionado }}">
                @endunless

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

                @if ($mostrarTramitador)
                <div class="tramite-field-6 tramite-inicio-field">
                    <div class="tramite-persona-select {{ $tramitadorBloqueado ? 'is-locked' : '' }}"
                        data-tramite-selector
                        data-tramite-select="tramitador"
                        data-bloqueado="{{ $tramitadorBloqueado ? '1' : '0' }}">
                        <label class="tramite-persona-select-label" for="form_id_persona_tramitador">Tramitador</label>

                        @if ($tramitadorBloqueado)
                            <input type="hidden" name="form_id_persona_tramitador" value="{{ $tramitadorSeleccionado }}">
                        @endif

                        {{-- El solicitante externo siempre tramita con su propia cuenta. --}}
                        <select id="form_id_persona_tramitador" name="form_id_persona_tramitador"
                            class="tramite-persona-native-select @error('form_id_persona_tramitador') is-invalid @enderror"
                            data-tramite-native required @disabled($tramitadorBloqueado)>
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
                            @disabled($tramitadorBloqueado)
                            data-placeholder="Seleccione tramitador"
                            data-help="{{ $tramitadorBloqueado ? 'Asignado automaticamente a la cuenta que inicia el tramite.' : 'Busque por nombre o tipo' }}">
                            <span class="tramite-persona-select-text">
                                <span class="tramite-persona-select-name" data-tramite-label>
                                    {{ $tramitadorActual['nombre'] ?? 'Seleccione tramitador' }}
                                </span>
                                <span class="tramite-persona-select-help" data-tramite-help>
                                    {{ $tramitadorBloqueado ? 'Asignado automaticamente a la cuenta que inicia el tramite.' : ($tramitadorActual['detalle'] ?? 'Busque por nombre o tipo') }}
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

                    @unless ($tramitadorBloqueado)
                        <label class="tramite-mini-check">
                            <input type="checkbox" id="mismoBeneficiario">
                            <span>Beneficiario y tramitador son la misma persona</span>
                        </label>
                    @endunless

                    <x-input-error for="form_id_persona_tramitador" class="mt-2" />
                </div>
                @endif

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
                    <i class="fa-solid fa-file-lines"></i>
                </div>

                <div>
                    <h2 class="tramite-persona-title">Documentos requeridos</h2>
                    <p class="tramite-persona-subtitle">
                        Adjunte la evidencia solicitada para continuar con el trámite.
                    </p>
                </div>
            </div>

            <div class="tramite-documentos-progress" aria-live="polite">
                <span id="requisitosProgresoTexto">0 de 0 documentos</span>
                <div class="tramite-documentos-progress-track"
                    role="progressbar"
                    aria-label="Documentos completados"
                    aria-valuemin="0"
                    aria-valuemax="0"
                    aria-valuenow="0">
                    <span id="requisitosProgresoBar"></span>
                </div>
            </div>
        </div>

        <div class="tramite-persona-body is-documents">
            <div id="contenedorDocumentosTramite" class="tramite-requisitos-form is-empty">
                <div class="tramite-requisitos-empty">
                    Seleccione un tipo de certificado para cargar los requisitos.
                </div>
            </div>

            <div id="resumenDocumentosTramite" class="tramite-documentos-summary is-hidden" aria-live="polite">
                <span class="tramite-documentos-summary-icon">
                    <i class="fa-regular fa-file-lines"></i>
                </span>
                <strong id="resumenDocumentosTexto">0 documentos pendientes</strong>
                <div class="tramite-documentos-summary-track" aria-hidden="true">
                    <span id="resumenDocumentosBarra"></span>
                </div>
            </div>
        </div>
    </section>
</div>
