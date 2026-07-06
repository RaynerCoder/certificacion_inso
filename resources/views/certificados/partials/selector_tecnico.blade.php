@php
    /*
     * Selector visual de funcionario.
     * Mantiene un select real con name/id para que el controlador reciba el mismo dato.
     */
    $selectId = $selectId ?? 'id_tecnico';
    $selectName = $selectName ?? 'id_tecnico';
    $selectLabel = $selectLabel ?? 'Tecnico asignado';
    $placeholder = $placeholder ?? 'Seleccione funcionario';
    $helpText = $helpText ?? 'Busque por nombre, cargo o area';
    $oldValue = (string) ($oldValue ?? old($selectName));

    $excluirIds = collect($excluirIds ?? [])
        ->filter(fn ($id) => filled($id))
        ->map(fn ($id) => (int) $id)
        ->values();

    $opcionesTecnicos = collect($tecnicos ?? [])
        ->reject(fn ($tecnico) => $excluirIds->contains((int) $tecnico->id))
        ->map(function ($tecnico) {
            // Nombre visible: se toma de la ficha de funcionario, no del usuario de acceso.
            $nombreFuncionario = $tecnico->funcionario
                ? trim(implode(' ', array_filter([
                    $tecnico->funcionario->nombres,
                    $tecnico->funcionario->apellido_paterno,
                    $tecnico->funcionario->apellido_materno,
                ])))
                : '';

            $nombreVisible = $nombreFuncionario ?: ($tecnico->email ?: 'Funcionario sin nombre');
            $cargos = $tecnico->funcionario?->cargos ?? collect();
            $textoCargo = $cargos->pluck('nombre')->filter()->unique()->implode(', ') ?: 'Sin cargo';
            $textoArea = $cargos->pluck('area.nombre')->filter()->unique()->implode(', ') ?: 'Sin area';
            $cargaActual = (int) ($tecnico->carga_actual ?? 0);
            $textoCarga = $cargaActual . ' tramite' . ($cargaActual === 1 ? ' activo' : 's activos');

            return [
                'id' => (int) $tecnico->id,
                'nombre' => $nombreVisible,
                'cargo' => $textoCargo,
                'area' => $textoArea,
                'carga' => $textoCarga,
                'busqueda' => \Illuminate\Support\Str::lower($nombreVisible . ' ' . $textoCargo . ' ' . $textoArea . ' ' . $textoCarga),
            ];
        })
        ->values();

    $tecnicoSeleccionado = $opcionesTecnicos->firstWhere('id', (int) $oldValue);
@endphp

<div class="cert-technical-field" data-technical-selector>
    <label class="cert-show-label" for="{{ $selectId }}">{{ $selectLabel }}</label>

    {{-- Select real: se mantiene para que Laravel reciba el mismo name en el POST. --}}
    <select id="{{ $selectId }}" class="cert-review-select cert-technical-native-select @error($selectName) is-invalid @enderror"
        name="{{ $selectName }}" data-technical-native>
        <option value="">{{ $placeholder }}</option>
        @foreach ($opcionesTecnicos as $opcion)
            <option value="{{ $opcion['id'] }}" @selected($oldValue !== '' && (int) $oldValue === $opcion['id'])>
                {{ $opcion['nombre'] }}
            </option>
        @endforeach
    </select>

    {{-- Control visible: muestra una lectura compacta y abre el buscador de funcionarios. --}}
    <button type="button" class="cert-technical-control" data-technical-toggle
        data-placeholder="{{ $placeholder }}" data-help="{{ $helpText }}" aria-expanded="false">
        <span class="cert-technical-avatar">
            <i class="fa-regular fa-user"></i>
        </span>

        <span class="cert-technical-selected">
            <span class="cert-technical-selected-name" data-technical-label>
                {{ $tecnicoSeleccionado['nombre'] ?? $placeholder }}
            </span>
            <span class="cert-technical-selected-help" data-technical-help>
                @if ($tecnicoSeleccionado)
                    {{ $tecnicoSeleccionado['cargo'] }} - {{ $tecnicoSeleccionado['area'] }}
                @else
                    {{ $helpText }}
                @endif
            </span>
        </span>

        <span class="cert-technical-chip {{ $tecnicoSeleccionado ? '' : 'is-hidden' }}" data-technical-chip>
            {{ $tecnicoSeleccionado['carga'] ?? '' }}
        </span>

        <i class="fa-solid fa-chevron-down cert-technical-chevron"></i>
    </button>

    <div class="cert-technical-dropdown" data-technical-menu hidden>
        <div class="cert-technical-search">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="search" data-technical-search placeholder="Buscar funcionario, cargo o area">
        </div>

        <div class="cert-technical-options">
            @forelse ($opcionesTecnicos as $opcion)
                <button type="button" class="cert-technical-option" data-technical-option
                    data-value="{{ $opcion['id'] }}"
                    data-label="{{ $opcion['nombre'] }}"
                    data-help="{{ $opcion['cargo'] }} - {{ $opcion['area'] }}"
                    data-chip="{{ $opcion['carga'] }}"
                    data-search="{{ $opcion['busqueda'] }}">
                    <span class="cert-technical-option-icon">
                        <i class="fa-regular fa-user"></i>
                    </span>

                    <span class="cert-technical-option-main">
                        <strong>{{ $opcion['nombre'] }}</strong>
                        <span>{{ $opcion['cargo'] }}</span>
                        <small>{{ $opcion['area'] }}</small>
                    </span>

                    <span class="cert-technical-option-chip">
                        {{ $opcion['carga'] }}
                    </span>
                </button>
            @empty
                <div class="cert-technical-empty">No hay funcionarios disponibles.</div>
            @endforelse

            <div class="cert-technical-empty is-hidden" data-technical-empty>
                No se encontraron funcionarios con ese criterio.
            </div>
        </div>
    </div>

    @error($selectName)
        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
    @enderror
</div>
