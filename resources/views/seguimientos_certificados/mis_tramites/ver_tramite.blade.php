<x-admin-layout title="Ver tramite | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Mis tramites', 'href' => route('seguimientos_mis_solicitudes')],
    ['name' => 'Ver tramite', 'href' => route('certificados_show', ['certificado' => $certificado, 'bandeja' => 'enviadas'])],
]">
    @include('certificados.componentes.detalle_tramite')
</x-admin-layout>
