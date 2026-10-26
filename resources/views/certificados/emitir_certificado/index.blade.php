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
