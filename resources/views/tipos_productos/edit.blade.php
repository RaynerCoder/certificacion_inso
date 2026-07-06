<x-admin-layout title="Tipos de Productos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Tipos de Productos',
        'href' => route('tipos_productos_index'),
    ],
    [
        'name' => 'Editar'
    ]
]">

    <x-wire-card>
        <form action="{{ route('tipos_productos_update', $tipoProducto) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-wire-textarea label="Descripción" id="descripcion" name="form_descripcion"
                placeholder="Descripción del tipo de producto"> {{ old('form_descripcion', $tipoProducto->descripcion) }}
            </x-wire-textarea>

            <x-wire-input label="Código" id="codigo" name="form_codigo" type="text" placeholder="Código del tipo de producto"
                value="{{ old('form_codigo', $tipoProducto->codigo) }}" />
               
            <x-wire-input label="Estado" id="estado" name="form_estado" type="text" placeholder="Estado del tipo de producto"
                value="{{ old('form_estado', $tipoProducto->estado) }}" />    

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>
    </x-wire-card>

</x-admin-layout>