{{-- Paso 1: datos principales de productos. --}}
<div class="producto-step" data-producto-step="0">
    <section class="producto-section">
        <div class="grid grid-cols-1 gap-x-5 gap-y-4 p-5 lg:grid-cols-12 m-2">


            <div class="lg:col-span-7">
                <label class="producto-field-label" for="form_id_importador_persona">Importador</label>
                <select class="producto-select{{ $claseErrorProducto('form_id_importador_persona') }}"
                    id="form_id_importador_persona" name="form_id_importador_persona">
                    <option value="">Seleccione el importador</option>
                    @foreach ($importadoresCatalogo as $persona)
                        <option value="{{ $persona->id }}" @selected(old('form_id_importador_persona') == $persona->id)>
                            {{ $nombrePersonaProducto($persona) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-5">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <label class="producto-field-label !mb-0" for="form_id_tipo_producto">Tipo de producto</label>
                    <button type="button" class="text-xs font-bold text-teal-700 hover:text-teal-900"
                        onclick="abrirModalProducto('modalTipoProducto')">
                        + Nuevo tipo
                    </button>
                </div>
                <select class="producto-select{{ $claseErrorProducto('form_id_tipo_producto') }}"
                    id="form_id_tipo_producto" name="form_id_tipo_producto">
                    <option value="">Seleccione el tipo</option>
                    @if (str_starts_with((string) old('form_id_tipo_producto'), 'TEMP-') && old('form_tipo_producto_temporal_descripcion'))
                        {{-- Conserva el tipo creado desde modal si el formulario vuelve con validacion. --}}
                        <option value="{{ old('form_id_tipo_producto') }}" data-temporal="1" selected>
                            {{ old('form_tipo_producto_temporal_descripcion') }}
                        </option>
                    @endif
                    @foreach ($tiposProductosCatalogo as $tipoProducto)
                        <option value="{{ $tipoProducto->id }}" @selected(old('form_id_tipo_producto') == $tipoProducto->id)>
                            {{ $tipoProducto->descripcion }}{{ $tipoProducto->codigo ? ' - ' . $tipoProducto->codigo : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="lg:col-span-4">
                <label class="producto-field-label" for="form_codigo">Código</label>
                <input class="producto-input{{ $claseErrorProducto('form_codigo') }}" id="form_codigo"
                    name="form_codigo" type="text" value="{{ old('form_codigo') }}" placeholder="Ej: PROD-PLAG-001">
                @error('form_codigo')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-8">
                <label class="producto-field-label" for="form_nombre_comercial">Nombre comercial</label>
                <input class="producto-input{{ $claseErrorProducto('form_nombre_comercial') }}"
                    id="form_nombre_comercial" name="form_nombre_comercial" type="text"
                    value="{{ old('form_nombre_comercial') }}" placeholder="Ej: GLIFOSATO 48 SL">
                @error('form_nombre_comercial')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-7">
                <label class="producto-field-label" for="form_nombre_cientifico">Nombre científico</label>
                <input class="producto-input{{ $claseErrorProducto('form_nombre_cientifico') }}"
                    id="form_nombre_cientifico" name="form_nombre_cientifico" type="text"
                    value="{{ old('form_nombre_cientifico') }}" placeholder="Ej: N-(fosfonometil) glicina">
                @error('form_nombre_cientifico')
                    <p class="producto-field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="lg:col-span-5">
                <label class="producto-field-label" for="form_clasificacion">Clasificación</label>
                <input class="producto-input{{ $claseErrorProducto('form_clasificacion') }}" id="form_clasificacion"
                    name="form_clasificacion" type="text" value="{{ old('form_clasificacion') }}"
                    placeholder="Ej: Herbicida agrícola">
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
                <div class="mb-2 flex items-center justify-between gap-2">
                    <label class="producto-field-label !mb-0" for="form_id_fabricante">Fabricante</label>
                    <button type="button" class="text-xs font-bold text-teal-700 hover:text-teal-900"
                        onclick="abrirModalProducto('modalFabricanteProducto')">
                        + Nuevo fabricante
                    </button>
                </div>
                <select class="producto-select{{ $claseErrorProducto('form_id_fabricante') }}" id="form_id_fabricante"
                    name="form_id_fabricante">
                    <option value="">Seleccione el fabricante</option>
                    @if (str_starts_with((string) old('form_id_fabricante'), 'TEMP-') && old('form_fabricante_temporal_nombre'))
                        {{-- Conserva el fabricante creado desde modal si Laravel devuelve errores en otro campo. --}}
                        <option value="{{ old('form_id_fabricante') }}" data-temporal="1" selected>
                            {{ old('form_fabricante_temporal_nombre') }}
                        </option>
                    @endif
                    @foreach ($fabricantesCatalogo as $fabricante)
                        <option value="{{ $fabricante->id }}" @selected(old('form_id_fabricante') == $fabricante->id)>
                            {{ $fabricante->nombre }}{{ $fabricante->razon_social ? ' - ' . $fabricante->razon_social : '' }}
                        </option>
                    @endforeach
                </select>
            </div>



            <div class="lg:col-span-2">
                <label class="producto-field-label" for="form_estado">Estado</label>
                <select class="producto-select{{ $claseErrorProducto('form_estado') }}" id="form_estado"
                    name="form_estado">
                    {{-- Estados usados por ahora: ACTIVO / INACTIVO. --}}
                    <option value="ACTIVO" @selected(old('form_estado', 'ACTIVO') === 'ACTIVO')>Activo</option>
                    <option value="INACTIVO" @selected(old('form_estado') === 'INACTIVO')>Inactivo</option>
                </select>
            </div>
        </div>
    </section>
</div>
