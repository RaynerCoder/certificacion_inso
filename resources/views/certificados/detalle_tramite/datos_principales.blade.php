{{--
    Muestra los datos principales del trámite:
    beneficiario, tramitador e información de inicio.
--}}
{{-- Datos de la persona o empresa beneficiaria. --}}
<article class="tramite-detail-panel tramite-panel-beneficiario">
    <h3 class="tramite-detail-title">
        <i class="fa-regular fa-file-lines"></i>Solicitante / Beneficiario
    </h3>
    
    <dl class="tramite-definition is-compact">
        <div class="tramite-definition-row"><dt>Solicitante</dt><dd>{{ $nombrePersona($certificado->beneficiario) }}</dd></div>
        <div class="tramite-definition-row"><dt>CI / NIT</dt><dd>{{ $identificacionPersona($certificado->beneficiario) }}</dd></div>
        <div class="tramite-definition-row"><dt>Correo electronico</dt><dd>{{ $certificado->beneficiario?->correo ?? 'Sin correo' }}</dd></div>
        <div class="tramite-definition-row"><dt>Telefono</dt><dd>{{ $telefonoPersona($certificado->beneficiario) }}</dd></div>
        <div class="tramite-definition-row"><dt>Territorio</dt><dd>{{ $certificado->beneficiario?->territorio?->nombre ?? 'Sin territorio' }}</dd></div>
    </dl>
</article>

{{-- Datos de la persona que realiza el trámite. --}}
<article class="tramite-detail-panel tramite-panel-tramitador">
    <h3 class="tramite-detail-title"><i class="fa-regular fa-user"></i>Tramitador</h3>
    <dl class="tramite-definition is-compact">
        
        <div class="tramite-definition-row"><dt>Tramitador</dt>
            <dd><span class="block">{{ $nombrePersona($certificado->tramitador) }}</span>
            <span class="tramite-summary-role">{{ $tipoRelacionTramitadorActual }}</span></dd>
        </div>

        <div class="tramite-definition-row"><dt>CI / NIT</dt>
            <dd>{{ $identificacionPersona($certificado->tramitador) }}</dd>
        </div>
        
        <div class="tramite-definition-row"><dt>Correo electronico</dt>
            <dd>{{ $certificado->tramitador?->correo ?? 'Sin correo' }}</dd>
        </div>

        <div class="tramite-definition-row"><dt>Telefono</dt>
            <dd>{{ $telefonoPersona($certificado->tramitador) }}</dd>
        </div>

        <div class="tramite-definition-row"><dt>Territorio</dt>
            <dd>{{ $certificado->tramitador?->territorio?->nombre ?? 'Sin territorio' }}</dd>
        </div>
    </dl>
</article>

{{-- Datos de creación y primer destino del trámite. --}}
<article class="tramite-detail-panel tramite-panel-inicio">
    <h3 class="tramite-detail-title"><i class="fa-solid fa-users"></i>Inicio del tramite</h3>
    <dl class="tramite-definition is-compact">
        
        <div class="tramite-definition-row"><dt>Fecha de solicitud</dt>
            <dd>{{ ($certificado->fecha_inicio ?? $certificado->created_at)?->format('d/m/Y H:i') ?? 'Sin fecha' }}</dd>
        </div>
        
        <div class="tramite-definition-row"><dt>Codigo de solicitud</dt>
            <dd>{{ $certificado->codigo ?: 'Auto-generado al enviar' }}</dd>
        </div>
        
        <div class="tramite-definition-row"><dt>Area que recibe</dt>
            <dd>{{ $seguimientoActualDetalle?->usuarioSiguiente?->funcionario?->cargos?->pluck('area.nombre')->filter()->implode(', ') ?: 'Sin area asignada' }}</dd>
        </div>
        
        <div class="tramite-definition-row"><dt>Registrado inicialmente por</dt>
            <dd class="tramite-user-stack">
                <span class="tramite-user-name">{{ $nombreUsuario($seguimientoOrigenDetalle?->usuarioOrigen, 'Sin usuario de registro') }}</span>
                    @if ($seguimientoOrigenDetalle?->usuarioOrigen)
                        <span class="tramite-user-cargo">
                            {{ $rolUsuarioEnTramite($seguimientoOrigenDetalle->usuarioOrigen) }}
                        </span>
                    @endif
            </dd>
        </div>
        
        <div class="tramite-definition-row"><dt>Primer destino</dt>
            <dd class="tramite-user-stack">
                <span class="tramite-user-name">{{ $nombreUsuario($seguimientoOrigenDetalle?->usuarioSiguiente, 'Sin destino inicial') }}</span>
                <span class="tramite-user-cargo">
                    {{ $cargoUsuario($seguimientoOrigenDetalle?->usuarioSiguiente) }}
                </span>
            </dd>
        </div>
    </dl>
</article>