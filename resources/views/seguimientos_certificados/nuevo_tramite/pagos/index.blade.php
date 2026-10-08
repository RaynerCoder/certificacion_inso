<x-admin-layout title="Realizar pago | Certificador" :breadcrumbs="[
    [
        'name' => 'Mis trámites',
        'href' => route('seguimientos_mis_tramites_beneficiario'),
    ],
    [
        'name' => 'Realizar pago',
    ],
]">
    @php
        // Obtiene el tipo de certificado.
        $tipoCertificado = $certificado->tipoCertificado;

        // Obtiene el beneficiario.
        $beneficiario = $certificado->beneficiario;
        $nombreBeneficiario = $beneficiario?->empresa?->razon_social;

        // Si es persona natural, forma su nombre completo.
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


    <div class="mx-auto max-w-5xl">

        {{-- Encabezado --}}
        <div class="mb-4">
            <h1 class="text-xl font-semibold text-slate-900">
                Realizar pago
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Verifique los datos del trámite y genere el código QR para realizar el pago.
            </p>
        </div>


        {{-- Contenido principal --}}
        <div class="grid overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm lg:grid-cols-5">

            {{-- ================================================= --}}
            {{-- RESUMEN DEL TRÁMITE --}}
            {{-- ================================================= --}}
            <section class="lg:col-span-3">

                {{-- Encabezado --}}
                <div class="border-b border-slate-200 px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Resumen del trámite
                    </h2>
                </div>


                {{-- Datos --}}
                <div class="px-5 py-2">

                    <dl class="divide-y divide-slate-100">

                        {{-- Número de trámite --}}
                        <div class="grid gap-1 py-3 sm:grid-cols-[160px_1fr] sm:gap-4">

                            <dt class="text-sm text-slate-500">
                                Número de trámite
                            </dt>

                            <dd class="text-sm font-medium text-slate-900">
                                #{{ $certificado->id }}
                            </dd>

                        </div>


                        {{-- Tipo de certificado --}}
                        <div class="grid gap-1 py-3 sm:grid-cols-[160px_1fr] sm:gap-4">

                            <dt class="text-sm text-slate-500">
                                Tipo de certificado
                            </dt>

                            <dd class="text-sm font-medium text-slate-900">
                                {{ $tipoCertificado?->nombre ?? 'Sin información' }}
                            </dd>

                        </div>


                        {{-- Beneficiario --}}
                        <div class="grid gap-1 py-3 sm:grid-cols-[160px_1fr] sm:gap-4">

                            <dt class="text-sm text-slate-500">
                                Beneficiario
                            </dt>

                            <dd class="text-sm font-medium leading-5 text-slate-900">
                                {{ $nombreBeneficiario ?: 'Sin información' }}
                            </dd>

                        </div>

                    </dl>

                </div>


                {{-- Importe --}}
                <div class="border-t border-slate-200 bg-slate-50 px-5 py-4">

                    <div class="flex items-center justify-between gap-4">

                        <div>
                            <span class="block text-sm font-medium text-slate-700">
                                Importe a pagar
                            </span>

                            <span class="text-xs text-slate-500">
                                Expresado en bolivianos
                            </span>
                        </div>

                        <strong class="whitespace-nowrap text-xl font-semibold text-teal-700">
                            Bs {{ number_format((float) $pago->monto, 2, ',', '.') }}
                        </strong>

                    </div>

                </div>

            </section>


            {{-- ================================================= --}}
            {{-- PAGO QR --}}
            {{-- ================================================= --}}
            <section
                class="border-t border-slate-200 bg-slate-50/50
                       lg:col-span-2 lg:border-l lg:border-t-0">

                {{-- Encabezado --}}
                <div class="border-b border-slate-200 bg-white px-5 py-3">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Pago mediante QR
                    </h2>
                </div>


                {{-- Área del QR --}}
                <div class="flex h-full flex-col items-center px-5 py-5">

                    {{-- Muestra el QR guardado o un espacio vacío mientras todavía no exista. --}}
                    <div id="contenedorQr"
                        class="flex h-40 w-40 items-center justify-center overflow-hidden rounded-lg border border-slate-300 bg-white">
                        @if ($ultimoQrOpenBcb?->imagen_qr_path)
                            <img src="{{ route('pagos_openbcb_mostrar_imagen_qr', ['pagoOpenBcb' => $ultimoQrOpenBcb->id], false) }}"
                                alt="Código QR para realizar el pago" class="h-full w-full object-contain">
                        @else
                            <div class="text-center">
                                <i class="fa-solid fa-qrcode text-3xl text-slate-300"></i>

                                <p class="mt-2 text-xs text-slate-400">
                                    Código QR pendiente
                                </p>
                            </div>
                        @endif
                    </div>


                    {{-- Texto --}}
                    <p class="mt-3 text-center text-xs leading-5 text-slate-500">
                        Genere el código QR para efectuar el pago.
                    </p>


                    {{-- Generar QR --}}
                    <button type="button" id="botonGenerarQr"
                        data-url="{{ route('pagos_openbcb_generar_qr', ['pago' => $pago->id]) }}"
                        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-md bg-teal-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">

                        <i class="fa-solid fa-qrcode"></i>

                        <span>Generar código QR</span>
                    </button>

                </div>

            </section>

        </div>


        {{-- Volver --}}
        <div class="mt-4">

            <a href="{{ route('seguimientos_mis_tramites_beneficiario') }}"
                class="inline-flex w-full items-center justify-center gap-2
                       rounded-md border border-slate-300 bg-white
                       px-4 py-2 text-sm font-medium text-slate-700
                       transition hover:bg-slate-50
                       sm:w-auto">
                <i class="fa-solid fa-arrow-left text-xs"></i>

                Volver a mis trámites
            </a>

        </div>

    </div>


    {{-- Activa el botón que solicita la generación del código QR. --}}
    @include('seguimientos_certificados.nuevo_tramite.pagos.script')

</x-admin-layout>
