<x-admin-layout title="Pago del trámite | Certificador" :breadcrumbs="[
    ['name' => 'Mis trámites', 'href' => route('seguimientos_mis_tramites_beneficiario')],
    ['name' => 'Pago del trámite'],
]">
    @include('seguimientos_certificados.pago_tramite.estilos')

    {{-- Respaldo para abrir el pago directamente mediante su URL. --}}
    <div class="pago-modal-backdrop">
        @include('seguimientos_certificados.pago_tramite.contenido')
    </div>

    @include('seguimientos_certificados.pago_tramite.script')
</x-admin-layout>
