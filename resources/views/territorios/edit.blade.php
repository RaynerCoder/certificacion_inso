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
        'name' => 'Editar',
    ],
]">


    <x-wire-card>
        <form action="{{ route('territorios_update', $territorio) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-wire-native-select label="Territorio Dependiente" name="form_id_padre_territorio">
                <option value="">Seleccione o ninguno</option>
                @foreach ($listaTerritorios as $elemento)
                    <option value="{{ $elemento->id }}" @selected(old('form_id_padre_territorio', $territorio->id_padre_territorio) == $elemento->id)>
                        {{ $elemento->nombre }}
                    </option>
                @endforeach
            </x-wire-native-select>


            <x-wire-input label="Nombre" id="nombre" name="form_nombre" type="text"
                placeholder="Nombre del territorio" value="{{ old('form_nombre', $territorio->nombre) }}" />


            <x-wire-input label="Código" id="codigo" name="form_codigo" type="text"
                placeholder="Código del territorio" value="{{ old('form_codigo', $territorio->codigo) }}" />


            <x-wire-native-select label="Estado" name="form_id_estado">
                <option value="">Seleccione estado</option>

                {{-- Estados manejados por ahora: ACTIVO e INACTIVO. --}}
                <option value="ACTIVO" @selected(old('form_id_estado', $territorio->estado) === 'ACTIVO')>
                    Activo
                </option>

                <option value="INACTIVO" @selected(old('form_id_estado', $territorio->estado) === 'INACTIVO')>
                    Inactivo
                </option>
            </x-wire-native-select>

            
            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>
    </x-wire-card>


</x-admin-layout>
