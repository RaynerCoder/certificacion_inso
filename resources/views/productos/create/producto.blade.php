{{-- Paso 1: datos principales de productos. --}}
<div class="producto-step" data-producto-step="0">
    <section class="producto-section">
        <div class="producto-step-grid grid grid-cols-1 gap-x-4 gap-y-3 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label class="producto-field-label" for="form_id_importador_persona">Importador</label>
                <select class="producto-select{{ $claseErrorProducto('form_id_importador_persona') }} producto-select-search"
                    id="form_id_importador_persona" name="form_id_importador_persona" data-producto-buscador="1"
                    data-producto-placeholder="Buscar importador">
                    <option value="">Seleccione importador</option>
                    @foreach ($importadoresCatalogo as $persona)
                        @php
                            $importadorSeleccionado = $valorProducto(
                                'form_id_importador_persona',
                                request('form_id_importador_persona')
                            );
                            // El controlador entrega este texto calculado; el fallback conserva una etiqueta clara.
                            $detalleImportador = $persona->detalle_importador
                                ?? ($persona->empresa ? 'Empresa' : 'Persona natural');
                        @endphp
                        <option value="{{ $persona->id }}" data-producto-detalle="{{ $detalleImportador }}"
                            @selected((string) $importadorSeleccionado === (string) $persona->id)>
                            {{ $nombrePersonaProducto($persona) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-5">
                <div class="producto-field-head">
                    <label class="producto-field-label !mb-0" for="form_id_fabricante">Fabricante</label>
                    <button type="button" class="producto-field-link"
                        onclick="abrirModalProducto('modalFabricanteProducto')">
                        + Nuevo fabricante
                    </button>
                </div>
                <select class="producto-select{{ $claseErrorProducto('form_id_fabricante') }} producto-select-search"
                    id="form_id_fabricante" name="form_id_fabricante" data-producto-buscador="1"
                    data-producto-placeholder="Buscar fabricante">
                    <option value="">Seleccione fabricante</option>
                    @if (str_starts_with((string) old('form_id_fabricante'), 'TEMP-') && old('form_fabricante_temporal_nombre'))
                        <option value="{{ old('form_id_fabricante') }}" data-temporal="1"
                            data-nombre="{{ old('form_fabricante_temporal_nombre') }}"
                            data-razon-social="{{ old('form_fabricante_temporal_razon_social') }}" selected>
                            {{ old('form_fabricante_temporal_razon_social') ?: old('form_fabricante_temporal_nombre') }}
                        </option>
                    @endif
                    @foreach ($fabricantesCatalogo as $fabricante)
                        <option value="{{ $fabricante->id }}" data-nombre="{{ $fabricante->nombre }}"
                            data-razon-social="{{ $fabricante->razon_social }}" @selected($valorProducto('form_id_fabricante') == $fabricante->id)>
                            {{ $fabricante->razon_social ?: $fabricante->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="producto-field-label" for="form_estado">Estado</label>
                <select class="producto-select{{ $claseErrorProducto('form_estado') }}" id="form_estado"
                    name="form_estado">
                    <option value="ACTIVO" @selected($valorProducto('form_estado', 'ACTIVO') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected($valorProducto('form_estado') === 'INACTIVO')>Inactivo</option>
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="producto-field-label" for="form_codigo">Código</label>
                <input class="producto-input{{ $claseErrorProducto('form_codigo') }}" id="form_codigo"
                    name="form_codigo" type="text" value="{{ $valorProducto('form_codigo') }}" placeholder="Ej: PROD-PLAG-001">
                @error('form_codigo')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-5">
                <label class="producto-field-label" for="form_nombre_comercial">Nombre comercial</label>
                <input class="producto-input{{ $claseErrorProducto('form_nombre_comercial') }}"
                    id="form_nombre_comercial" name="form_nombre_comercial" type="text"
                    value="{{ $valorProducto('form_nombre_comercial') }}" placeholder="Ej: GLIFOSATO 48 SL">
                @error('form_nombre_comercial')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-4">
                <label class="producto-field-label" for="form_id_territorio_pais">País</label>
                <select class="producto-select{{ $claseErrorProducto('form_id_territorio_pais') }} producto-select-search"
                    id="form_id_territorio_pais" name="form_id_territorio_pais" data-producto-buscador="1"
                    data-producto-placeholder="Buscar país">
                    <option value="">Seleccione el país</option>
                    @foreach ($territoriosCatalogo as $territorio)
                        <option value="{{ $territorio->id }}" @selected($valorProducto('form_id_territorio_pais') == $territorio->id)>
                            {{ $territorio->nombre }}{{ $territorio->codigo ? ' - ' . $territorio->codigo : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-6">
                <label class="producto-field-label" for="form_tipo_producto">Tipo de producto</label>
                <select class="producto-select{{ $claseErrorProducto('form_tipo_producto') }}"
                    id="form_tipo_producto" name="form_tipo_producto">
                    <option value="">Seleccione el tipo de producto</option>
                    @foreach ($tiposProducto as $valor => $etiqueta)
                        <option value="{{ $valor }}" @selected($valorProducto('form_tipo_producto') === $valor)>
                            {{ $etiqueta }}
                        </option>
                    @endforeach
                </select>
                @error('form_tipo_producto')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-6">
                <label class="producto-field-label" for="form_nombre_tecnico">Nombre técnico</label>
                <input class="producto-input{{ $claseErrorProducto('form_nombre_tecnico') }}"
                    id="form_nombre_tecnico" name="form_nombre_tecnico" type="text"
                    value="{{ $valorProducto('form_nombre_tecnico') }}"
                    placeholder="Se completa con: Ingrediente 1 + Ingrediente 2" readonly
                    aria-describedby="form_nombre_tecnico_ayuda">
                <p id="form_nombre_tecnico_ayuda" class="producto-field-help">
                    Se genera automáticamente con los ingredientes agregados, por ejemplo: Ingrediente 1 + Ingrediente 2.
                </p>
                @error('form_nombre_tecnico')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-6">
                <div class="producto-field-head">
                    <label class="producto-field-label !mb-0" for="form_id_clasificacion_quimica">Clasificación química</label>
                    <button type="button" class="producto-field-link"
                        onclick="abrirModalProducto('modalClasificacionQuimica')">
                        + Nueva clasificación
                    </button>
                </div>
                <select class="producto-select{{ $claseErrorProducto('form_id_clasificacion_quimica') }} producto-select-search"
                    id="form_id_clasificacion_quimica" name="form_id_clasificacion_quimica" data-producto-buscador="1"
                    data-producto-placeholder="Buscar clasificación química">
                    <option value="">Seleccione clasificación química</option>
                    @if (str_starts_with((string) old('form_id_clasificacion_quimica'), 'TEMP-') && old('form_clasificacion_quimica_temporal_nombre'))
                        <option value="{{ old('form_id_clasificacion_quimica') }}" data-temporal="1"
                            data-nombre="{{ old('form_clasificacion_quimica_temporal_nombre') }}" selected>
                            {{ old('form_clasificacion_quimica_temporal_nombre') }}
                        </option>
                    @endif
                    @foreach ($clasificacionesQuimicasCatalogo as $clasificacionQuimica)
                        <option value="{{ $clasificacionQuimica->id }}" data-nombre="{{ $clasificacionQuimica->nombre }}"
                            @selected((string) $valorProducto('form_id_clasificacion_quimica') === (string) $clasificacionQuimica->id)>
                            {{ $clasificacionQuimica->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-12">
                <label class="producto-field-label" for="form_observacion_toxicologica_muestra">
                    Observación toxicológica de muestra
                </label>
                <textarea class="producto-textarea{{ $claseErrorProducto('form_observacion_toxicologica_muestra') }}"
                    id="form_observacion_toxicologica_muestra" name="form_observacion_toxicologica_muestra"
                    rows="3" maxlength="2000"
                    placeholder="Ej.: DL-50 oral aguda > 2000 mg/kg">{{ $valorProducto('form_observacion_toxicologica_muestra') }}</textarea>
                <p class="producto-field-help">Puede incluir números, signos y unidades tal como figuran en el análisis.</p>
                @error('form_observacion_toxicologica_muestra')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-6">
                <div class="producto-field-head">
                    <label class="producto-field-label !mb-0" for="form_id_clasificacion_toxicologica">Clasificación toxicológica</label>
                    <button type="button" class="producto-field-link"
                        onclick="abrirModalProducto('modalClasificacionToxicologica')">
                        + Nueva clasificación
                    </button>
                </div>
                <select class="producto-select{{ $claseErrorProducto('form_id_clasificacion_toxicologica') }} producto-select-search"
                    id="form_id_clasificacion_toxicologica" name="form_id_clasificacion_toxicologica"
                    data-producto-buscador="1" data-producto-placeholder="Buscar clasificación toxicológica">
                    <option value="">Seleccione clasificación toxicológica</option>
                    @if (str_starts_with((string) old('form_id_clasificacion_toxicologica'), 'TEMP-') && old('form_clasificacion_toxicologica_temporal_codigo'))
                        <option value="{{ old('form_id_clasificacion_toxicologica') }}" data-temporal="1"
                            data-codigo="{{ old('form_clasificacion_toxicologica_temporal_codigo') }}"
                            data-descripcion="{{ old('form_clasificacion_toxicologica_temporal_descripcion') }}" selected>
                            {{ old('form_clasificacion_toxicologica_temporal_codigo') }}{{ old('form_clasificacion_toxicologica_temporal_descripcion') ? ' - ' . old('form_clasificacion_toxicologica_temporal_descripcion') : '' }}
                        </option>
                    @endif
                    @foreach ($clasificacionesToxicologicasCatalogo as $clasificacionToxicologica)
                        <option value="{{ $clasificacionToxicologica->id }}"
                            data-codigo="{{ $clasificacionToxicologica->codigo }}"
                            data-descripcion="{{ $clasificacionToxicologica->descripcion }}"
                            @selected($valorProducto('form_id_clasificacion_toxicologica') == $clasificacionToxicologica->id)>
                            {{ $clasificacionToxicologica->codigo }}{{ $clasificacionToxicologica->descripcion ? ' - ' . $clasificacionToxicologica->descripcion : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>
</div>
