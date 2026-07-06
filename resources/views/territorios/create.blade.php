<x-admin-layout title="Territorios | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Territorios',
        'href' => route('territorios_index'),
    ],
    [
        'name' => 'Crear',
    ],
]">


    <x-wire-card>
        <form action="{{ route('territorios_store') }}" method="POST" class="space-y-4">
            @csrf

            <x-wire-select label="Territorio Dependiente" name="form_id_padre_territorio"
                placeholder="Seleccione o ninguno" searchable :options="$territorios
                    ->map(
                        fn($elemento) => [
                            'id' => $elemento->id,
                            'nombre' => $elemento->nombre,
                        ],
                    )
                    ->toArray()" option-label="nombre" option-value="id" />


            <x-wire-input label="Nombre" id="nombre" name="form_nombre" type="text"
                placeholder="Nombre del territorio" value="{{ old('form_nombre') }}" />


            <x-wire-input label="Código" id="codigo" name="form_codigo" type="text"
                placeholder="Código del territorio" value="{{ old('form_codigo') }}" />


            {{-- Estados manejados por ahora: ACTIVO e INACTIVO. --}}
            <x-wire-select label="Estado" name="form_id_estado" placeholder="Seleccione o ninguno" :options="[['nombre' => 'Activo', 'id' => 'ACTIVO'], ['nombre' => 'Inactivo', 'id' => 'INACTIVO']]"
                option-label="nombre" option-value="id" />


            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>
    </x-wire-card>


</x-admin-layout>


<!--
            <x-wire-native-select label="Territorio Dependiente" name="form_id_padre_territorio">
                <option value="">Seleccione o ninguno</option>

                @foreach ($territorios as $elemento)
<option value="{{ $elemento->id }}" @selected(old('form_id_padre_territorio') == $elemento->id)>
                        {{ $elemento->nombre }}
                    </option>
@endforeach
            </x-wire-native-select>-->
