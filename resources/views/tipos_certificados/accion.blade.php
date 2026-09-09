<div class="tipos-certificados-acciones">

    {{-- Ambos permisos pueden consultar el detalle completo. --}}
    <x-wire-button href="{{ route('tipos_certificados_show', $tipoCertificado) }}" green xs>
        Ver
    </x-wire-button>

    @permiso('tipos_certificados.gestionar')
        <x-wire-button type="button" blue xs data-id="{{ $tipoCertificado->id }}"
            data-nombre="{{ $tipoCertificado->nombre }}" data-estado="{{ $tipoCertificado->estado }}"
            data-url="{{ route('tipos_certificados_update_basico', $tipoCertificado) }}"
            onclick="abrirModalEdicionTipoCertificado(this)">
            Editar
        </x-wire-button>
    @endpermiso

    @permiso('tipos_certificados.configurar')
        <x-wire-button href="{{ route('tipos_certificados_configuracion', $tipoCertificado) }}" amber xs>
            Configurar
        </x-wire-button>
    @endpermiso

</div>
