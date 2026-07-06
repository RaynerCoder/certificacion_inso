@php
    $esEdicion = $modo === 'editar';
    $permisosParaSelector = $permisos->map(fn ($permiso) => [
        'id' => $permiso->id,
        'nombre' => $permiso->nombre,
    ])->values();
    $permisosNuevosSeleccionados = collect(old('form_permisos_nuevos', []))
        ->filter(fn ($permiso) => trim((string) $permiso) !== '')
        ->values();
@endphp

@if (session('error'))
    <div class="seg-alert">{{ session('error') }}</div>
@endif

<section class="seg-card is-emerald">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-shield-halved"></i>
            </span>
            <div>
                <h2 class="seg-card-title">{{ $esEdicion ? 'Editar rol' : 'Crear rol' }}</h2>
                <p class="seg-card-subtitle">Defina nombre, codigo interno y estado del rol.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="seg-grid">
            <div class="seg-col-6">
                <x-wire-input label="Nombre del rol" id="form_name" name="form_name" type="text"
                    placeholder="Ej: Tecnico evaluador" value="{{ old('form_name', $rol->name ?? '') }}" />
            </div>

            <div class="seg-col-6">
                <x-wire-input label="Slug" id="form_slug" name="form_slug" type="text"
                    placeholder="Ej: tecnico-evaluador" value="{{ old('form_slug', $rol->slug ?? '') }}" />
            </div>

            <div class="seg-col-6">
                <x-wire-input label="Marca especial" id="form_especial" name="form_especial" type="text"
                    placeholder="Opcional: SISTEMA, ALL, etc." value="{{ old('form_especial', $rol->especial ?? '') }}" />
            </div>

            <div class="seg-col-3">
                <x-wire-native-select label="Estado" id="form_estado" name="form_estado">
                    <option value="1" @selected((string) old('form_estado', $rol->estado ?? '1') === '1')>Activo</option>
                    <option value="0" @selected((string) old('form_estado', $rol->estado ?? '') === '0')>Inactivo</option>
                </x-wire-native-select>
            </div>

            <div class="seg-col-12">
                <x-wire-textarea label="Descripcion" id="form_descripcion" name="form_descripcion"
                    placeholder="Describa para que se usara este rol" rows="3">{{ old('form_descripcion', $rol->descripcion ?? '') }}</x-wire-textarea>
            </div>
        </div>
    </div>
</section>

<section class="seg-card is-violet">
    <div class="seg-card-head">
        <div class="flex items-center gap-3">
            <span class="seg-section-icon">
                <i class="fa-solid fa-key"></i>
            </span>
            <div>
                <h2 class="seg-card-title">Permisos del rol</h2>
                <p class="seg-card-subtitle">Estos permisos se aplicaran a todos los usuarios que tengan este rol.</p>
            </div>
        </div>
    </div>

    <div class="seg-card-body">
        <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div>
                <label for="seg_select_permiso_rol" class="seg-field-label">Seleccionar permiso</label>
                <select id="seg_select_permiso_rol" class="seg-native-select">
                    <option value="">Seleccione un permiso</option>
                </select>
            </div>

            <x-wire-button type="button" emerald onclick="mostrarPermisoNuevoRol()">
                + Nuevo permiso
            </x-wire-button>
        </div>

        <div id="seg_permisos_rol_hidden"></div>
        <div id="seg_lista_permisos_rol" class="seg-chip-list"></div>

        <p id="seg_permisos_rol_vacio" class="seg-empty-state">
            Todavia no se agregaron permisos al rol.
        </p>
    </div>
</section>

<div id="seg_permiso_rol_nuevo_modal" class="seg-modal hidden">
    <div class="seg-modal-box">
        <div class="seg-modal-head">
            <div>
                <h2 class="seg-modal-title">Nuevo permiso</h2>
                <p class="mt-1 text-xs font-semibold text-slate-500">Se agregara al rol al guardar.</p>
            </div>
            <button type="button" class="seg-modal-close" onclick="cerrarModalPermisoNuevoRol()">x</button>
        </div>

        <div class="space-y-4 p-4">
            <div>
                <label for="seg_permiso_rol_nuevo_nombre" class="seg-field-label">Nombre del permiso</label>
                <input id="seg_permiso_rol_nuevo_nombre" type="text" class="seg-native-input"
                    placeholder="Ej: aprobar solicitud">

                <p id="seg_permiso_rol_nuevo_error" class="mt-2 hidden text-xs font-bold text-rose-700"></p>
            </div>

            <div class="seg-actions !px-0 !pb-0">
                <x-wire-button type="button" onclick="cerrarModalPermisoNuevoRol()" secondary>
                    Cancelar
                </x-wire-button>

                <x-wire-button type="button" blue onclick="agregarPermisoNuevoRol()">
                    Crear y agregar
                </x-wire-button>
            </div>
        </div>
    </div>
</div>

