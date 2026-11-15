<x-admin-layout title="Certificados emitidos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Trámites finalizados',
        'href' => route('seguimientos_finalizados'),
    ],
    [
        'name' => 'Certificados emitidos',
        'href' => route('certificados_emitidos_index'),
    ],
]">

    <style>
        @media (max-width: 767px) {
            .certificados-emitidos-table-shell {
                overflow: visible !important;
                border: 0 !important;
                background: transparent !important;
            }

            .certificados-emitidos-table,
            .certificados-emitidos-table tbody {
                display: block;
                width: 100% !important;
                min-width: 0 !important;
            }

            .certificados-emitidos-table thead {
                position: absolute;
                width: 1px;
                height: 1px;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
            }

            .certificados-emitidos-table tbody {
                display: grid;
                gap: .75rem;
                background: transparent;
            }

            .certificados-emitidos-table tbody tr {
                display: block;
                overflow: hidden;
                border: 1px solid #e2e8f0;
                border-radius: .75rem;
                background: #fff;
                box-shadow: 0 1px 2px rgb(15 23 42 / 5%);
            }

            .certificados-emitidos-table tbody td {
                display: grid;
                grid-template-columns: 6.25rem minmax(0, 1fr);
                gap: .75rem;
                width: 100% !important;
                min-width: 0 !important;
                max-width: none !important;
                padding: .7rem .9rem;
                border-bottom: 1px solid #f1f5f9;
                text-align: left !important;
                white-space: normal !important;
                overflow-wrap: anywhere;
            }

            .certificados-emitidos-table tbody td::before {
                content: attr(data-label);
                color: #64748b;
                font-size: .68rem;
                font-weight: 700;
                letter-spacing: .03em;
                text-transform: uppercase;
            }

            .certificados-emitidos-table tbody td:last-child {
                border-bottom: 0;
            }

            .certificados-emitidos-table tbody td[data-column="acciones"] > div {
                flex-wrap: wrap;
                white-space: normal;
                gap: .4rem;
            }
        }
    </style>

    <x-slot name="action">
        <div class="flex flex-wrap items-center gap-2">
            <x-wire-button href="{{ route('seguimientos_finalizados') }}" secondary>
                Volver a trámites finalizados
            </x-wire-button>
        </div>
    </x-slot>

    <div class="mb-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
        Para acciones en grupo, marca las casillas de los certificados y abre <strong>Acciones Masivas</strong>. La descarga reúne los PDF en un ZIP; la anulación solicita un motivo común.
    </div>

    @livewire('datatables.certificado-emitido-table')

    <dialog id="modal-anulacion-certificado" class="w-[min(94vw,34rem)] rounded-xl border-0 p-0 shadow-2xl backdrop:bg-slate-950/50">
        <form id="form-anulacion-certificado" method="POST" class="overflow-hidden rounded-xl bg-white">
            @csrf
            <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="modal-anulacion-titulo" class="text-lg font-bold text-slate-900">Anular certificado</h2>
                        <p id="modal-anulacion-resumen" class="mt-1 text-sm text-slate-600"></p>
                    </div>
                    <button type="button" onclick="document.getElementById('modal-anulacion-certificado').close()" class="rounded-md px-2 py-1 text-xl leading-none text-slate-500 hover:bg-slate-200" aria-label="Cerrar">&times;</button>
                </div>
            </div>
            <div class="space-y-3 px-5 py-4">
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
                    Los documentos se marcarán como anulados. Sus PDF, números y códigos QR se conservarán en el historial.
                </div>
                <label for="motivo-anulacion-lote" class="block text-sm font-semibold text-slate-700">Motivo de anulación</label>
                <textarea id="motivo-anulacion-lote" name="form_motivo_anulacion" rows="4" maxlength="1000" required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-200"
                    placeholder="Escribe el motivo que quedará registrado"></textarea>
                <p id="modal-anulacion-ayuda" class="text-xs text-slate-500">Este motivo se guardará en el historial del certificado.</p>
            </div>
            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4">
                <button type="button" onclick="document.getElementById('modal-anulacion-certificado').close()" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Confirmar anulación</button>
            </div>
        </form>
    </dialog>

    @push('js')
        <script>
            const modalAnulacion = document.getElementById('modal-anulacion-certificado');
            const formularioAnulacion = document.getElementById('form-anulacion-certificado');
            const campoMotivoAnulacion = document.getElementById('motivo-anulacion-lote');
            const tituloAnulacion = document.getElementById('modal-anulacion-titulo');
            const resumenAnulacion = document.getElementById('modal-anulacion-resumen');
            const ayudaAnulacion = document.getElementById('modal-anulacion-ayuda');

            window.abrirModalAnulacionCertificado = function(boton) {
                formularioAnulacion.action = boton.dataset.url;
                formularioAnulacion.querySelectorAll('.emision-anulacion-lote').forEach(campo => campo.remove());
                campoMotivoAnulacion.name = 'form_motivo_anulacion';
                campoMotivoAnulacion.value = '';
                tituloAnulacion.textContent = 'Anular certificado';
                resumenAnulacion.textContent = 'N.º ' + boton.dataset.numero;
                ayudaAnulacion.textContent = 'El motivo se guardará para este certificado.';
                modalAnulacion.showModal();
            };

            window.addEventListener('certificados-solicitar-motivo-lote', function(evento) {
                const emisiones = evento.detail.emisiones || [];
                if (!emisiones.length) return;

                formularioAnulacion.action = @json(route('certificados_emitidos_anular_lote'));
                formularioAnulacion.querySelectorAll('.emision-anulacion-lote').forEach(campo => campo.remove());
                emisiones.forEach(function(id) {
                    const campo = document.createElement('input');
                    campo.type = 'hidden';
                    campo.name = 'emisiones[]';
                    campo.value = id;
                    campo.className = 'emision-anulacion-lote';
                    formularioAnulacion.appendChild(campo);
                });
                campoMotivoAnulacion.name = 'motivo';
                campoMotivoAnulacion.value = '';
                tituloAnulacion.textContent = 'Anular certificados seleccionados';
                resumenAnulacion.textContent = emisiones.length + ' certificado(s) · Se usará el mismo motivo para todos.';
                ayudaAnulacion.textContent = 'El motivo se guardará en cada certificado que se anule.';
                modalAnulacion.showModal();
            });

            window.addEventListener('certificados-lote-mensaje', function(evento) {
                window.alert(evento.detail.mensaje || 'No se pudo completar la acción.');
            });
        </script>
    @endpush

</x-admin-layout>
