@include('pagos.openbcb._estilos')

<x-admin-layout title="Detalle del pago | Certificador" :breadcrumbs="[
    ['name' => 'Menú', 'href' => route('admin_dashboard')],
    ['name' => 'Trámite', 'href' => route('certificados_show', $certificado)],
    ['name' => 'Detalle del pago'],
]">

    @include('pagos.openbcb._estilos')

    @php
        // Tipo de certificado y precio configurado.
        $tipoCertificado = $certificado->tipoCertificado;
        $costoUnitario = (float) ($tipoCertificado?->costo ?? 0);

        // Por ahora cada pago corresponde a un certificado.
        $cantidad = 1;
        $montoTotal = $cantidad * $costoUnitario;

        // Beneficiario del trámite.
        $beneficiario = $certificado->beneficiario;
        $nombreBeneficiario = $beneficiario?->empresa?->razon_social;

        // Si no es una empresa, mostramos el nombre de la persona.
        if (blank($nombreBeneficiario)) {
            $nombreBeneficiario = collect([
                $beneficiario?->natural?->nombres,
                $beneficiario?->natural?->apellido_paterno,
                $beneficiario?->natural?->apellido_materno,
            ])
                ->filter()
                ->implode(' ');
        }
    @endphp

    <div class="bcb-page">
        <div class="bcb-head">
            <div>
                <h1 class="bcb-title">Detalle del pago</h1>
                <p class="bcb-subtitle">
                    Revise los datos antes de generar el código QR.
                </p>
            </div>
        </div>

        <section class="bcb-card">
            <h2 class="bcb-card-title">
                <i class="fa-solid fa-receipt" aria-hidden="true"></i>
                Información del trámite
            </h2>

            <div class="bcb-field-list">
                <div class="bcb-field">
                    <span class="bcb-label">Número de trámite</span>
                    <span class="bcb-value">#{{ $certificado->id }}</span>
                </div>

                <div class="bcb-field">
                    <span class="bcb-label">Tipo de certificado</span>
                    <span class="bcb-value">
                        {{ $tipoCertificado?->nombre ?? 'Sin tipo de certificado' }}
                    </span>
                </div>

                <div class="bcb-field">
                    <span class="bcb-label">Beneficiario</span>
                    <span class="bcb-value">
                        {{ $nombreBeneficiario ?: 'Sin beneficiario' }}
                    </span>
                </div>

                <div class="bcb-field">
                    <span class="bcb-label">Cantidad</span>
                    <span class="bcb-value">{{ $cantidad }}</span>
                </div>

                <div class="bcb-field">
                    <span class="bcb-label">Costo unitario</span>
                    <span class="bcb-value">
                        Bs {{ number_format($costoUnitario, 2, ',', '.') }}
                    </span>
                </div>

                <div class="bcb-field">
                    <span class="bcb-label">Total a pagar</span>
                    <span class="bcb-value">
                        Bs {{ number_format($montoTotal, 2, ',', '.') }}
                    </span>
                </div>

                <div class="bcb-field">
                    <span class="bcb-label">Moneda</span>
                    <span class="bcb-value">BOB - Bolivianos</span>
                </div>
            </div>

            @if ($montoTotal <= 0)
                <div class="bcb-note">
                    Este tipo de certificado todavía no tiene un costo configurado.
                </div>
            @endif

            <div class="bcb-actions">
                <a href="{{ route('certificados_show', $certificado) }}"
                    class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                    Volver al trámite
                </a>

                {{-- Se habilitará cuando conectemos la generación del QR. --}}
                <button type="button"
                    class="rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50"
                    disabled>
                    <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    Generar código QR
                </button>
            </div>
        </section>
    </div>
</x-admin-layout>
