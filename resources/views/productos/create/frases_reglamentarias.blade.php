{{-- Paso 4: frases reglamentarias de riesgo y seguridad del producto. --}}
@php
    $frasesSeleccionadas = collect(old('frases_reglamentarias', $frasesReglamentariasIniciales ?? []))
        ->map(fn ($idFrase) => (string) $idFrase)
        ->flip();
    $frasesRiesgo = $frasesReglamentariasCatalogo->where('tipo', 'RIESGO')->values();
    $frasesSeguridad = $frasesReglamentariasCatalogo->where('tipo', 'SEGURIDAD')->values();
    $frasesTemporalesOld = collect(old('frases_reglamentarias_temporales', []))
        ->map(function ($frase, $idTemporal) {
            return (object) [
                'id' => (string) $idTemporal,
                'tipo' => $frase['tipo'] ?? '',
                'codigo' => $frase['codigo'] ?? '',
                'descripcion' => $frase['descripcion'] ?? '',
            ];
        });
    $frasesRiesgoSeleccionadas = $frasesRiesgo
        ->filter(fn ($frase) => $frasesSeleccionadas->has((string) $frase->id))
        ->concat($frasesTemporalesOld->filter(fn ($frase) => $frase->tipo === 'RIESGO' && $frasesSeleccionadas->has($frase->id)))
        ->values();
    $frasesSeguridadSeleccionadas = $frasesSeguridad
        ->filter(fn ($frase) => $frasesSeleccionadas->has((string) $frase->id))
        ->concat($frasesTemporalesOld->filter(fn ($frase) => $frase->tipo === 'SEGURIDAD' && $frasesSeleccionadas->has($frase->id)))
        ->values();
    $frasesSeleccionables = $frasesReglamentariasCatalogo->where('estado', 'ACTIVO')->values();
    $tieneErrorFrases = $errors->has('frases_reglamentarias') || $errors->has('frases_reglamentarias.*');
@endphp

<div class="producto-step" data-producto-step="3">
    <section class="producto-section">
        <div class="producto-step-grid space-y-5">
            <div class="producto-frases-selector">
                <div class="producto-frases-selector-field">
                    <div class="producto-field-label-row">
                        <label class="producto-field-label !mb-0" for="form_frase_reglamentaria_select">Frase reglamentaria</label>
                        <button type="button" class="text-xs font-bold text-teal-700 hover:text-teal-900"
                            onclick="abrirModalFraseReglamentariaProducto()">
                            + Nueva frase
                        </button>
                    </div>
                    <select class="producto-select" id="form_frase_reglamentaria_select" data-producto-buscador="1"
                        data-producto-placeholder="Buscar por código o frase">
                        <option value="">Buscar y seleccionar una frase</option>
                        @foreach ($frasesSeleccionables as $frase)
                            <option value="{{ $frase->id }}" data-tipo="{{ $frase->tipo }}"
                                data-codigo="{{ $frase->codigo }}" data-descripcion="{{ $frase->descripcion }}"
                                data-producto-detalle="{{ \App\Models\FraseReglamentaria::TIPOS[$frase->tipo] ?? $frase->tipo }}">
                                {{ $frase->codigo }} — {{ $frase->descripcion }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($tieneErrorFrases)
                <p class="producto-field-error producto-frases-error">
                    {{ $errors->first('frases_reglamentarias') ?: $errors->first('frases_reglamentarias.*') }}
                </p>
            @endif

            {{-- Las frases nuevas permanecen como datos temporales hasta guardar el producto. --}}
            <div id="frasesReglamentariasTemporalesProducto" hidden>
                @foreach (old('frases_reglamentarias_temporales', []) as $idTemporal => $fraseTemporal)
                    <input type="hidden" name="frases_reglamentarias_temporales[{{ $idTemporal }}][tipo]"
                        value="{{ $fraseTemporal['tipo'] ?? '' }}">
                    <input type="hidden" name="frases_reglamentarias_temporales[{{ $idTemporal }}][codigo]"
                        value="{{ $fraseTemporal['codigo'] ?? '' }}">
                    <input type="hidden" name="frases_reglamentarias_temporales[{{ $idTemporal }}][descripcion]"
                        value="{{ $fraseTemporal['descripcion'] ?? '' }}">
                @endforeach
            </div>

            <div id="frases_reglamentarias" class="producto-frases-grid {{ $tieneErrorFrases ? 'is-invalid' : '' }}">
                <section class="producto-frases-group" aria-labelledby="tituloFrasesRiesgo">
                    <header class="producto-frases-group-head is-risk">
                        <div>
                            <span>Riesgo</span>
                            <h4 id="tituloFrasesRiesgo">Frases de riesgo</h4>
                        </div>
                        <strong data-frases-total="RIESGO">{{ $frasesRiesgoSeleccionadas->count() }}</strong>
                    </header>

                    <div id="listaFrasesRiesgoProducto" class="producto-frases-list" data-frases-list="RIESGO"
                        data-mensaje-vacio="No seleccionó frases de riesgo.">
                        @forelse ($frasesRiesgoSeleccionadas as $frase)
                            <article class="producto-frase-option is-selected" data-frase-reglamentaria-id="{{ $frase->id }}">
                                <input type="hidden" name="frases_reglamentarias[]" value="{{ $frase->id }}"
                                    data-tipo="RIESGO" data-codigo="{{ $frase->codigo }}"
                                    data-descripcion="{{ $frase->descripcion }}">
                                <span class="producto-frase-check"><i class="fa-solid fa-check"></i></span>
                                <span class="producto-frase-copy"><strong>{{ $frase->codigo }}</strong><span>{{ $frase->descripcion }}</span></span>
                                <button type="button" class="producto-frase-remove" title="Quitar frase {{ $frase->codigo }}"
                                    onclick="quitarFraseReglamentariaProducto('{{ $frase->id }}')">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </article>
                        @empty
                            <p class="producto-frases-empty">No seleccionó frases de riesgo.</p>
                        @endforelse
                    </div>
                </section>

                <section class="producto-frases-group" aria-labelledby="tituloFrasesSeguridad">
                    <header class="producto-frases-group-head is-safety">
                        <div>
                            <span>Seguridad</span>
                            <h4 id="tituloFrasesSeguridad">Frases de seguridad</h4>
                        </div>
                        <strong data-frases-total="SEGURIDAD">{{ $frasesSeguridadSeleccionadas->count() }}</strong>
                    </header>

                    <div id="listaFrasesSeguridadProducto" class="producto-frases-list" data-frases-list="SEGURIDAD"
                        data-mensaje-vacio="No seleccionó frases de seguridad.">
                        @forelse ($frasesSeguridadSeleccionadas as $frase)
                            <article class="producto-frase-option is-selected" data-frase-reglamentaria-id="{{ $frase->id }}">
                                <input type="hidden" name="frases_reglamentarias[]" value="{{ $frase->id }}"
                                    data-tipo="SEGURIDAD" data-codigo="{{ $frase->codigo }}"
                                    data-descripcion="{{ $frase->descripcion }}">
                                <span class="producto-frase-check"><i class="fa-solid fa-check"></i></span>
                                <span class="producto-frase-copy"><strong>{{ $frase->codigo }}</strong><span>{{ $frase->descripcion }}</span></span>
                                <button type="button" class="producto-frase-remove" title="Quitar frase {{ $frase->codigo }}"
                                    onclick="quitarFraseReglamentariaProducto('{{ $frase->id }}')">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </article>
                        @empty
                            <p class="producto-frases-empty">No seleccionó frases de seguridad.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </section>
</div>
