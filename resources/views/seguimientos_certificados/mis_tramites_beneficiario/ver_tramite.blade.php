<x-admin-layout title="Ver trámite | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Mis trámites', 'href' => route('seguimientos_mis_tramites_beneficiario')],
    ['name' => 'Ver trámite', 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => 'enviadas'])],
]">
    @include('certificados.componentes.detalle_tramite')
</x-admin-layout>
