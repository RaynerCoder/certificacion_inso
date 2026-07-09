@php
    $esEdicion = $modo === 'editar';
    $rolesParaSelector = $roles->map(fn ($rol) => [
        'id' => $rol->id,
        'name' => $rol->name,
        'slug' => $rol->slug,
        'descripcion' => $rol->descripcion,
        'permisos' => $rol->permisos->map(fn ($permiso) => [
            'id' => $permiso->id,
            'nombre' => $permiso->nombre,
        ])->values(),
    ])->values();

    $permisosParaSelector = $permisos->map(fn ($permiso) => [
        'id' => $permiso->id,
        'nombre' => $permiso->nombre,
    ])->values();

    $cargosParaSelector = $cargos->map(fn ($cargo) => [
        'id' => $cargo->id,
        'nombre' => $cargo->nombre,
        'descripcion' => $cargo->descripcion,
        'area' => $cargo->area?->nombre,
    ])->values();

    $cargosNuevosSeleccionados = collect(old('form_cargos_nuevos', []))
        ->filter(fn ($cargo) => trim((string) $cargo) !== '')
        ->values();
@endphp

@if (session('error'))
    <div class="seg-alert">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="seg-alert">
        <p class="mb-2 font-bold">Revise los datos del formulario:</p>
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="seg-card is-blue">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-user-lock"></i>
            </span>
            <div>
                <h2 class="seg-card-title">{{ $esEdicion ? 'Editar usuario' : 'Crear usuario' }}</h2>
                <p class="seg-card-subtitle">Credenciales principales para ingresar al sistema.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="seg-grid">
            <div class="seg-col-6">
                <x-wire-input label="Nombre de usuario" id="form_name" name="form_name" type="text"
                    placeholder="Nombre visible de la cuenta" value="{{ old('form_name', $usuario->name ?? '') }}" required />
            </div>

            <div class="seg-col-6">
                <x-wire-input label="Correo de acceso" id="form_email" name="form_email" type="email"
                    placeholder="usuario@correo.com" value="{{ old('form_email', $usuario->email ?? '') }}" required />
            </div>

            <div class="seg-col-3">
                <x-wire-native-select label="Estado" id="form_estado" name="form_estado" required>
                    <option value="1" @selected((string) old('form_estado', $usuario->estado ?? '1') === '1')>Activo</option>
                    <option value="0" @selected((string) old('form_estado', $usuario->estado ?? '') === '0')>Inactivo</option>
                </x-wire-native-select>
            </div>

            <div class="seg-col-3"></div>

            <div class="seg-col-6">
                <x-wire-input label="Contrasena" id="form_password"
                    name="form_password" type="password" placeholder="Minimo 8 caracteres" required />
            </div>

            <div class="seg-col-6">
                <x-wire-input label="Confirmar contrasena" id="form_password_confirmation"
                    name="form_password_confirmation" type="password" placeholder="Repita la contrasena" required />
            </div>
        </div>
    </div>
</section>

