{{--
    Permite asignar o derivar el trámite a un funcionario técnico.
    Esta sección no administra requisitos ni pagos.
--}}
@if (
    $certificado->estado !== 'OBSERVADO' &&
        ($puedeAsignarTecnico || ($puedeRevisarRequisitos && $seguimientoTecnicoActual?->id_usuario_siguiente)))
    
    {{-- SECCION 6: asignacion o derivacion tecnica. Solo se elige funcionario y se agrega una descripcion. --}}
    <section class="tramite-card mb-4 tramite-section-technical">
        <div class="tramite-card-head">
            <h2 class="tramite-card-title">Asignación técnica</h2>
        </div>
        <div class="tramite-card-body">
            @if ($puedeAsignarTecnico)
                <form action="{{ route('seguimientos_asignar_tecnico', $seguimientoAtencionActual) }}" method="POST"
                    data-prevent-double-submit data-loading-button="Asignando...">
                    @csrf
                    <div class="cert-derive-grid">
                        @include('certificados.partials.selector_tecnico', [
                            'selectId' => 'id_funcionario_v2',
                            'selectName' => 'form_id_funcionario',
                            'selectLabel' => 'Funcionario asignado',
                            'placeholder' => 'Seleccione funcionario',
                            'oldValue' => old('form_id_funcionario'),
                            'tecnicos' => $tecnicosDerivacion,
                            'excluirIds' => [(int) $seguimientoAtencionActual?->id_usuario_siguiente],
                        ])
                        <div>
                            <label class="cert-show-label" for="descripcion_derivacion_v2">Referencia de
                                asignación</label>
                            <textarea id="descripcion_derivacion_v2" class="cert-review-textarea" name="form_descripcion_derivacion"
                                placeholder="Ingrese una referencia opcional">{{ old('form_descripcion_derivacion') }}</textarea>
                        </div>
                        <button type="submit" class="tramite-btn tramite-btn-primary">
                            <i class="fa-solid fa-user-check"></i>
                            Asignar
                        </button>
                    </div>
                </form>
            @endif

            @if ($puedeRevisarRequisitos && !$esJefeUnidad && $seguimientoTecnicoActual?->id_usuario_siguiente)
                <form action="{{ route('seguimientos_derivar_tecnico', $seguimientoTecnicoActual) }}" method="POST"
                    class="mt-4" data-prevent-double-submit data-loading-button="Derivando...">
                    @csrf
                    <div class="cert-derive-grid is-transfer">
                        @include('certificados.partials.selector_tecnico', [
                            'selectId' => 'id_funcionario_destino_v2',
                            'selectName' => 'form_id_funcionario_destino',
                            'selectLabel' => 'Funcionario destino',
                            'placeholder' => 'Seleccione funcionario',
                            'oldValue' => old('form_id_funcionario_destino'),
                            'tecnicos' => $tecnicosDerivacion,
                            'excluirIds' => [
                                (int) $seguimientoAtencionActual?->id_usuario_siguiente,
                                (int) $seguimientoTecnicoActual->id_usuario_siguiente,
                            ],
                        ])
                        <div>
                            <label class="cert-show-label" for="motivo_derivacion_v2">Referencia de derivación</label>
                            <textarea id="motivo_derivacion_v2" class="cert-review-textarea" name="form_motivo_derivacion"
                                placeholder="Ingrese la referencia de esta derivación." required>{{ old('form_motivo_derivacion') }}</textarea>
                        </div>
                        <button type="submit" class="tramite-btn tramite-btn-primary">
                            <i class="fa-solid fa-share"></i>
                            Derivar
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endif
