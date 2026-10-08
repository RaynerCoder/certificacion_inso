{{--
    Modal utilizado por funcionarios.
    Permite registrar o corregir un pago manual del trámite.
    El pago QR del solicitante se administra en pagos/openbcb.
--}}

@if ($requierePagoTramite && ($puedeRegistrarPago || $puedeEditarPago))
    {{-- El mismo formulario registra o corrige el pago sin duplicar campos ni reglas. --}}
    <div id="modalRegistrarPagoTramite" class="tramite-modal {{ $abrirModalPago ? 'is-open' : '' }}" data-payment-modal
        @if ($abrirModalPago) data-open-on-error="1" @endif
        aria-hidden="{{ $abrirModalPago ? 'false' : 'true' }}">
        <div class="tramite-modal-backdrop" data-close-payment-modal></div>
        <section class="tramite-modal-panel" role="dialog" aria-modal="true" aria-labelledby="tituloModalPagoTramite">
            <div class="tramite-modal-head">
                <div>
                    <h2 id="tituloModalPagoTramite" class="tramite-card-title">
                        <i class="fa-solid fa-credit-card"></i>
                        <span
                            data-payment-modal-title>{{ $modalPagoEnEdicion ? 'Editar pago' : 'Registrar pago' }}</span>
                    </h2>
                    <p data-payment-modal-description>
                        {{ $modalPagoEnEdicion ? 'Corrija los datos del pago relacionado a este trámite.' : 'Complete el pago relacionado a este trámite.' }}
                    </p>
                </div>
                <button type="button" class="tramite-modal-close" data-close-payment-modal
                    aria-label="Cerrar modal de pago">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ $modalPagoEnEdicion ? route('pagos_update', $pagoEditando) : route('pagos_store') }}"
                method="POST" enctype="multipart/form-data" data-payment-form
                data-store-url="{{ route('pagos_store') }}"
                data-current-pdf-url="{{ $pagoEditando?->comprobante ? route('pagos_comprobante', $pagoEditando) : '' }}">
                @csrf
                <input type="hidden" data-payment-method
                    @if ($modalPagoEnEdicion) name="_method" value="PUT" @endif>
                <input type="hidden" name="form_id_pago" data-payment-id value="{{ $pagoEditando?->id }}">
                <input type="hidden" name="form_id_certificado" value="{{ $certificado->id }}">
                <input type="hidden" name="form_bandeja" value="{{ request('bandeja', 'recibidas') }}">

                <div class="tramite-payment-form-grid">
                    <div class="tramite-payment-field-6">
                        <label class="cert-show-label" for="form_id_procedencia_pago">Procedencia</label>
                        <select id="form_id_procedencia_pago" name="form_id_procedencia_pago"
                            class="cert-review-select @error('form_id_procedencia_pago') is-invalid @enderror" required>
                            <option value="">Seleccione procedencia</option>
                            @foreach ($procedenciasPago as $procedencia)
                                <option value="{{ $procedencia->id }}" @selected(old('form_id_procedencia_pago', $pagoEditando?->id_procedencia) == $procedencia->id)>
                                    {{ $procedencia->codigo }}{{ $procedencia->descripcion ? ' - ' . $procedencia->descripcion : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('form_id_procedencia_pago')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="tramite-payment-field-6">
                        <label class="cert-show-label" for="form_tipo_pago">Tipo de pago</label>
                        <select id="form_tipo_pago" name="form_tipo_pago"
                            class="cert-review-select @error('form_tipo_pago') is-invalid @enderror" required>
                            <option value="">Seleccione tipo</option>
                            @foreach (\App\Models\Pago::TIPOS_PAGOS as $valor => $texto)
                                <option value="{{ $valor }}" @selected(old('form_tipo_pago', $pagoEditando?->tipo_pago) === $valor)>{{ $texto }}
                                </option>
                            @endforeach
                        </select>
                        @error('form_tipo_pago')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="tramite-payment-field-4">
                        <label class="cert-show-label" for="form_fecha_pago">Fecha de pago</label>
                        <input id="form_fecha_pago" name="form_fecha_pago" type="date"
                            class="cert-review-select @error('form_fecha_pago') is-invalid @enderror"
                            value="{{ old('form_fecha_pago', $pagoEditando?->fecha ?? now()->toDateString()) }}"
                            required>
                        @error('form_fecha_pago')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="tramite-payment-field-4">
                        <label class="cert-show-label" for="form_monto_pago">Monto</label>
                        <input id="form_monto_pago" name="form_monto_pago" type="number" min="0.01" step="0.01"
                            class="cert-review-select @error('form_monto_pago') is-invalid @enderror"
                            value="{{ old('form_monto_pago', $pagoEditando?->monto) }}" placeholder="0.00" required>
                        @error('form_monto_pago')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="tramite-payment-field-4">
                        <label class="cert-show-label" for="form_factura_pago">Factura</label>
                        <input id="form_factura_pago" name="form_factura_pago" type="text" maxlength="100"
                            class="cert-review-select @error('form_factura_pago') is-invalid @enderror"
                            value="{{ old('form_factura_pago', $pagoEditando?->factura) }}"
                            placeholder="Ingrese el número de factura">
                        @error('form_factura_pago')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="tramite-payment-field-12">
                        <label class="cert-show-label" for="form_comprobante_pago">Comprobante PDF <span
                                class="font-normal text-slate-500">(opcional)</span></label>
                        <div class="tramite-payment-pdf @error('form_comprobante_pago') is-invalid @enderror">
                            <input id="form_comprobante_pago" name="form_comprobante_pago" type="file"
                                accept="application/pdf,.pdf" class="hidden" data-payment-pdf-input>
                            <span class="tramite-payment-pdf-icon"><i class="fa-regular fa-file-pdf"></i></span>
                            <span class="tramite-payment-pdf-details">
                                <strong class="tramite-payment-pdf-name"
                                    data-payment-pdf-name>{{ $modalPagoEnEdicion && $pagoEditando?->comprobante ? 'Comprobante actual registrado' : 'Sin PDF seleccionado' }}</strong>
                                <small
                                    data-payment-pdf-help>{{ $modalPagoEnEdicion ? 'Seleccione otro PDF solo para reemplazarlo' : 'PDF opcional · Máximo 10 MB' }}</small>
                            </span>
                            <span class="tramite-payment-pdf-actions">
                                <button type="button" class="tramite-payment-pdf-button is-select"
                                    data-payment-pdf-select aria-label="Seleccionar comprobante PDF"
                                    title="Seleccionar PDF">
                                    <i class="fa-solid fa-upload" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="tramite-payment-pdf-button is-view"
                                    data-payment-pdf-view aria-label="Ver comprobante PDF" title="Ver PDF" disabled>
                                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="tramite-payment-pdf-button is-remove"
                                    data-payment-pdf-remove aria-label="Quitar comprobante PDF" title="Quitar PDF"
                                    disabled>
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </span>
                        </div>
                        @error('form_comprobante_pago')
                            <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="tramite-actions-row">
                    <button type="button" class="tramite-btn tramite-btn-secondary" data-close-payment-modal>
                        Cancelar
                    </button>
                    <button type="submit" class="tramite-btn tramite-btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i>
                        <span
                            data-payment-submit-label>{{ $modalPagoEnEdicion ? 'Actualizar pago' : 'Registrar pago' }}</span>
                    </button>
                </div>
            </form>
        </section>
    </div>
@endif