<div class="seg-actions">
    <x-wire-button href="{{ route('roles_index') }}" secondary>
        Cancelar
    </x-wire-button>

    <x-wire-button type="submit" blue>
        {{ $esEdicion ? 'Actualizar rol' : 'Guardar rol' }}
    </x-wire-button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Catalogo de permisos activos y selecciones iniciales enviadas por Laravel.
        window.permisosRolCatalogo = @json($permisosParaSelector);
        window.permisosRolSeleccionados = @json($permisosSeleccionados);
        window.permisosRolNuevos = @json($permisosNuevosSeleccionados);

        inicializarPermisosRol();
        document.getElementById('seg_select_permiso_rol')?.addEventListener('change', agregarPermisoRol);
        document.getElementById('seg_permiso_rol_nuevo_nombre')?.addEventListener('keydown', eventoEnterPermisoNuevoRol);
    });

    // Prepara permisos existentes y nuevos antes de dibujar la interfaz.
    function inicializarPermisosRol() {
        window.permisosRolSeleccionados = normalizarIdsRol(window.permisosRolSeleccionados || []);
        window.permisosRolNuevos = normalizarTextosPermisoRol(window.permisosRolNuevos || []);

        renderPermisosRol();
        refrescarSelectPermisosRol();
    }

    // Convierte valores del formulario en ids numericos validos.
    function normalizarIdsRol(valores) {
        return valores
            .map(valor => Number(valor))
            .filter(valor => Number.isInteger(valor) && valor > 0);
    }

    // Limpia nombres de permisos nuevos y evita duplicados en memoria.
    function normalizarTextosPermisoRol(valores) {
        return valores
            .map(valor => limpiarTextoPermisoRol(valor))
            .filter((valor, indice, lista) => valor && lista.indexOf(valor) === indice);
    }

    // Normaliza espacios para comparar nombres de permisos correctamente.
    function limpiarTextoPermisoRol(valor) {
        return String(valor ?? '').replace(/\s+/g, ' ').trim();
    }

    // Busca un permiso existente por id dentro del catalogo.
    function buscarPermisoRol(idPermiso) {
        return (window.permisosRolCatalogo || []).find(permiso => Number(permiso.id) === Number(idPermiso));
    }

    // Busca por nombre para no crear permisos duplicados desde el modal.
    function buscarPermisoRolPorNombre(nombrePermiso) {
        const nombreNormalizado = limpiarTextoPermisoRol(nombrePermiso).toLocaleLowerCase('es');

        return (window.permisosRolCatalogo || []).find(permiso => {
            return limpiarTextoPermisoRol(permiso.nombre).toLocaleLowerCase('es') === nombreNormalizado;
        });
    }

    // Agrega al rol un permiso existente elegido desde el select.
    function agregarPermisoRol() {
        const select = document.getElementById('seg_select_permiso_rol');
        const idPermiso = Number(select?.value || 0);

        if (!idPermiso || window.permisosRolSeleccionados.includes(idPermiso)) {
            return;
        }

        window.permisosRolSeleccionados.push(idPermiso);
        renderPermisosRol();
        refrescarSelectPermisosRol();
        select.value = '';
    }

    // Abre el modal para registrar un permiso nuevo desde el formulario de rol.
    function mostrarPermisoNuevoRol() {
        const modal = document.getElementById('seg_permiso_rol_nuevo_modal');
        const input = document.getElementById('seg_permiso_rol_nuevo_nombre');

        limpiarErrorPermisoNuevoRol();
        modal?.classList.remove('hidden');
        modal?.classList.add('flex');
        input?.focus();
    }

    // Cierra el modal y limpia el campo temporal.
    function cerrarModalPermisoNuevoRol() {
        const modal = document.getElementById('seg_permiso_rol_nuevo_modal');
        const input = document.getElementById('seg_permiso_rol_nuevo_nombre');

        if (input) {
            input.value = '';
        }
        limpiarErrorPermisoNuevoRol();
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    }

    // Permite crear el permiso presionando Enter dentro del modal.
    function eventoEnterPermisoNuevoRol(evento) {
        if (evento.key !== 'Enter') {
            return;
        }

        evento.preventDefault();
        agregarPermisoNuevoRol();
    }

    // Agrega un permiso nuevo a la lista temporal o reutiliza uno existente si ya esta registrado.
    function agregarPermisoNuevoRol() {
        const input = document.getElementById('seg_permiso_rol_nuevo_nombre');
        const nombrePermiso = limpiarTextoPermisoRol(input?.value);
        const permisoExistente = buscarPermisoRolPorNombre(nombrePermiso);

        limpiarErrorPermisoNuevoRol();

        if (!nombrePermiso) {
            mostrarErrorPermisoNuevoRol('Ingrese el nombre del permiso.');
            return;
        }

        if (permisoExistente) {
            if (!window.permisosRolSeleccionados.includes(Number(permisoExistente.id))) {
                window.permisosRolSeleccionados.push(Number(permisoExistente.id));
            }

            input.value = '';
            renderPermisosRol();
            refrescarSelectPermisosRol();
            cerrarModalPermisoNuevoRol();
            return;
        }

        const yaExisteNuevo = (window.permisosRolNuevos || []).some(permiso => {
            return limpiarTextoPermisoRol(permiso).toLocaleLowerCase('es') === nombrePermiso.toLocaleLowerCase('es');
        });

        if (yaExisteNuevo) {
            mostrarErrorPermisoNuevoRol('Ese permiso ya fue agregado.');
            return;
        }

        window.permisosRolNuevos.push(nombrePermiso);
        input.value = '';
        renderPermisosRol();
        cerrarModalPermisoNuevoRol();
    }

    // Muestra errores de validacion local dentro del modal.
    function mostrarErrorPermisoNuevoRol(mensaje) {
        const error = document.getElementById('seg_permiso_rol_nuevo_error');

        if (!error) return;

        error.textContent = mensaje;
        error.classList.remove('hidden');
    }

    // Limpia mensajes de error del modal de permiso nuevo.
    function limpiarErrorPermisoNuevoRol() {
        const error = document.getElementById('seg_permiso_rol_nuevo_error');

        if (!error) return;

        error.textContent = '';
        error.classList.add('hidden');
    }

    // Quita un permiso existente de la seleccion del rol.
    function quitarPermisoRol(idPermiso) {
        window.permisosRolSeleccionados = (window.permisosRolSeleccionados || [])
            .filter(id => Number(id) !== Number(idPermiso));

        renderPermisosRol();
        refrescarSelectPermisosRol();
    }

    // Quita un permiso nuevo antes de guardar el rol.
    function quitarPermisoNuevoRol(nombrePermiso) {
        const nombreNormalizado = limpiarTextoPermisoRol(nombrePermiso).toLocaleLowerCase('es');

        window.permisosRolNuevos = (window.permisosRolNuevos || [])
            .filter(permiso => limpiarTextoPermisoRol(permiso).toLocaleLowerCase('es') !== nombreNormalizado);

        renderPermisosRol();
    }

    // Reconstruye el select ocultando permisos que ya estan agregados.
    function refrescarSelectPermisosRol() {
        const select = document.getElementById('seg_select_permiso_rol');
        if (!select) return;

        const seleccionados = new Set(window.permisosRolSeleccionados || []);

        select.innerHTML = '<option value="">Seleccione un permiso</option>';

        (window.permisosRolCatalogo || [])
            .filter(permiso => !seleccionados.has(Number(permiso.id)))
            .forEach(permiso => {
                select.add(new Option(permiso.nombre, permiso.id));
            });
    }

    // Dibuja los chips del rol y genera inputs hidden para el controlador.
    function renderPermisosRol() {
        const lista = document.getElementById('seg_lista_permisos_rol');
        const hidden = document.getElementById('seg_permisos_rol_hidden');
        const vacio = document.getElementById('seg_permisos_rol_vacio');

        if (!lista || !hidden || !vacio) return;

        lista.innerHTML = '';
        hidden.innerHTML = '';

        (window.permisosRolSeleccionados || []).forEach(idPermiso => {
            const permiso = buscarPermisoRol(idPermiso);
            if (!permiso) return;

            const chip = document.createElement('span');
            chip.className = `seg-chip ${claseColorPermisoRol(permiso.id)}`;
            chip.innerHTML = `
                ${escapeHtmlRol(permiso.nombre)}
                <button type="button" class="seg-chip-remove" onclick="quitarPermisoRol(${permiso.id})">x</button>
            `;

            lista.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'form_permisos[]';
            input.value = permiso.id;
            hidden.appendChild(input);
        });

        (window.permisosRolNuevos || []).forEach(nombrePermiso => {
            const nombreSeguro = escapeHtmlRol(nombrePermiso);
            const chip = document.createElement('span');
            chip.className = `seg-chip ${claseColorTextoPermisoRol(nombrePermiso)}`;
            chip.innerHTML = `
                ${nombreSeguro}
                <span class="seg-chip-tag">nuevo</span>
                <button type="button" class="seg-chip-remove" onclick="quitarPermisoNuevoRol('${escapeJsRol(nombrePermiso)}')">x</button>
            `;

            lista.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'form_permisos_nuevos[]';
            input.value = nombrePermiso;
            hidden.appendChild(input);
        });

        const totalPermisos = (window.permisosRolSeleccionados || []).length + (window.permisosRolNuevos || []).length;
        vacio.classList.toggle('hidden', totalPermisos > 0);
    }

    // Asigna un color estable para permisos existentes segun su id.
    function claseColorPermisoRol(idPermiso) {
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

    // Asigna un color estable para permisos nuevos segun su texto.
    function claseColorTextoPermisoRol(texto) {
        const colores = [
            'is-blue',
            'is-emerald',
            'is-violet',
            'is-amber',
            'is-rose',
            'is-cyan',
            'is-slate',
        ];

        const indice = limpiarTextoPermisoRol(texto)
            .split('')
            .reduce((total, letra) => total + letra.charCodeAt(0), 0);

        return colores[indice % colores.length];
    }

    // Escapa textos antes de insertarlos en HTML generado con JavaScript.
    function escapeHtmlRol(valor) {
        return String(valor ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    // Escapa texto usado dentro de handlers onclick generados dinamicamente.
    function escapeJsRol(valor) {
        return String(valor ?? '')
            .replaceAll('\\', '\\\\')
            .replaceAll("'", "\\'")
            .replaceAll('\n', ' ');
    }
</script>