<section class="seg-card is-emerald">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-id-card-clip"></i>
            </span>
            <div>
                <h2 class="seg-card-title">Datos del funcionario</h2>
                <p class="seg-card-subtitle">Ficha laboral vinculada uno a uno con la cuenta de usuario.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="seg-grid">
            <div class="seg-col-4">
                <x-wire-input label="Nombres" id="form_funcionario_nombres" name="form_funcionario_nombres" type="text"
                    placeholder="Nombres del funcionario"
                    value="{{ old('form_funcionario_nombres', $usuario->funcionario->nombres ?? '') }}" required />
            </div>

            <div class="seg-col-4">
                <x-wire-input label="Apellido paterno" id="form_funcionario_apellido_paterno" name="form_funcionario_apellido_paterno" type="text"
                    placeholder="Apellido paterno"
                    value="{{ old('form_funcionario_apellido_paterno', $usuario->funcionario->apellido_paterno ?? '') }}" required />
            </div>

            <div class="seg-col-4">
                <x-wire-input label="Apellido materno" id="form_funcionario_apellido_materno" name="form_funcionario_apellido_materno" type="text"
                    placeholder="Apellido materno"
                    value="{{ old('form_funcionario_apellido_materno', $usuario->funcionario->apellido_materno ?? '') }}" />
            </div>

            <div class="seg-col-3">
                <x-wire-input label="Carnet" id="form_funcionario_carnet" name="form_funcionario_carnet" type="text"
                    placeholder="CI o codigo interno"
                    value="{{ old('form_funcionario_carnet', $usuario->funcionario->carnet ?? '') }}" required />
            </div>

            <div class="seg-col-3">
                <x-wire-input label="Telefono" id="form_funcionario_telefono" name="form_funcionario_telefono" type="text"
                    placeholder="Telefono de contacto"
                    value="{{ old('form_funcionario_telefono', $usuario->funcionario->telefono ?? '') }}" />
            </div>

            <div class="seg-col-3">
                <x-wire-native-select label="Genero" id="form_funcionario_genero" name="form_funcionario_genero" required>
                    <option value="">Seleccione</option>
                    <option value="1" @selected((string) old('form_funcionario_genero', $usuario->funcionario->genero ?? '') === '1')>Masculino</option>
                    <option value="0" @selected((string) old('form_funcionario_genero', $usuario->funcionario->genero ?? '') === '0')>Femenino</option>
                </x-wire-native-select>
            </div>

            <div class="seg-col-3">
                <x-wire-native-select label="Estado funcionario" id="form_funcionario_estado" name="form_funcionario_estado" required>
                    <option value="1" @selected((string) old('form_funcionario_estado', $usuario->funcionario->estado ?? '1') === '1')>Activo</option>
                    <option value="0" @selected((string) old('form_funcionario_estado', $usuario->funcionario->estado ?? '') === '0')>Inactivo</option>
                </x-wire-native-select>
            </div>
        </div>
    </div>
</section>

<section class="seg-card is-blue">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-id-badge"></i>
            </span>
            <div>
                <h2 class="seg-card-title">Cargos del funcionario</h2>
                <p class="seg-card-subtitle">Opcional. Puede guardar el usuario sin cargos y asignarlos despues.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
                <label for="seg_select_cargo_funcionario" class="seg-field-label">Seleccionar cargo</label>
                <select id="seg_select_cargo_funcionario" class="seg-native-select">
                    <option value="">Seleccione un cargo</option>
                </select>
            </div>

            <x-wire-button type="button" emerald onclick="mostrarCargoNuevoFuncionario()">
                + Nuevo cargo
            </x-wire-button>
        </div>

        <div id="seg_cargos_funcionario_hidden"></div>
        <div id="seg_lista_cargos_funcionario" class="seg-chip-list"></div>

        <p id="seg_cargos_funcionario_vacio" class="seg-empty-state">
            Sin cargos asignados. Este dato no bloquea el registro.
        </p>
    </div>
</section>

<div id="seg_cargo_funcionario_nuevo_modal" class="seg-modal hidden">
    <div class="seg-modal-box">
        <div class="seg-modal-head">
            <div>
                <h2 class="seg-modal-title">Nuevo cargo</h2>
                <p class="mt-1 text-xs font-semibold text-slate-500">Se agregara al funcionario al guardar.</p>
            </div>
            <button type="button" class="seg-modal-close" onclick="cerrarModalCargoNuevoFuncionario()">x</button>
        </div>

        <div class="space-y-4 p-4">
            <div>
                <label for="seg_cargo_funcionario_nuevo_nombre" class="seg-field-label">Nombre del cargo</label>
                <input id="seg_cargo_funcionario_nuevo_nombre" type="text" class="seg-native-input"
                    placeholder="Ej: Responsable tecnico">

                <p id="seg_cargo_funcionario_nuevo_error" class="mt-2 hidden text-xs font-bold text-rose-700"></p>
            </div>

            <div class="seg-actions !px-0 !pb-0">
                <x-wire-button type="button" onclick="cerrarModalCargoNuevoFuncionario()" secondary>
                    Cancelar
                </x-wire-button>

                <x-wire-button type="button" blue onclick="agregarCargoNuevoFuncionario()">
                    Crear y agregar
                </x-wire-button>
            </div>
        </div>
    </div>
</div>

