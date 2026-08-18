{{-- Paso 1: datos principales de productos. --}}
<div class="producto-step" data-producto-step="0">
    <section class="producto-section">
        <div class="producto-step-grid grid grid-cols-1 gap-x-4 gap-y-3 lg:grid-cols-12">
            <div class="lg:col-span-5">
                <label class="producto-field-label" for="form_id_importador_persona">Importador</label>
                <select class="producto-select{{ $claseErrorProducto('form_id_importador_persona') }}"
                    id="form_id_importador_persona" name="form_id_importador_persona">
                    <option value="">Seleccione importador</option>
                    @foreach ($importadoresCatalogo as $persona)
                        @php
                            $importadorSeleccionado = old('form_id_importador_persona', request('form_id_importador_persona'));
                        @endphp
                        <option value="{{ $persona->id }}" @selected((string) $importadorSeleccionado === (string) $persona->id)>
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
                <select class="producto-select{{ $claseErrorProducto('form_id_fabricante') }}" id="form_id_fabricante"
                    name="form_id_fabricante">
                    <option value="">Seleccione fabricante</option>
                    @if (str_starts_with((string) old('form_id_fabricante'), 'TEMP-') && old('form_fabricante_temporal_nombre'))
                        <option value="{{ old('form_id_fabricante') }}" data-temporal="1"
                            data-nombre="{{ old('form_fabricante_temporal_nombre') }}"
                            data-razon-social="{{ old('form_fabricante_temporal_razon_social') }}" selected>
                            {{ old('form_fabricante_temporal_razon_social') ? old('form_fabricante_temporal_razon_social') . ' - ' : '' }}{{ old('form_fabricante_temporal_nombre') }}
                        </option>
                    @endif
                    @foreach ($fabricantesCatalogo as $fabricante)
                        <option value="{{ $fabricante->id }}" data-nombre="{{ $fabricante->nombre }}"
                            data-razon-social="{{ $fabricante->razon_social }}" @selected(old('form_id_fabricante') == $fabricante->id)>
                            {{ $fabricante->razon_social ? $fabricante->razon_social . ' - ' : '' }}{{ $fabricante->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-2">
                <label class="producto-field-label" for="form_estado">Estado</label>
                <select class="producto-select{{ $claseErrorProducto('form_estado') }}" id="form_estado"
                    name="form_estado">
                    <option value="ACTIVO" @selected(old('form_estado', 'ACTIVO') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_estado') === 'INACTIVO')>Inactivo</option>
                </select>
            </div>

            <div class="lg:col-span-3">
                <label class="producto-field-label" for="form_codigo">Código</label>
                <input class="producto-input{{ $claseErrorProducto('form_codigo') }}" id="form_codigo"
                    name="form_codigo" type="text" value="{{ old('form_codigo') }}" placeholder="Ej: PROD-PLAG-001">
                @error('form_codigo')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-5">
                <label class="producto-field-label" for="form_nombre_comercial">Nombre comercial</label>
                <input class="producto-input{{ $claseErrorProducto('form_nombre_comercial') }}"
                    id="form_nombre_comercial" name="form_nombre_comercial" type="text"
                    value="{{ old('form_nombre_comercial') }}" placeholder="Ej: GLIFOSATO 48 SL">
                @error('form_nombre_comercial')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-4">
                <label class="producto-field-label" for="form_id_territorio_pais">País</label>
                <select class="producto-select{{ $claseErrorProducto('form_id_territorio_pais') }}"
                    id="form_id_territorio_pais" name="form_id_territorio_pais">
                    <option value="">Seleccione el país</option>
                    @foreach ($territoriosCatalogo as $territorio)
                        <option value="{{ $territorio->id }}" @selected(old('form_id_territorio_pais') == $territorio->id)>
                            {{ $territorio->nombre }}{{ $territorio->codigo ? ' - ' . $territorio->codigo : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-6">
                <label class="producto-field-label" for="form_tipo_producto">Tipo de producto</label>
                <input class="producto-input{{ $claseErrorProducto('form_tipo_producto') }}"
                    id="form_tipo_producto" name="form_tipo_producto" type="text"
                    value="{{ old('form_tipo_producto') }}" placeholder="Ej: Plaguicida o Materia Prima" oninput="this.value = this.value.toUpperCase()">
                @error('form_tipo_producto')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-6">
                <label class="producto-field-label" for="form_nombre_tecnico">Nombre técnico</label>
                <input class="producto-input{{ $claseErrorProducto('form_nombre_tecnico') }}"
                    id="form_nombre_tecnico" name="form_nombre_tecnico" type="text"
                    value="{{ old('form_nombre_tecnico') }}" placeholder="Ingrese el nombre técnico">
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
                    id="form_id_clasificacion_quimica" name="form_id_clasificacion_quimica" data-producto-buscador="1">
                    <option value="">Seleccione clasificación química</option>
                    @if (str_starts_with((string) old('form_id_clasificacion_quimica'), 'TEMP-') && old('form_clasificacion_quimica_temporal_nombre'))
                        <option value="{{ old('form_id_clasificacion_quimica') }}" data-temporal="1" selected>
                            {{ old('form_clasificacion_quimica_temporal_nombre') }}
                        </option>
                    @endif
                    @foreach ($clasificacionesQuimicasCatalogo as $clasificacionQuimica)
                        <option value="{{ $clasificacionQuimica->id }}"
                            @selected((string) old('form_id_clasificacion_quimica') === (string) $clasificacionQuimica->id)>
                            {{ $clasificacionQuimica->nombre }}
                        </option>
                    @endforeach
                </select>
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
                    data-producto-buscador="1">
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
                            @selected(old('form_id_clasificacion_toxicologica') == $clasificacionToxicologica->id)>
                            {{ $clasificacionToxicologica->codigo }}{{ $clasificacionToxicologica->descripcion ? ' - ' . $clasificacionToxicologica->descripcion : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </section>
</div>

