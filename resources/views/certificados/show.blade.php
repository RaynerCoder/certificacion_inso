@php
    $bandejaRetorno = \App\Support\BandejaTramite::retorno(request('bandeja'));
    $urlBandejaRetorno = route($bandejaRetorno[1]);
@endphp
<x-admin-layout title="Detalle de tramite | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => $bandejaRetorno[0], 'href' => $urlBandejaRetorno],
    ['name' => 'Detalle', 'href' => route('certificados_show', $certificado)],
]">
    {{-- Carga el detalle completo del trámite. --}}
    @include('certificados.detalle_tramite.index')
</x-admin-layout>
