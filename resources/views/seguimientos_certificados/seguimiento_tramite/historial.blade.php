@php
    $bandejaRetorno = \App\Support\BandejaTramite::retorno(request('bandeja', $bandeja));
    $urlBandejaRetorno = route($bandejaRetorno[1]);
@endphp
<x-admin-layout :title="'Seguimiento del trámite | Certificador'" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => $bandejaRetorno[0],
        'href' => $urlBandejaRetorno,
    ],
    [
        'name' => 'Seguimiento del trámite',
        'href' => route('seguimientos_tramite_historial', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]),
    ],
]">
    @include('seguimientos_certificados.seguimiento_tramite.historial-estilos')

    <section class="timeline-page">
        <div class="timeline-page-head">
            <div>
                <h1 class="timeline-page-title">Seguimiento del trámite</h1>
            </div>

            <a href="{{ $urlBandejaRetorno }}"
                class="timeline-page-action">
                <i class="fa-solid fa-arrow-left"></i>
                Volver
            </a>
        </div>

        {{-- Vista modular: el historial vive en un partial para no cargar el detalle de revision. --}}
        @include('seguimientos_certificados.componentes.historial_general', ['certificado' => $certificado])
    </section>
</x-admin-layout>
