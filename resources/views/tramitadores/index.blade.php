<x-admin-layout title="Tramitadores | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Personas y Empresas',
        'href' => '#',
    ],
    [
        'name' => 'Tramitadores',
        'href' => route('tramitadores_index'),
    ],
]">

    <x-slot name="action">
        @if ($empresa)
            @permiso('tramitadores.ver')
                <x-wire-button href="{{ route('tramitadores_create') }}" class="w-full justify-center sm:w-auto" blue>
                    Asignar tramitador
                </x-wire-button>
            @endpermiso
        @endif
    </x-slot>

    @if ($errors->any())
        <div role="alert" class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    {{-- Tabla principal del modulo: app/Livewire/Datatables/TramitadorTable.php --}}
    <div class="min-w-0 max-w-full overflow-x-auto">
        @livewire('datatables.tramitador-table')
    </div>

    @push('js')
        <script>
            document.addEventListener('submit', (evento) => {
                const formulario = evento.target.closest('[data-tramitador-baja]');

                if (! formulario) {
                    return;
                }

                evento.preventDefault();

                const nombre = formulario.dataset.tramitadorNombre;
                const empresa = formulario.dataset.tramitadorEmpresa;
                const pendientes = Number(formulario.dataset.tramitesPendientes || 0);
                const detalle = pendientes
                    ? `Las correcciones pendientes de ${pendientes === 1 ? 'este trámite pasarán' : 'estos ' + pendientes + ' trámites pasarán'} a la persona que seleccione para su atención.`
                    : 'No tiene trámites pendientes de corrección.';

                const contenido = document.createElement('div');
                contenido.style.cssText = 'text-align:left;font-size:14px;line-height:1.5';
                const datos = document.createElement('dl');
                datos.style.cssText = 'margin:0 0 16px';
                [['Tramitador', nombre], ['Empresa / beneficiario', empresa]].forEach(([etiqueta, valor]) => {
                    const titulo = document.createElement('dt');
                    titulo.textContent = etiqueta;
                    titulo.style.cssText = 'font-size:12px;color:#64748b;margin-top:10px';
                    const texto = document.createElement('dd');
                    texto.textContent = valor;
                    texto.style.cssText = 'margin:2px 0 0;font-weight:600;overflow-wrap:anywhere';
                    datos.append(titulo, texto);
                });
                contenido.appendChild(datos);
                const descripcion = document.createElement('p');
                descripcion.textContent = `Se quitará su autorización para tramitar en esta empresa. ${detalle}`;
                contenido.appendChild(descripcion);

                let tramites = [];
                try {
                    tramites = JSON.parse(formulario.dataset.detalleTramites || '[]') || [];
                } catch (error) {
                    tramites = [];
                }
                tramites = [...new Map(tramites.map((tramite) => [tramite.codigo, tramite])).values()]
                    .sort((a, b) => Number(a.codigo) - Number(b.codigo));
                if (tramites.length) {
                    const titulo = document.createElement('p');
                    titulo.textContent = 'Trámites pendientes de corrección de esta empresa:';
                    titulo.style.cssText = 'margin:16px 0 8px;font-weight:600;text-align:left;font-size:14px';
                    contenido.appendChild(titulo);
                    const lista = document.createElement('ul');
                    lista.style.cssText = 'max-height:180px;overflow-y:auto;text-align:left;margin:0;padding:0;list-style:none;border:1px solid #e2e8f0;border-radius:6px';
                    tramites.forEach((tramite) => {
                        const item = document.createElement('li');
                        item.style.cssText = 'padding:10px 12px;font-size:14px;border-bottom:1px solid #f1f5f9';
                        const codigo = document.createElement('strong');
                        codigo.textContent = `Código de trámite: ${tramite.codigo}`;
                        codigo.style.cssText = 'display:block;font-size:13px;color:#0f766e';
                        const nombreTipo = document.createElement('span');
                        nombreTipo.textContent = tramite.nombre || 'Sin nombre de tipo de trámite';
                        nombreTipo.style.cssText = 'display:block;margin-top:4px;overflow-wrap:anywhere;color:#334155';
                        item.append(codigo, nombreTipo);
                        lista.appendChild(item);
                    });
                    contenido.appendChild(lista);
                }

                let selectorDestinatario = null;
                if (pendientes > 0) {
                    let opciones = [];
                    try { opciones = JSON.parse(formulario.dataset.destinatariosBaja || '[]'); } catch (error) { opciones = []; }
                    const etiqueta = document.createElement('label');
                    etiqueta.htmlFor = 'destinatarioBajaTramitador';
                    etiqueta.textContent = '¿Quién continuará estos trámites?';
                    etiqueta.style.cssText = 'display:block;margin:16px 0 6px;font-weight:600';
                    selectorDestinatario = document.createElement('select');
                    selectorDestinatario.id = 'destinatarioBajaTramitador';
                    selectorDestinatario.style.cssText = 'width:100%;padding:10px;border:1px solid #cbd5e1;border-radius:6px;font-size:14px;background:white';
                    const representante = opciones.find((opcion) => opcion.tipo === 'Representante legal');
                    const vacia = document.createElement('option');
                    vacia.value = '';
                    vacia.textContent = opciones.length ? 'Seleccione quién recibirá las correcciones' : 'No hay destinatarios habilitados en esta empresa';
                    selectorDestinatario.appendChild(vacia);
                    opciones.forEach((opcion) => {
                        const item = document.createElement('option');
                        item.value = opcion.id;
                        const esYo = opcion.tipo === 'Representante legal' && String(opcion.id) === formulario.dataset.usuarioActual;
                        item.textContent = `${esYo ? 'Yo, como representante legal' : opcion.tipo} — ${opcion.nombre}`;
                        item.selected = opcion.id === representante?.id;
                        selectorDestinatario.appendChild(item);
                    });
                    contenido.append(etiqueta, selectorDestinatario);
                    const ayuda = document.createElement('p');
                    ayuda.textContent = 'Solo se muestran el representante legal y los otros tramitadores activos y validados de esta empresa.';
                    ayuda.style.cssText = 'font-size:12px;color:#64748b;margin-top:6px';
                    contenido.appendChild(ayuda);
                }

                Swal.fire({
                    title: 'Dar de baja al tramitador',
                    width: '600px',
                    html: contenido,
                    input: 'textarea',
                    inputLabel: 'Motivo de la baja (obligatorio)',
                    inputPlaceholder: 'Explique por qué se da de baja al tramitador en esta empresa.',
                    inputAttributes: { maxlength: '2000', rows: '3', 'aria-label': 'Motivo de la baja' },
                    inputValidator: (valor) => !valor?.trim() ? 'Indique el motivo de la baja.' : undefined,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Confirmar baja',
                    cancelButtonText: 'Cancelar',
                    preConfirm: () => {
                        if (selectorDestinatario && !selectorDestinatario.value) {
                            Swal.showValidationMessage('Seleccione una persona habilitada para recibir las correcciones.');
                            return false;
                        }
                        return Swal.getInput().value.trim();
                    },
                }).then((resultado) => {
                    if (!resultado.isConfirmed) {
                        return;
                    }

                    formulario.querySelector('input[name="motivo_baja"]').value = resultado.value.trim();
                    formulario.querySelector('input[name="id_usuario_destinatario_baja"]').value = selectorDestinatario?.value || '';
                    formulario.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
                    formulario.submit();
                });
            });
        </script>
    @endpush
</x-admin-layout>
