<x-admin-layout title="Registrar tramitador | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Tramitadores', 'href' => route('tramitadores_index')],
    ['name' => 'Registrar', 'href' => '#'],
]">
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-semibold text-slate-900">Asignar un tramitador</h1>
            <p class="mt-2 text-sm text-slate-600">
                Empresa: <span class="font-semibold text-slate-800">{{ $empresa->razon_social }}</span>.
                Primero se verificará si la persona ya existe. Esta solicitud no crea ni muestra credenciales.
            </p>
        </div>

        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <p class="font-semibold">Revise los datos marcados antes de continuar.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('tramitadores_store') }}" method="POST" enctype="multipart/form-data"
            id="formTramitador" class="space-y-6">
            @csrf

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="font-semibold text-slate-900">1. Identificar a la persona</h2>
                    <p class="mt-1 text-sm text-slate-500">La búsqueda evita crear otra ficha y otras credenciales para la misma persona.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-[1fr_180px_auto] md:items-end">
                    <div>
                        <label for="form_ci" class="mb-1 block text-sm font-medium text-slate-700">Cédula de identidad</label>
                        <input id="form_ci" name="form_ci" value="{{ old('form_ci') }}" maxlength="50" required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="form_complemento" class="mb-1 block text-sm font-medium text-slate-700">Complemento</label>
                        <input id="form_complemento" name="form_complemento" value="{{ old('form_complemento') }}" maxlength="10"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <button type="button" id="btnBuscarPersona"
                        class="rounded-lg bg-slate-800 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-700">
                        Buscar
                    </button>
                </div>

                <div id="resultadoBusqueda" class="mt-4 hidden rounded-lg border p-4 text-sm" aria-live="polite"></div>
            </section>

            <section id="datosPersonaNueva" class="hidden rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="font-semibold text-slate-900">2. Datos mínimos de la persona nueva</h2>
                    <p class="mt-1 text-sm text-slate-500">Solo se solicitan datos necesarios para crear su ficha. La cuenta se habilitará posteriormente en INSO.</p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="form_nombres" class="mb-1 block text-sm font-medium text-slate-700">Nombres</label>
                        <input id="form_nombres" name="form_nombres" value="{{ old('form_nombres') }}" maxlength="100"
                            data-requerido-persona class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="form_apellido_paterno" class="mb-1 block text-sm font-medium text-slate-700">Apellido paterno</label>
                        <input id="form_apellido_paterno" name="form_apellido_paterno" value="{{ old('form_apellido_paterno') }}" maxlength="100"
                            data-requerido-persona class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="form_apellido_materno" class="mb-1 block text-sm font-medium text-slate-700">Apellido materno</label>
                        <input id="form_apellido_materno" name="form_apellido_materno" value="{{ old('form_apellido_materno') }}" maxlength="100"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="form_expedido" class="mb-1 block text-sm font-medium text-slate-700">Expedido</label>
                        <select id="form_expedido" name="form_expedido"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccione</option>
                            @foreach (\App\Models\Natural::EXPEDIDOS as $codigo => $nombre)
                                <option value="{{ $codigo }}" @selected(old('form_expedido') === $codigo)>{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="form_correo" class="mb-1 block text-sm font-medium text-slate-700">Correo</label>
                        <input id="form_correo" name="form_correo" type="email" value="{{ old('form_correo') }}" maxlength="50"
                            data-requerido-persona class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                    <div>
                        <label for="form_id_territorio" class="mb-1 block text-sm font-medium text-slate-700">Territorio</label>
                        <select id="form_id_territorio" name="form_id_territorio" data-requerido-persona
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccione</option>
                            @foreach ($territorios as $territorio)
                                <option value="{{ $territorio->id }}" @selected((string) old('form_id_territorio') === (string) $territorio->id)>
                                    {{ $territorio->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="form_genero" class="mb-1 block text-sm font-medium text-slate-700">Género</label>
                        <select id="form_genero" name="form_genero" data-requerido-persona
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="">Seleccione</option>
                            <option value="1" @selected(old('form_genero') === '1')>Masculino</option>
                            <option value="0" @selected(old('form_genero') === '0')>Femenino</option>
                        </select>
                    </div>
                    <div>
                        <label for="form_domicilio" class="mb-1 block text-sm font-medium text-slate-700">Domicilio (opcional)</label>
                        <input id="form_domicilio" name="form_domicilio" value="{{ old('form_domicilio') }}" maxlength="255"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5">
                    <h2 class="font-semibold text-slate-900">3. Autorización de la empresa</h2>
                    <p class="mt-1 text-sm text-slate-500">La relación permanecerá pendiente hasta que INSO revise esta información.</p>
                </div>

                <div>
                    <div>
                        <label for="form_fecha_registro" class="mb-1 block text-sm font-medium text-slate-700">Fecha de registro</label>
                        <input id="form_fecha_registro" name="form_fecha_registro" type="date"
                            value="{{ old('form_fecha_registro', now()->toDateString()) }}" required
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500 md:max-w-md">
                    </div>
                    <div class="mt-4">
                        <label for="form_carta_autorizacion" class="mb-1 block text-sm font-medium text-slate-700">Carta de autorización (PDF)</label>
                        <input id="form_carta_autorizacion" name="form_carta_autorizacion" type="file" accept="application/pdf" required
                            class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700 file:mr-4 file:border-0 file:bg-slate-100 file:px-4 file:py-2.5 file:font-semibold">
                        <p class="mt-1 text-xs text-slate-500">Máximo 5 MB. El archivo se guarda de forma privada.</p>
                    </div>
                </div>
            </section>

            <div class="flex justify-end gap-3">
                <a href="{{ route('tramitadores_index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</a>
                <button type="submit" id="btnGuardar" disabled
                    class="rounded-lg bg-teal-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-teal-600 disabled:cursor-not-allowed disabled:opacity-50">
                    Registrar solicitud
                </button>
            </div>
        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const botonBuscar = document.getElementById('btnBuscarPersona');
                const botonGuardar = document.getElementById('btnGuardar');
                const resultado = document.getElementById('resultadoBusqueda');
                const datosPersonaNueva = document.getElementById('datosPersonaNueva');
                const camposPersonaNueva = datosPersonaNueva.querySelectorAll('[data-requerido-persona]');
                let documentoVerificado = false;

                const mostrarPersonaNueva = (mostrar) => {
                    datosPersonaNueva.classList.toggle('hidden', !mostrar);
                    camposPersonaNueva.forEach((campo) => campo.required = mostrar);
                };

                const mostrarResultado = (mensaje, tipo = 'ok') => {
                    resultado.className = `mt-4 rounded-lg border p-4 text-sm ${tipo === 'error'
                        ? 'border-red-200 bg-red-50 text-red-800'
                        : 'border-teal-200 bg-teal-50 text-teal-900'}`;
                    resultado.textContent = mensaje;
                };

                const invalidarBusqueda = () => {
                    documentoVerificado = false;
                    botonGuardar.disabled = true;
                    resultado.classList.add('hidden');
                    mostrarPersonaNueva(false);
                };

                document.getElementById('form_ci').addEventListener('input', invalidarBusqueda);
                document.getElementById('form_complemento').addEventListener('input', invalidarBusqueda);

                botonBuscar.addEventListener('click', async () => {
                    const ci = document.getElementById('form_ci').value.trim();
                    const complemento = document.getElementById('form_complemento').value.trim();

                    if (!ci) {
                        mostrarResultado('Ingrese la cédula de identidad.', 'error');
                        return;
                    }

                    botonBuscar.disabled = true;
                    botonBuscar.textContent = 'Buscando...';

                    try {
                        const respuesta = await fetch(@json(route('tramitadores_buscar_persona')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @json(csrf_token()),
                            },
                            body: JSON.stringify({ ci, complemento }),
                        });
                        const datos = await respuesta.json();

                        if (!respuesta.ok) {
                            const errores = datos.errors ? Object.values(datos.errors).flat().join(' ') : datos.message;
                            throw new Error(errores || 'No se pudo completar la búsqueda.');
                        }

                        documentoVerificado = true;

                        if (!datos.existe) {
                            mostrarPersonaNueva(true);
                            mostrarResultado('La persona no está registrada. Complete sus datos mínimos para crear la ficha.');
                            botonGuardar.disabled = false;
                            return;
                        }

                        mostrarPersonaNueva(false);
                        const cuenta = datos.tiene_cuenta ? 'Ya posee una cuenta; no se crearán nuevas credenciales.' : 'Aún no posee una cuenta; INSO la habilitará al validar.';
                        const relacion = datos.relacion?.existe ? ` Ya existe una relación con estado ${datos.relacion.estado}.` : '';
                        mostrarResultado(`${datos.nombre}. Correo: ${datos.correo || 'no registrado'}. ${cuenta}${relacion}`, datos.relacion?.existe ? 'error' : 'ok');
                        botonGuardar.disabled = Boolean(datos.relacion?.existe);
                    } catch (error) {
                        documentoVerificado = false;
                        botonGuardar.disabled = true;
                        mostrarPersonaNueva(false);
                        mostrarResultado(error.message, 'error');
                    } finally {
                        botonBuscar.disabled = false;
                        botonBuscar.textContent = 'Buscar';
                    }
                });

                document.getElementById('formTramitador').addEventListener('submit', (evento) => {
                    if (!documentoVerificado) {
                        evento.preventDefault();
                        mostrarResultado('Busque y verifique primero la cédula de identidad.', 'error');
                    }
                });

                @if (old('form_correo'))
                    documentoVerificado = true;
                    mostrarPersonaNueva(true);
                    botonGuardar.disabled = false;
                    mostrarResultado('Corrija los datos indicados y vuelva a registrar la solicitud.');
                @endif
            });
        </script>
    @endpush
</x-admin-layout>
