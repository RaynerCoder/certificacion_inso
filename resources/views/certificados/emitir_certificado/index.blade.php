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

    @livewire('datatables.certificado-emitido-table')

    @push('js')
        <script>
            window.confirmarAnulacionCertificado = function(formulario, numeroCertificado) {
                const motivo = window.prompt(
                    'Indique el motivo de anulación del certificado ' + numeroCertificado + '.'
                );

                if (!motivo || !motivo.trim()) {
                    return false;
                }

                if (!window.confirm('El certificado quedará anulado. Su PDF, número y QR se conservarán como historial. ¿Desea continuar?')) {
                    return false;
                }

                formulario.querySelector('[name="form_motivo_anulacion"]').value = motivo.trim();
                return true;
            };
        </script>
    @endpush

</x-admin-layout>
