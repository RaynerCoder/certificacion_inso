{{-- CUENTA DE USUARIO --}}
@php
    $esEdicionCuenta = ($modoCuenta ?? 'crear') === 'editar';
    $usuarioCuenta = ($usuarioCuentaAcceso ?? null) ?: ($persona->usuario ?? null);
    $cuentaExistente = (bool) $usuarioCuenta;
@endphp

<div class="wizard-section-block">
    <div class="wizard-section-heading">
        <span class="wizard-section-number">1</span>
        <div>
            <h3 id="tituloCuentaAccesoPersona">Cuenta de acceso al sistema</h3>
            <p id="descripcionCuentaAccesoPersona">Credenciales que se utilizaran para iniciar sesion.</p>
        </div>
    </div>

    <div id="persona_cuenta_usuario_panel" class="persona-account-panel">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-wire-input label="Nombre de usuario" id="form_usuario_name" name="form_usuario_name"
                placeholder="Nombre de usuario" value="{{ old('form_usuario_name', $usuarioCuenta->name ?? '') }}" />

            <x-wire-input label="Correo de acceso" id="form_usuario_email" name="form_usuario_email" type="email"
                placeholder="correo@ejemplo.com" value="{{ old('form_usuario_email', $usuarioCuenta->email ?? '') }}" />

            <div>
                <label for="form_id_role_visible" class="mb-1 block text-sm font-medium text-slate-700">
                    Rol de acceso
                </label>

                <select id="form_id_role" name="form_id_role" class="hidden">
                    <option value="{{ $rolSolicitante->id }}" selected>{{ $rolSolicitante->name }}</option>
                </select>

                <input id="form_id_role_visible" type="text" value="{{ $rolSolicitante->name }}" readonly
                    class="w-full rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-slate-700 cursor-not-allowed">

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
