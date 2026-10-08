<x-admin-layout title="Detalle de tramite | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Tramites', 'href' => request('bandeja') === 'todos' ? route('seguimientos_todos') : route('seguimientos_index')],
    ['name' => 'Detalle', 'href' => route('certificados_show', $certificado)],
]">
    {{-- Carga el detalle completo del trámite. --}}
    @include('certificados.detalle_tramite.index')
</x-admin-layout>
