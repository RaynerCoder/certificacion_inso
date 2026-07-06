<x-admin-layout title="Fabricantes | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Fabricantes',
        'href' => route('fabricantes_index'),
    ],
    [
        'name' => 'Editar'
    ]
]">


    <x-wire-card>
        <form action="{{ route('fabricantes_update', $fabricante) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-wire-input label="Nombre" id="nombre" name="form_nombre" type="text" placeholder="Nombre del fabricante"
                value="{{ old('form_nombre', $fabricante->nombre) }}" />

            <x-wire-textarea label="Descripción" id="descripcion" name="form_descripcion"
                placeholder="Descripción del fabricante"> {{ old('form_descripcion', $fabricante->descripcion) }}
            </x-wire-textarea>

            <x-wire-input label="Razón Social" id="razon_social" name="form_razon_social" type="text"
                placeholder="Razón social del fabricante" value="{{ old('form_razon_social', $fabricante->razon_social) }}" />

            <x-wire-input label="Estado" id="estado" name="form_estado" type="text"
                placeholder="Estado del fabricante" value="{{ old('form_estado', $fabricante->estado) }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>
    </x-wire-card>


</x-admin-layout>