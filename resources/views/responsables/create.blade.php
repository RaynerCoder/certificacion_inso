<x-admin-layout title="Responsables de Empresas | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Responsables de Empresas', 'href' => route('responsables_index')],
    ['name' => 'Asignar Responsable a Empresa'],
]">

    @php
        // Nombre legible de persona natural para no mostrar solo IDs en los selects.
        $nombrePersonaResponsable = function ($persona) {
            $natural = $persona->natural;

            return trim(implode(' ', array_filter([
                $natural?->nombres,
                $natural?->apellido_paterno,
                $natural?->apellido_materno,
            ]))) ?: 'Persona sin nombre';
        };
    @endphp

    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form action="{{ route('responsables_store') }}" method="POST" enctype="multipart/form-data"
            class="grid grid-cols-1 gap-4 lg:grid-cols-12">
            @csrf

            {{-- Relacion principal: empresa donde se asigna el responsable. --}}
            <div class="lg:col-span-6">
                <x-wire-native-select label="Empresa" name="form_id_empresa">
                    <option value="">Seleccione la empresa</option>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" @selected(old('form_id_empresa') == $empresa->id)>
                            {{ $empresa->razon_social }}{{ $empresa->persona?->nit ? ' - NIT: ' . $empresa->persona->nit : '' }}
                        </option>
                    @endforeach
                </x-wire-native-select>
            </div>

            {{-- Persona natural que actuara como representante/responsable. --}}
            <div class="lg:col-span-6">
                <x-wire-native-select label="Persona responsable" name="form_id_persona">
                    <option value="">Seleccione la persona</option>
                    @foreach ($personas as $persona)
                        <option value="{{ $persona->id }}" @selected(old('form_id_persona') == $persona->id)>
                            {{ $nombrePersonaResponsable($persona) }}{{ $persona->natural?->ci ? ' - CI: ' . $persona->natural->ci : '' }}
                        </option>
                    @endforeach
                </x-wire-native-select>
            </div>

            {{-- Rol del responsable: se guarda como responsables.id_rol. --}}
            <div class="lg:col-span-6">
                <x-wire-native-select label="Rol del responsable" name="form_id_rol">
                    <option value="">Seleccione el rol</option>
                    @foreach ($roles as $rol)
                        <option value="{{ $rol->id }}" @selected(old('form_id_rol') == $rol->id)>
                            {{ $rol->name }}
                        </option>
                    @endforeach
                </x-wire-native-select>
            </div>

            <div class="lg:col-span-3">
                <x-wire-datetime-picker label="Fecha de registro" name="form_fecha_registro" without-time
                    :value="old('form_fecha_registro')" />
            </div>

            <div class="lg:col-span-3">
                <x-wire-datetime-picker label="Fecha de baja" name="form_fecha_baja" without-time
                    :value="old('form_fecha_baja')" />
            </div>

            <div class="lg:col-span-4">
                <x-wire-native-select label="Estado" name="form_estado">
                    @foreach (['ACTIVO', 'INACTIVO'] as $estado)
                        <option value="{{ $estado }}" @selected(old('form_estado', 'ACTIVO') === $estado)>
                            {{ $estado }}
                        </option>
                    @endforeach
                </x-wire-native-select>
            </div>

            <div class="lg:col-span-8">
                <x-wire-input label="Documento de respaldo (PDF)" name="form_url_respaldo" type="file"
                    accept="application/pdf" />
            </div>

            <div class="flex flex-wrap justify-end gap-3 lg:col-span-12">
                <x-wire-button href="{{ route('responsables_index') }}" secondary>
                    Cancelar
                </x-wire-button>

                <x-wire-button type="submit" emerald>
                    Guardar responsable
                </x-wire-button>
            </div>
        </form>
    </div>
</x-admin-layout>