<section class="seg-card is-emerald">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-user-shield"></i>
            </span>
            <div>
                <h2 class="seg-card-title">Roles asignados</h2>
                <p class="seg-card-subtitle">Seleccione un rol y revise los permisos que incluye.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="seg-select-row">
            <div>
                <label for="seg_select_rol" class="seg-field-label">Seleccionar rol</label>
                <select id="seg_select_rol" class="seg-native-select">
                    <option value="">Seleccione un rol</option>
                </select>
            </div>
        </div>

        <div id="seg_roles_hidden"></div>

        <div class="seg-selected-table-wrap">
            <table class="seg-selected-table">
                <thead>
                    <tr>
                        <th>Rol</th>
                        <th>Permisos que incluye</th>
                        <th>Accion</th>
                    </tr>
                </thead>
                <tbody id="seg_tabla_roles"></tbody>
            </table>
        </div>

        <p id="seg_roles_vacio" class="seg-empty-state">
            Todavia no se agregaron roles.
        </p>
        <p id="seg_roles_error" class="mt-2 hidden text-sm font-semibold text-rose-700">
            Seleccione al menos un rol.
        </p>
        @error('form_roles')
            <p class="mt-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
        @enderror
    </div>
</section>

<section class="seg-card is-violet">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-key"></i>
            </span>
            <div>
                <h2 class="seg-card-title">Permisos directos</h2>
                <p class="seg-card-subtitle">Agregue solo permisos especiales que no esten cubiertos por roles.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="seg-select-row">
            <div>
                <label for="seg_select_permiso" class="seg-field-label">Seleccionar permiso directo</label>
                <select id="seg_select_permiso" class="seg-native-select">
                    <option value="">Seleccione un permiso</option>
                </select>
            </div>
        </div>

        <div id="seg_permisos_hidden"></div>
        <div id="seg_lista_permisos_directos" class="seg-chip-list"></div>

        <p id="seg_permisos_vacio" class="seg-empty-state">
            Todavia no se agregaron permisos directos.
        </p>
    </div>
</section>

