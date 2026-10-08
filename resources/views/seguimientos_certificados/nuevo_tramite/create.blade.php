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
        // Opciones listas para los selectores con búsqueda.
        $personasSelect = collect($personas ?? [])->values();

        $tiposCertificadosSelect = collect($tiposCertificados ?? [])
            ->map(
                fn($tipoCertificado) => [
                    'id' => $tipoCertificado->id,
                    'nombre' => $tipoCertificado->nombre,
                ],
            )
            ->values();

        // Conserva los valores seleccionados cuando Laravel devuelve errores.
        $beneficiarioSeleccionado = old('form_id_persona_beneficiario', $beneficiarioAutomatico['id'] ?? null);

        $tramitadorSeleccionado = old('form_id_persona_tramitador', $tramitadorAutomatico['id'] ?? null);

        $tipoSeleccionado = old('form_id_tipo_certificado');

        // Carga los tramitadores relacionados con el beneficiario seleccionado.
        $tramitadoresIniciales = collect($tramitadoresPorBeneficiario[$beneficiarioSeleccionado] ?? [])->values();
    @endphp

    {{-- Estilos propios de la pantalla para iniciar un trámite. --}}
    @include('seguimientos_certificados.nuevo_tramite.estilos')

    <form action="{{ route('seguimientos_store') }}" method="POST" enctype="multipart/form-data" id="formIniciarTramite"
        data-prevent-double-submit data-loading-alert="true" data-loading-title="Enviando trámite"
        data-loading-message="Espere un momento, estamos registrando la solicitud." data-loading-button="Enviando...">
        @csrf

        <input type="hidden" name="form_token" value="{{ $tokenFormulario }}">

        <div class="tramite-shell">
            {{-- Datos principales y requisitos del nuevo trámite. --}}
            <div class="tramite-content">
                @include('seguimientos_certificados.nuevo_tramite.inicio')
            </div>

            {{-- El botón registra o continúa al pago según los requisitos. --}}
            <div class="tramite-actions">
                <a href="{{ route('seguimientos_index') }}" class="tramite-btn tramite-btn-neutral">
                    <i class="fa-solid fa-xmark"></i>
                    Salir sin guardar
                </a>

                <button type="submit" name="form_accion" value="enviar" id="botonRegistrarTramite"
                    class="tramite-btn tramite-btn-primary">
                    <i id="iconoBotonRegistrarTramite" class="fa-regular fa-paper-plane"></i>

                    <span id="textoBotonRegistrarTramite">
                        Registrar trámite
                    </span>
                </button>
            </div>
        </div>
    </form>

    {{-- Requisitos dinámicos y controles del formulario. --}}
    @include('seguimientos_certificados.nuevo_tramite.script')
</x-admin-layout>
