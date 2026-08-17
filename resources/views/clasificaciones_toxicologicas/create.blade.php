<x-admin-layout title="Clasificaciones toxicológicas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Clasificaciones toxicológicas',
        'href' => route('clasificaciones_toxicologicas_index'),
    ],
    [
        'name' => 'Crear'
    ]
]">


    <x-wire-card>
        <form action="{{ route('clasificaciones_toxicologicas_store') }}" method="POST" class="space-y-4">
            @csrf

            <x-wire-input label="Código" id="codigo" name="form_codigo" type="text" placeholder="Ej: CLASE II"
                value="{{ old('form_codigo') }}" />

            <x-wire-textarea label="Descripción (opcional)" id="descripcion" name="form_descripcion"
                placeholder="Ej: Moderadamente peligroso">{{ old('form_descripcion') }}</x-wire-textarea>
               
            <x-wire-input label="Estado" id="estado" name="form_estado" type="text" placeholder="Estado de la clasificación toxicológica"
                value="{{ old('form_estado') }}" />    

            <div class="flex justify-end">
                <x-button type="submit">
                    Guardar
                </x-button>
            </div>

        </form>
    </x-wire-card>


</x-admin-layout>
