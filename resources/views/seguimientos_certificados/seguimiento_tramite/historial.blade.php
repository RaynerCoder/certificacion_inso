<x-admin-layout :title="'Historial de seguimiento | Certificador'" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => $bandeja === 'enviadas' ? 'Solicitudes enviadas' : ($bandeja === 'todos' ? 'Consulta general' : 'Solicitudes recibidas'),
        'href' => $bandeja === 'enviadas'
            ? route('seguimientos_mis_tramites_beneficiario')
            : ($bandeja === 'todos' ? route('seguimientos_todos') : route('seguimientos_index')),
    ],
    [
        'name' => 'Historial de seguimiento',
        'href' => route('seguimientos_tramite_historial', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]),
    ],
]">
    @include('seguimientos_certificados.seguimiento_tramite.historial-estilos')

    <section class="timeline-page">
        <div class="timeline-page-head">
            <div>
                <h1 class="timeline-page-title">Historial de seguimiento del tramite</h1>
                <p class="timeline-page-description">
                    Consulte la auditoría completa de movimientos, responsables, referencias y descripciones.
                </p>
            </div>

            <a href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}"
                class="timeline-page-action">
                <i class="fa-solid fa-arrow-left"></i>
                Volver al tramite
            </a>
        </div>

        {{-- Vista modular: el historial vive en un partial para no cargar el detalle de revision. --}}
        @include('seguimientos_certificados.componentes.historial_general', ['certificado' => $certificado])
    </section>
</x-admin-layout>