<div class="seg-actions">
    <x-wire-button href="{{ route('usuarios_index') }}" secondary>
        Cancelar
    </x-wire-button>

    <x-wire-button type="submit" blue id="seg_usuario_guardar">
        {{ $esEdicion ? 'Actualizar usuario' : 'Guardar usuario' }}
    </x-wire-button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Catalogos enviados desde Laravel para construir selects y tablas sin recargar la pagina.
        window.rolesSeguridadCatalogo = @json($rolesParaSelector);
        window.permisosSeguridadCatalogo = @json($permisosParaSelector);
        window.cargosFuncionarioCatalogo = @json($cargosParaSelector);
        window.rolesSeguridadSeleccionados = @json($rolesSeleccionados);
        window.permisosDirectosSeguridadSeleccionados = @json($permisosSeleccionados);
        window.cargosFuncionarioSeleccionados = @json($cargosSeleccionados);
        window.cargosFuncionarioNuevos = @json($cargosNuevosSeleccionados);

        inicializarCargosFuncionario();
        inicializarAsignacionesSeguridad();

        document.getElementById('seg_select_cargo_funcionario')?.addEventListener('change', agregarCargoFuncionario);
        document.getElementById('seg_cargo_funcionario_nuevo_nombre')?.addEventListener('keydown', eventoEnterCargoNuevoFuncionario);
        document.getElementById('seg_select_rol')?.addEventListener('change', agregarRolSeguridad);
        document.getElementById('seg_select_permiso')?.addEventListener('change', agregarPermisoDirectoSeguridad);
        document.querySelector('form.seg-page')?.addEventListener('submit', validarEnvioFormularioUsuario);
    });

    // Revisa los campos basicos antes de enviar. El backend vuelve a validar todo al guardar.
    function validarEnvioFormularioUsuario(evento) {
        limpiarErrorRolesSeguridad();

        const camposObligatorios = [
            { id: 'form_name', mensaje: 'Ingrese el nombre de usuario.' },
            { id: 'form_email', mensaje: 'Ingrese el correo de acceso.' },
            { id: 'form_password', mensaje: 'Ingrese la contrasena.' },
            { id: 'form_password_confirmation', mensaje: 'Confirme la contrasena.' },
            { id: 'form_funcionario_nombres', mensaje: 'Ingrese los nombres del funcionario.' },
            { id: 'form_funcionario_apellido_paterno', mensaje: 'Ingrese el apellido paterno.' },
            { id: 'form_funcionario_carnet', mensaje: 'Ingrese el carnet.' },
            { id: 'form_funcionario_genero', mensaje: 'Seleccione el genero.' },
        ];

        for (const campo of camposObligatorios) {
            const input = document.getElementById(campo.id);

            if (!input || String(input.value || '').trim() !== '') {
                continue;
            }

            evento.preventDefault();
            mostrarMensajeCampoUsuario(input, campo.mensaje);
            return;
        }

        if (!validarPasswordUsuario(evento)) {
            return;
        }

        if (!(window.rolesSeguridadSeleccionados || []).length) {
            evento.preventDefault();
            mostrarErrorRolesSeguridad('Seleccione al menos un rol.');
            document.getElementById('seg_select_rol')?.focus();
            return;
        }

        bloquearBotonGuardarUsuario();
    }

    // Muestra el mensaje nativo del navegador sin deformar el diseno del campo.
    function mostrarMensajeCampoUsuario(input, mensaje) {
        input.setCustomValidity(mensaje);
        input.reportValidity();
        input.focus();

        setTimeout(() => input.setCustomValidity(''), 1200);
    }

    // La contrasena se confirma en pantalla para evitar errores simples antes de guardar.
    function validarPasswordUsuario(evento) {
        const password = document.getElementById('form_password');
        const confirmacion = document.getElementById('form_password_confirmation');

        if (!password || !confirmacion) {
            return true;
        }

        if (password.value.length < 8) {
            evento.preventDefault();
            mostrarMensajeCampoUsuario(password, 'La contrasena debe tener al menos 8 caracteres.');
            return false;
        }

        if (password.value !== confirmacion.value) {
            evento.preventDefault();
            mostrarMensajeCampoUsuario(confirmacion, 'La confirmacion no coincide con la contrasena.');
            return false;
        }

        return true;
    }

    // Evita dobles clics cuando el formulario ya esta enviandose.
    function bloquearBotonGuardarUsuario() {
        const boton = document.getElementById('seg_usuario_guardar');

        if (!boton) {
            return;
        }

        boton.disabled = true;
        boton.textContent = 'Guardando...';
    }

    // Prepara cargos existentes y nuevos antes de dibujar la interfaz.
    function inicializarCargosFuncionario() {
        window.cargosFuncionarioSeleccionados = normalizarIds(window.cargosFuncionarioSeleccionados || []);
        window.cargosFuncionarioNuevos = normalizarTextosCargoFuncionario(window.cargosFuncionarioNuevos || []);

        renderCargosFuncionario();
        refrescarSelectCargosFuncionario();
    }

    // Limpia nombres de cargos nuevos y evita duplicados en memoria.
    function normalizarTextosCargoFuncionario(valores) {
        return valores
            .map(valor => limpiarTextoCargoFuncionario(valor))
            .filter((valor, indice, lista) => valor && lista.indexOf(valor) === indice);
    }

    // Normaliza espacios para comparar nombres de cargos correctamente.
    function limpiarTextoCargoFuncionario(valor) {
        return String(valor ?? '').replace(/\s+/g, ' ').trim();
    }

    // Busca un cargo por id dentro del catalogo enviado por Laravel.
    function buscarCargoFuncionario(idCargo) {
        return (window.cargosFuncionarioCatalogo || []).find(cargo => Number(cargo.id) === Number(idCargo));
    }

    // Busca por nombre para reutilizar cargos existentes antes de crear nuevos.
    function buscarCargoFuncionarioPorNombre(nombreCargo) {
        const nombreNormalizado = limpiarTextoCargoFuncionario(nombreCargo).toLocaleLowerCase('es');

        return (window.cargosFuncionarioCatalogo || []).find(cargo => {
            return limpiarTextoCargoFuncionario(cargo.nombre).toLocaleLowerCase('es') === nombreNormalizado;
        });
    }

    // Agrega un cargo existente elegido desde el select.
    function agregarCargoFuncionario() {
        const select = document.getElementById('seg_select_cargo_funcionario');
        const idCargo = Number(select?.value || 0);

        if (!idCargo || window.cargosFuncionarioSeleccionados.includes(idCargo)) {
            return;
        }

        window.cargosFuncionarioSeleccionados.push(idCargo);
        renderCargosFuncionario();
        refrescarSelectCargosFuncionario();
        select.value = '';
    }

    // Abre el modal para crear un cargo nuevo desde el formulario de usuario.
    function mostrarCargoNuevoFuncionario() {
        const modal = document.getElementById('seg_cargo_funcionario_nuevo_modal');
        const input = document.getElementById('seg_cargo_funcionario_nuevo_nombre');

        limpiarErrorCargoNuevoFuncionario();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        input?.focus();
    }

    // Cierra el modal y limpia el nombre temporal.
    function cerrarModalCargoNuevoFuncionario() {
        const modal = document.getElementById('seg_cargo_funcionario_nuevo_modal');
        const input = document.getElementById('seg_cargo_funcionario_nuevo_nombre');

        if (input) {
            input.value = '';
        }

        limpiarErrorCargoNuevoFuncionario();
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    }

    // Permite crear el cargo presionando Enter dentro del modal.
    function eventoEnterCargoNuevoFuncionario(evento) {
        if (evento.key !== 'Enter') {
            return;
        }

        evento.preventDefault();
        agregarCargoNuevoFuncionario();
    }

    // Agrega un cargo nuevo temporal o reutiliza uno existente si coincide por nombre.
    function agregarCargoNuevoFuncionario() {
        const input = document.getElementById('seg_cargo_funcionario_nuevo_nombre');
        const nombreCargo = limpiarTextoCargoFuncionario(input?.value);
        const cargoExistente = buscarCargoFuncionarioPorNombre(nombreCargo);

        limpiarErrorCargoNuevoFuncionario();

        if (!nombreCargo) {
            mostrarErrorCargoNuevoFuncionario('Ingrese el nombre del cargo.');
            return;
        }

        if (cargoExistente) {
            if (!window.cargosFuncionarioSeleccionados.includes(Number(cargoExistente.id))) {
                window.cargosFuncionarioSeleccionados.push(Number(cargoExistente.id));
            }

            input.value = '';
            renderCargosFuncionario();
            refrescarSelectCargosFuncionario();
            cerrarModalCargoNuevoFuncionario();
            return;
        }

        const yaExisteNuevo = (window.cargosFuncionarioNuevos || []).some(cargo => {
            return limpiarTextoCargoFuncionario(cargo).toLocaleLowerCase('es') === nombreCargo.toLocaleLowerCase('es');
        });

        if (yaExisteNuevo) {
            mostrarErrorCargoNuevoFuncionario('Ese cargo ya fue agregado.');
            return;
        }

        window.cargosFuncionarioNuevos.push(nombreCargo);
        input.value = '';
        renderCargosFuncionario();
        cerrarModalCargoNuevoFuncionario();
    }

    // Muestra errores de validacion local dentro del modal de cargo nuevo.
    function mostrarErrorCargoNuevoFuncionario(mensaje) {
        const error = document.getElementById('seg_cargo_funcionario_nuevo_error');

        if (!error) return;

        error.textContent = mensaje;
        error.classList.remove('hidden');
    }

    // Limpia mensajes de error del modal de cargo nuevo.
    function limpiarErrorCargoNuevoFuncionario() {
        const error = document.getElementById('seg_cargo_funcionario_nuevo_error');

        if (!error) return;

        error.textContent = '';
        error.classList.add('hidden');
    }

    // Quita un cargo existente de la seleccion.
    function quitarCargoFuncionario(idCargo) {
        window.cargosFuncionarioSeleccionados = (window.cargosFuncionarioSeleccionados || [])
            .filter(id => Number(id) !== Number(idCargo));

        renderCargosFuncionario();
        refrescarSelectCargosFuncionario();
    }

    // Quita un cargo nuevo antes de guardar el usuario.
    function quitarCargoNuevoFuncionario(nombreCargo) {
        const nombreNormalizado = limpiarTextoCargoFuncionario(nombreCargo).toLocaleLowerCase('es');

        window.cargosFuncionarioNuevos = (window.cargosFuncionarioNuevos || [])
            .filter(cargo => limpiarTextoCargoFuncionario(cargo).toLocaleLowerCase('es') !== nombreNormalizado);

        renderCargosFuncionario();
    }

    // Reconstruye el select de cargos ocultando los que ya fueron agregados.
    function refrescarSelectCargosFuncionario() {
        const select = document.getElementById('seg_select_cargo_funcionario');
        if (!select) return;

        const seleccionados = new Set(window.cargosFuncionarioSeleccionados || []);

        select.innerHTML = '<option value="">Seleccione un cargo</option>';

        (window.cargosFuncionarioCatalogo || [])
            .filter(cargo => !seleccionados.has(Number(cargo.id)))
            .forEach(cargo => {
                select.add(new Option(`${cargo.nombre}${cargo.area ? ' - ' + cargo.area : ''}`, cargo.id));
            });
    }

    // Dibuja los cargos seleccionados y genera inputs hidden para el controlador.
    function renderCargosFuncionario() {
        const lista = document.getElementById('seg_lista_cargos_funcionario');
        const hidden = document.getElementById('seg_cargos_funcionario_hidden');
        const vacio = document.getElementById('seg_cargos_funcionario_vacio');

        if (!lista || !hidden || !vacio) return;

        lista.innerHTML = '';
        hidden.innerHTML = '';

        (window.cargosFuncionarioSeleccionados || []).forEach(idCargo => {
            const cargo = buscarCargoFuncionario(idCargo);
            if (!cargo) return;

            const chip = document.createElement('span');
            chip.className = `seg-chip ${claseColorPermiso(cargo.id)}`;
            chip.innerHTML = `
                ${escapeHtml(cargo.nombre)}${cargo.area ? ' - ' + escapeHtml(cargo.area) : ''}
                <button type="button" class="seg-chip-remove" onclick="quitarCargoFuncionario(${cargo.id})">x</button>
            `;

            lista.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'form_cargos[]';
            input.value = cargo.id;
            hidden.appendChild(input);
        });

        (window.cargosFuncionarioNuevos || []).forEach(nombreCargo => {
            const chip = document.createElement('span');
            chip.className = `seg-chip ${claseColorTextoCargoFuncionario(nombreCargo)}`;
            chip.innerHTML = `
                ${escapeHtml(nombreCargo)}
                <span class="seg-chip-tag">nuevo</span>
                <button type="button" class="seg-chip-remove" onclick="quitarCargoNuevoFuncionario('${escapeJsCargoFuncionario(nombreCargo)}')">x</button>
            `;

            lista.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'form_cargos_nuevos[]';
            input.value = nombreCargo;
            hidden.appendChild(input);
        });

        const totalCargos = (window.cargosFuncionarioSeleccionados || []).length + (window.cargosFuncionarioNuevos || []).length;
        vacio.classList.toggle('hidden', totalCargos > 0);
    }

    // Asigna un color estable para cargos nuevos segun el texto.
    function claseColorTextoCargoFuncionario(texto) {
        const colores = [
            'is-blue',
            'is-emerald',
            'is-violet',
            'is-amber',
            'is-rose',
            'is-cyan',
            'is-slate',
        ];

        const indice = limpiarTextoCargoFuncionario(texto)
            .split('')
            .reduce((total, letra) => total + letra.charCodeAt(0), 0);

        return colores[indice % colores.length];
    }

    // Escapa texto usado dentro de handlers onclick generados dinamicamente.
    function escapeJsCargoFuncionario(valor) {
        return String(valor ?? '')
            .replaceAll('\\', '\\\\')
            .replaceAll("'", "\\'")
            .replaceAll('\n', ' ');
    }

    // Prepara los datos iniciales y reconstruye la vista segun lo que ya estaba seleccionado.
    function inicializarAsignacionesSeguridad() {
        window.rolesSeguridadSeleccionados = normalizarIds(window.rolesSeguridadSeleccionados || []);
        window.permisosDirectosSeguridadSeleccionados = normalizarIds(window.permisosDirectosSeguridadSeleccionados || []);

        limpiarPermisosDirectosCubiertosPorRoles();
        renderRolesSeguridad();
        renderPermisosDirectosSeguridad();
        refrescarSelectRolesSeguridad();
        refrescarSelectPermisosSeguridad();
    }

    // Convierte los valores recibidos del formulario en ids numericos validos.
    function normalizarIds(valores) {
        return valores
            .map(valor => Number(valor))
            .filter(valor => Number.isInteger(valor) && valor > 0);
    }

    // Busca un rol dentro del catalogo cargado desde el controlador.
    function buscarRolSeguridad(idRol) {
        return (window.rolesSeguridadCatalogo || []).find(rol => Number(rol.id) === Number(idRol));
    }

    // Busca un permiso dentro del catalogo cargado desde el controlador.
    function buscarPermisoSeguridad(idPermiso) {
        return (window.permisosSeguridadCatalogo || []).find(permiso => Number(permiso.id) === Number(idPermiso));
    }

    // Calcula los permisos que ya vienen incluidos por los roles seleccionados.
    function permisosCubiertosPorRolesSeguridad() {
        const permisos = new Set();

        (window.rolesSeguridadSeleccionados || []).forEach(idRol => {
            const rol = buscarRolSeguridad(idRol);

            (rol?.permisos || []).forEach(permiso => {
                permisos.add(Number(permiso.id));
            });
        });

        return permisos;
    }

    // Quita permisos directos duplicados cuando ya estan cubiertos por algun rol.
    function limpiarPermisosDirectosCubiertosPorRoles() {
        const cubiertos = permisosCubiertosPorRolesSeguridad();

        window.permisosDirectosSeguridadSeleccionados = (window.permisosDirectosSeguridadSeleccionados || [])
            .filter(idPermiso => !cubiertos.has(Number(idPermiso)));
    }

    // Agrega el rol elegido en el select y actualiza la tabla de roles.
    function agregarRolSeguridad() {
        const select = document.getElementById('seg_select_rol');
        const idRol = Number(select?.value || 0);

        if (!idRol || window.rolesSeguridadSeleccionados.includes(idRol)) {
            return;
        }

        window.rolesSeguridadSeleccionados.push(idRol);
        limpiarErrorRolesSeguridad();
        limpiarPermisosDirectosCubiertosPorRoles();
        renderRolesSeguridad();
        renderPermisosDirectosSeguridad();
        refrescarSelectRolesSeguridad();
        refrescarSelectPermisosSeguridad();
        select.value = '';
    }

    // Quita un rol seleccionado y vuelve a habilitar permisos directos disponibles.
    function quitarRolSeguridad(idRol) {
        window.rolesSeguridadSeleccionados = (window.rolesSeguridadSeleccionados || [])
            .filter(id => Number(id) !== Number(idRol));

        renderRolesSeguridad();
        renderPermisosDirectosSeguridad();
        refrescarSelectRolesSeguridad();
        refrescarSelectPermisosSeguridad();
    }

    // Mensaje propio del selector de roles porque la seleccion se guarda en inputs generados por JavaScript.
    function mostrarErrorRolesSeguridad(mensaje) {
        const error = document.getElementById('seg_roles_error');

        if (!error) {
            return;
        }

        error.textContent = mensaje;
        error.classList.remove('hidden');
    }

    function limpiarErrorRolesSeguridad() {
        const error = document.getElementById('seg_roles_error');

        if (!error) {
            return;
        }

        error.textContent = '';
        error.classList.add('hidden');
    }

    // Agrega un permiso directo solo si no esta incluido por un rol.
    function agregarPermisoDirectoSeguridad() {
        const select = document.getElementById('seg_select_permiso');
        const idPermiso = Number(select?.value || 0);
        const cubiertos = permisosCubiertosPorRolesSeguridad();

        if (!idPermiso || cubiertos.has(idPermiso) || window.permisosDirectosSeguridadSeleccionados.includes(idPermiso)) {
            return;
        }

        window.permisosDirectosSeguridadSeleccionados.push(idPermiso);
        renderPermisosDirectosSeguridad();
        refrescarSelectPermisosSeguridad();
        select.value = '';
    }

    // Quita un permiso directo seleccionado por el usuario.
    function quitarPermisoDirectoSeguridad(idPermiso) {
        window.permisosDirectosSeguridadSeleccionados = (window.permisosDirectosSeguridadSeleccionados || [])
            .filter(id => Number(id) !== Number(idPermiso));

        renderPermisosDirectosSeguridad();
        refrescarSelectPermisosSeguridad();
    }

    // Reconstruye el select de roles ocultando los que ya estan agregados.
    function refrescarSelectRolesSeguridad() {
        const select = document.getElementById('seg_select_rol');
        if (!select) return;

        const seleccionados = new Set(window.rolesSeguridadSeleccionados || []);

        select.innerHTML = '<option value="">Seleccione un rol</option>';

        (window.rolesSeguridadCatalogo || [])
            .filter(rol => !seleccionados.has(Number(rol.id)))
            .forEach(rol => {
                select.add(new Option(`${rol.name} (${rol.slug})`, rol.id));
            });
    }

    // Reconstruye el select de permisos directos excluyendo duplicados.
    function refrescarSelectPermisosSeguridad() {
        const select = document.getElementById('seg_select_permiso');
        if (!select) return;

        const cubiertos = permisosCubiertosPorRolesSeguridad();
        const directos = new Set(window.permisosDirectosSeguridadSeleccionados || []);

        select.innerHTML = '<option value="">Seleccione un permiso</option>';

        (window.permisosSeguridadCatalogo || [])
            .filter(permiso => !cubiertos.has(Number(permiso.id)))
            .filter(permiso => !directos.has(Number(permiso.id)))
            .forEach(permiso => {
                select.add(new Option(permiso.nombre, permiso.id));
            });
    }

    // Dibuja la tabla de roles y genera los inputs hidden form_roles[].
    function renderRolesSeguridad() {
        const tbody = document.getElementById('seg_tabla_roles');
        const hidden = document.getElementById('seg_roles_hidden');
        const vacio = document.getElementById('seg_roles_vacio');

        if (!tbody || !hidden || !vacio) return;

        tbody.innerHTML = '';
        hidden.innerHTML = '';

        (window.rolesSeguridadSeleccionados || []).forEach(idRol => {
            const rol = buscarRolSeguridad(idRol);
            if (!rol) return;

            const fila = document.createElement('tr');
            const permisos = (rol.permisos || [])
                .map(permiso => `<span class="seg-chip ${claseColorPermiso(permiso.id)}">${escapeHtml(permiso.nombre)}</span>`)
                .join('');

            fila.innerHTML = `
                <td>
                    <strong>${escapeHtml(rol.name)}</strong>
                    <span class="seg-check-meta">${escapeHtml(rol.slug || '')}</span>
                </td>
                <td>
                    <div class="seg-chip-list is-table">
                        ${permisos || '<span class="text-xs font-semibold text-slate-500">Sin permisos asignados</span>'}
                    </div>
                </td>
                <td>
                    <button type="button" class="seg-chip is-emerald" onclick="quitarRolSeguridad(${rol.id})">
                        Quitar
                    </button>
                </td>
            `;

            tbody.appendChild(fila);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'form_roles[]';
            input.value = rol.id;
            hidden.appendChild(input);
        });

        vacio.classList.toggle('hidden', (window.rolesSeguridadSeleccionados || []).length > 0);
    }

    // Dibuja los permisos directos y genera los inputs hidden form_permisos[].
    function renderPermisosDirectosSeguridad() {
        const lista = document.getElementById('seg_lista_permisos_directos');
        const hidden = document.getElementById('seg_permisos_hidden');
        const vacio = document.getElementById('seg_permisos_vacio');

        if (!lista || !hidden || !vacio) return;

        lista.innerHTML = '';
        hidden.innerHTML = '';

        (window.permisosDirectosSeguridadSeleccionados || []).forEach(idPermiso => {
            const permiso = buscarPermisoSeguridad(idPermiso);
            if (!permiso) return;

            const chip = document.createElement('span');
            chip.className = `seg-chip ${claseColorPermiso(permiso.id)}`;
            chip.innerHTML = `
                ${escapeHtml(permiso.nombre)}
                <button type="button" class="seg-chip-remove" onclick="quitarPermisoDirectoSeguridad(${permiso.id})">x</button>
            `;

            lista.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'form_permisos[]';
            input.value = permiso.id;
            hidden.appendChild(input);
        });

        vacio.classList.toggle('hidden', (window.permisosDirectosSeguridadSeleccionados || []).length > 0);
    }

    // Escapa textos antes de insertarlos en HTML generado con JavaScript.
    function escapeHtml(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // Asigna un color estable a cada permiso usando su id.
    function claseColorPermiso(idPermiso) {
        const colores = [
            'is-blue',
            'is-emerald',
            'is-violet',
            'is-amber',
            'is-rose',
            'is-cyan',
            'is-slate',
        ];

        return colores[Math.abs(Number(idPermiso) || 0) % colores.length];
    }
</script>
