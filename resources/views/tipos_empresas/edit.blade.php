<x-admin-layout title="Tipos de Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Tipos de Empresas',
        'href' => route('tipos_empresas_index'),
    ],
    [
        'name' => 'Editar',
    ],
]">


    <x-wire-card>
        <form action="{{ route('tipos_empresas_update', $tipoEmpresa) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-wire-textarea label="Descripción" id="descripcion" name="form_descripcion"
                placeholder="Descripción del fabricante"> {{ old('form_descripcion', $tipoEmpresa->descripcion) }}
            </x-wire-textarea>

            <x-wire-input label="Estado" id="estado" name="form_estado" type="text"
                placeholder="Estado del fabricante" value="{{ old('form_estado', $tipoEmpresa->estado) }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>
    </x-wire-card>


</x-admin-layout>

