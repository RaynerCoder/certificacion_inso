<x-admin-layout title="Iniciar tramite | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Tramites',
        'href' => route('seguimientos_index'),
    ],
    [
        'name' => 'Iniciar tramite',
        'href' => route('seguimientos_index'),
    ],
]">
    @php
        // Opciones listas para los selectores con busqueda.
        // Personas conserva nombre, detalle y tipo para pintar beneficiario/tramitador.
        $personasSelect = collect($personas ?? [])->values();
        $tiposCertificadosSelect = collect($tiposCertificados ?? [])
            ->map(fn($tipoCertificado) => [
                'id' => $tipoCertificado->id,
                'nombre' => $tipoCertificado->nombre,
            ])
            ->values();

        // Mantiene lo que el usuario eligio si Laravel devuelve errores.
        $beneficiarioSeleccionado = old('form_id_persona_beneficiario', $beneficiarioAutomatico['id'] ?? null);
        $tramitadorSeleccionado = old('form_id_persona_tramitador');
        $tipoSeleccionado = old('form_id_tipo_certificado');

        // Al volver con error, se cargan solo los tramitadores del beneficiario elegido.
        $tramitadoresIniciales = collect($tramitadoresPorBeneficiario[$beneficiarioSeleccionado] ?? [])->values();
    @endphp

    {{-- Estilos propios de esta pantalla. Si necesitas ajustar el formulario, empieza por este archivo. --}}
    @include('seguimientos_certificados.nuevo_tramite.estilos')

    <form action="{{ route('seguimientos_store') }}"
        method="POST"
        enctype="multipart/form-data"
        id="formIniciarTramite"
        data-prevent-double-submit
        data-loading-alert="true"
        data-loading-title="Enviando trámite"
        data-loading-message="Espere un momento, estamos registrando la solicitud."
        data-loading-button="Enviando...">
        @csrf
        <input type="hidden" name="form_token" value="{{ $tokenFormulario }}">

        <div class="tramite-shell">
            {{-- Formulario simple: el tramite se inicia con datos principales y documentos requeridos. --}}
            <div class="tramite-content">
                @include('seguimientos_certificados.nuevo_tramite.inicio')
            </div>

            {{-- Acciones finales del formulario: salir no guarda nada; registrar envia el tramite al area configurada en el tipo de certificado. --}}
            <div class="tramite-actions">
                <a href="{{ route('seguimientos_index') }}" class="tramite-btn tramite-btn-neutral">
                    <i class="fa-solid fa-xmark"></i> Salir sin guardar
                </a>

                <button type="submit" name="accion" value="enviar" class="tramite-btn tramite-btn-primary">
                    <i class="fa-regular fa-paper-plane"></i> Registrar tramite
                </button>
            </div>
        </div>
    </form>

    {{-- Script propio de esta pantalla: requisitos dinamicos, PDF y sincronizacion de selects. --}}
    @include('seguimientos_certificados.nuevo_tramite.script')
</x-admin-layout>
