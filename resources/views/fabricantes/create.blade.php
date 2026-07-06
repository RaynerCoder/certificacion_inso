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
        'name' => 'Crear',
    ],
]">


    <x-wire-card>
        <form action="{{ route('fabricantes_store') }}" method="POST" class="space-y-4">
            @csrf

            <x-wire-input label="Nombre" id="nombre" name="form_nombre" type="text" placeholder="Nombre del fabricante"
                value="{{ old('form_nombre') }}" />

            <x-wire-textarea label="Descripción" id="descripcion" name="form_descripcion"
                placeholder="Descripción del fabricante"> {{ old('form_descripcion') }}
            </x-wire-textarea>

            <x-wire-input label="Razón Social" id="razon_social" name="form_razon_social" type="text"
                placeholder="Razón social del fabricante" value="{{ old('form_razon_social') }}" />

            <x-wire-input label="Estado" id="estado" name="form_estado" type="text"
                placeholder="Estado del fabricante" value="{{ old('form_estado') }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>
    </x-wire-card>


</x-admin-layout>
