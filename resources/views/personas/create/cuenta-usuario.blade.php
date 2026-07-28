{{-- CUENTA DE USUARIO --}}
@php
    $esEdicionCuenta = ($modoCuenta ?? 'crear') === 'editar';
    $usuarioCuenta = $persona->usuario ?? null;
    $cuentaExistente = (bool) $usuarioCuenta;
    $rolActualCuenta = $usuarioCuenta?->roles?->first()?->id;
    $rolSeleccionadoCuenta = old('form_id_role', $rolActualCuenta);
@endphp

<div class="wizard-section-block">
    <div class="wizard-section-heading">
        <span class="wizard-section-number">1</span>
        <div>
            <h3>Cuenta de acceso al sistema</h3>
            <p>Revise o cambie las credenciales que usara la persona o empresa para iniciar sesion.</p>
        </div>
    </div>

    <div id="persona_cuenta_usuario_panel" class="persona-account-panel">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-wire-input label="Nombre de usuario" id="form_usuario_name" name="form_usuario_name"
                placeholder="Ej: CI o NIT" value="{{ old('form_usuario_name', $usuarioCuenta->name ?? '') }}" />

            <x-wire-input label="Correo de acceso" id="form_usuario_email" name="form_usuario_email" type="email"
                placeholder="correo@ejemplo.com" value="{{ old('form_usuario_email', $usuarioCuenta->email ?? '') }}" />

            <div class="ocupacion-persona-autocomplete" data-rol-cuenta>
                <label for="buscadorRolCuenta" class="mb-1 block text-sm font-medium text-slate-700">
                    Rol de acceso
                </label>

                <select id="form_id_role" name="form_id_role" class="hidden">
                    <option value="">Seleccione el rol</option>
                    @foreach ($rolesCuentaCatalogo as $rolCuenta)
                        <option value="{{ $rolCuenta->id }}" @selected((string) $rolSeleccionadoCuenta === (string) $rolCuenta->id)>
                            {{ $rolCuenta->name }}
                        </option>
                    @endforeach
                </select>

                <div class="ocupacion-persona-control">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>

                    <input type="search" id="buscadorRolCuenta" data-rol-cuenta-buscar
                        placeholder="Escriba el nombre del rol" autocomplete="off">

                    <button type="button" class="ocupacion-persona-limpiar" data-rol-cuenta-limpiar
                        aria-label="Quitar rol seleccionado">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="ocupacion-persona-resultados" data-rol-cuenta-resultados></div>

                @error('form_id_role')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <x-wire-input label="{{ $esEdicionCuenta && $cuentaExistente ? 'Nueva contrasena opcional' : 'Contrasena opcional' }}"
                    id="form_usuario_password" name="form_usuario_password" type="text"
                    placeholder="{{ $esEdicionCuenta && $cuentaExistente ? 'Vacio mantiene la actual' : 'Vacio genera una aleatoria al guardar' }}"
                    value="{{ old('form_usuario_password') }}" />

                <button type="button" class="persona-account-link" onclick="generarPasswordCuentaPersona(true)">
                    Generar contrasena aleatoria
                </button>
            </div>
        </div>

        <p class="persona-account-note">
            {{ $esEdicionCuenta
                ? ($cuentaExistente
                    ? 'La contrasena solo cambia si escribe o genera una nueva. Si deja el campo vacio, se mantiene la actual.'
                    : 'Esta persona todavia no tiene cuenta. Puede escribir una contrasena o dejar que el sistema genere una al guardar.')
                : 'Puede escribir una contrasena o dejar el campo vacio para que el sistema genere una al guardar.' }}
        </p>
    </div>
</div>
