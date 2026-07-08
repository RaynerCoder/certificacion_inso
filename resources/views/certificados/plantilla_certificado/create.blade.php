<x-admin-layout title="Configurar plantilla | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
    ['name' => 'Crear'],
]">
    @include('certificados.plantilla_certificado.estilo')

    <script>
        // Datos mínimos para actualizar el resumen cuando se selecciona el tipo de certificado.
        window.tiposCertificadosPlantilla = @json($tiposCertificadosPlantillaJson);
    </script>

    <form class="plantilla-shell" action="{{ route('certificados_plantillas_store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        {{-- El lienzo se convierte a JSON antes de enviar el formulario. --}}
        <input type="hidden" name="elementos_plantilla" data-plantilla-elementos-input value="[]">
        <input type="hidden" name="quitar_fondo_plantilla" value="0" data-plantilla-fondo-quitar-input>

        <section class="plantilla-card">
            <div class="plantilla-head">
                <div>
                    <h1 class="plantilla-title">Configurar plantilla de certificado</h1>
                    <p class="plantilla-subtitle">
                        Seleccione el tipo de certificado, suba una plantilla base y coloque los datos que deben aparecer.
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <x-wire-button href="{{ route('certificados_plantillas_index') }}" secondary>
                        Volver
                    </x-wire-button>
                    <x-wire-button type="submit" emerald>
                        Guardar plantilla
                    </x-wire-button>
                </div>
            </div>

            <div class="plantilla-step-title">
                <span>1</span>
                Datos principales
            </div>

            <div class="grid gap-4 p-4 lg:grid-cols-12">
                <div class="lg:col-span-4">
                    <x-wire-input label="Nombre de la plantilla" name="form_nombre" value="{{ old('form_nombre', 'Plantilla oficial') }}" />
                </div>

                <div class="lg:col-span-5">
                    <x-wire-native-select label="Tipo de certificado" name="form_id_tipo_certificado" data-plantilla-tipo>
                        <option value="">Seleccione</option>
                        @foreach ($tiposCertificados as $tipoCertificado)
                            <option value="{{ $tipoCertificado->id }}" @selected(old('form_id_tipo_certificado') == $tipoCertificado->id)>
                                {{ $tipoCertificado->nombre }}
                            </option>
                        @endforeach
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-3">
                    <x-wire-native-select label="Estado" name="form_estado">
                        <option value="ACTIVO" @selected(old('form_estado', 'ACTIVO') === 'ACTIVO')>Activo</option>
                        <option value="INACTIVO" @selected(old('form_estado') === 'INACTIVO')>Inactivo</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-6">
                    <label class="mb-1 block text-sm font-semibold text-slate-700">Fondo o plantilla</label>
                    <input type="file" name="form_url_fondo" accept="image/png,image/jpeg,image/webp,.png,.jpg,.jpeg,.webp" class="hidden" data-plantilla-fondo-input>
                    <div class="plantilla-file-control">
                        <button type="button" class="plantilla-file-btn is-select" data-plantilla-fondo-seleccionar>Seleccionar</button>
                        <button type="button" class="plantilla-file-btn" data-plantilla-fondo-ver disabled>Ver</button>
                        <button type="button" class="plantilla-file-btn is-danger" data-plantilla-fondo-quitar disabled>Quitar</button>
                        <span class="plantilla-file-name" data-plantilla-fondo-nombre>Sin archivo seleccionado</span>
                    </div>
                </div>

                <div class="lg:col-span-3">
                    <x-wire-native-select label="Tamaño de papel" name="form_tamano_papel">
                        <option value="CARTA" @selected(old('form_tamano_papel', 'CARTA') === 'CARTA')>Carta</option>
                        <option value="OFICIO" @selected(old('form_tamano_papel') === 'OFICIO')>Oficio</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-3">
                    <x-wire-native-select label="Orientación" name="form_orientacion">
                        <option value="VERTICAL" @selected(old('form_orientacion', 'VERTICAL') === 'VERTICAL')>Vertical</option>
                        <option value="HORIZONTAL" @selected(old('form_orientacion') === 'HORIZONTAL')>Horizontal</option>
                    </x-wire-native-select>
                </div>

                <div class="lg:col-span-8">
                    <x-wire-textarea label="Descripción" name="form_descripcion" rows="2">{{ old('form_descripcion') }}</x-wire-textarea>
                </div>

                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-3 lg:col-span-4" data-plantilla-resumen-tipo>
                    <span class="text-sm text-slate-500">Seleccione un tipo de certificado.</span>
                </div>
            </div>
        </section>

        <section class="plantilla-card">
            <div class="plantilla-step-title">
                <span>2</span>
                Diseño de plantilla
            </div>
        </section>

        <section class="plantilla-grid">
            <aside class="plantilla-panel">
                <div class="plantilla-panel-title">Campos del sistema</div>
                <div class="plantilla-panel-body">
                    @foreach ($camposPlantilla as $grupo => $campos)
                        <details class="plantilla-field-group" @if ($loop->first) open @endif>
                            <summary class="plantilla-field-title">
                                <span>{{ $grupo }}</span>
                                <span>{{ count($campos) }} campos</span>
                            </summary>

                            <div class="plantilla-field-list">
                                @foreach ($campos as $campo)
                                    <button type="button" class="plantilla-field"
                                        data-plantilla-campo
                                        data-codigo="{{ $campo['codigo'] }}"
                                        data-nombre="{{ $campo['nombre'] }}">
                                        <span class="plantilla-field-code">{{ $campo['codigo'] }}</span>
                                        <span class="plantilla-field-name">{{ $campo['nombre'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </aside>

            <main class="plantilla-panel">
                <div class="plantilla-toolbar">
                    <button type="button" class="plantilla-tool" data-plantilla-tool="texto">Agregar texto fijo</button>
                    <button type="button" class="plantilla-tool" data-plantilla-tool="campo">Agregar campo</button>
                    <button type="button" class="plantilla-tool" data-plantilla-tool="tabla">Agregar tabla</button>
                    <button type="button" class="plantilla-tool" data-plantilla-tool="firma">Agregar firma</button>
                    <button type="button" class="plantilla-tool" data-plantilla-tool="qr">Agregar QR</button>
                </div>

                <div class="plantilla-canvas-wrap">
                    <div class="plantilla-canvas">
                        <img data-plantilla-fondo-preview class="plantilla-fondo" alt="Fondo de plantilla">
                        <div class="plantilla-paper-content">
                            <div class="plantilla-drop-zone" data-plantilla-lienzo>
                                <div class="plantilla-empty-message" data-plantilla-empty>
                                    Suba una plantilla o agregue campos para empezar a ubicar la información.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <aside class="plantilla-panel">
                <div class="plantilla-panel-title">Propiedades</div>
                <div class="plantilla-panel-body">
                    <div class="plantilla-summary" data-plantilla-propiedades>
                        <div>
                            <span class="text-xs font-bold uppercase text-slate-400">Campo seleccionado</span>
                            <div class="font-bold text-slate-800" data-prop-codigo>Sin campo seleccionado</div>
                        </div>
                        <div>
                            <span class="text-xs font-bold uppercase text-slate-400">Nombre visible</span>
                            <div class="text-slate-700" data-prop-nombre>Seleccione un campo del lienzo.</div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3">
                        <x-wire-input label="Posición X" type="number" min="0" value="0" data-prop-x />
                        <x-wire-input label="Posición Y" type="number" min="0" value="0" data-prop-y />
                        <x-wire-input label="Ancho" type="number" min="35" value="210" data-prop-ancho />
                        <x-wire-input label="Alto" type="number" min="18" value="34" data-prop-alto />
                        <x-wire-input label="Tamaño de letra" type="number" min="6" value="12" data-prop-tamano-letra />
                        <div class="plantilla-format-row">
                            <button type="button" class="plantilla-format-btn" data-prop-accion="letra_menos">A-</button>
                            <button type="button" class="plantilla-format-btn" data-prop-accion="letra_mas">A+</button>
                            <button type="button" class="plantilla-format-btn" data-prop-accion="negrita">B</button>
                            <button type="button" class="plantilla-format-btn" data-prop-accion="cursiva">I</button>
                            <button type="button" class="plantilla-format-btn" data-prop-accion="subrayado">U</button>
                            <input type="color" value="#0f172a" class="plantilla-color-input" title="Color de texto" data-prop-color-texto>
                        </div>
                        <x-wire-native-select label="Alineación" data-prop-alineacion>
                            <option>Izquierda</option>
                            <option>Centro</option>
                            <option>Derecha</option>
                        </x-wire-native-select>
                        <label class="plantilla-toggle">
                            <input type="checkbox" data-prop-negrita>
                            <span>Texto en negrita</span>
                        </label>
                        <button type="button" class="plantilla-remove-field" data-plantilla-quitar-campo disabled>
                        <label class="plantilla-toggle">
                            <input type="checkbox" data-prop-cursiva>
                            <span>Texto en cursiva</span>
                        </label>
                        <label class="plantilla-toggle">
                            <input type="checkbox" data-prop-subrayado>
                            <span>Texto subrayado</span>
                        </label>
                            Quitar campo seleccionado
                        </button>
                    </div>

                    <div class="mt-5">
                        <div class="plantilla-panel-title rounded-lg">Requisitos del tipo seleccionado</div>
                        <div class="mt-3 grid gap-2" data-plantilla-requisitos>
                            <div class="text-sm text-slate-500">Sin requisitos para mostrar.</div>
                        </div>
                    </div>
                </div>
            </aside>
        </section>

        <section class="plantilla-card">
            <div class="plantilla-step-title is-amber">
                <span>3</span>
                Revisión y guardado
            </div>
            <div class="plantilla-actions">
                <span class="mr-auto text-sm text-slate-600">
                    Esta plantilla tiene <strong data-plantilla-contador-campos>0</strong> campos colocados.
                </span>
                <x-wire-button href="{{ route('certificados_plantillas_index') }}" secondary>
                    Salir sin guardar
                </x-wire-button>
                <x-wire-button type="button" blue data-plantilla-vista-previa>
                    Vista previa
                </x-wire-button>
                <x-wire-button type="button" secondary data-plantilla-imprimir-prueba>
                    Imprimir prueba
                </x-wire-button>
                <x-wire-button type="submit" emerald>
                    Guardar plantilla
                </x-wire-button>
            </div>
        </section>
    </form>

    @include('certificados.plantilla_certificado.script')
</x-admin-layout>
