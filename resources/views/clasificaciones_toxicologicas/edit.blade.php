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
        'name' => 'Editar'
    ]
]">

    <x-wire-card>
        <form action="{{ route('clasificaciones_toxicologicas_update', $clasificacionToxicologica) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')

            <x-wire-textarea label="Descripción" id="descripcion" name="form_descripcion"
                placeholder="Descripción de la clasificación toxicológica"> {{ old('form_descripcion', $clasificacionToxicologica->descripcion) }}
            </x-wire-textarea>

            <x-wire-input label="Código" id="codigo" name="form_codigo" type="text" placeholder="Código de la clasificación toxicológica"
                value="{{ old('form_codigo', $clasificacionToxicologica->codigo) }}" />
               
            <x-wire-input label="Estado" id="estado" name="form_estado" type="text" placeholder="Estado de la clasificación toxicológica"
                value="{{ old('form_estado', $clasificacionToxicologica->estado) }}" />

            <div class="flex justify-end">
                <x-button type="submit">
                    Actualizar
                </x-button>
            </div>

        </form>
    </x-wire-card>

</x-admin-layout>
