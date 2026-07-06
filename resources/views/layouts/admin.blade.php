@props([
    'title' => config('app.name', 'Laravel'),
    'breadcrumbs' => []
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Ubicacion -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    {{-- FontAwesome CSS: carga los iconos usados en menu y formularios con clases fa-solid/fa-*. --}}
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        referrerpolicy="no-referrer" />

    {{-- FontAwesome Kit: se deja como respaldo por si alguna vista usa iconos del kit anterior. --}}
    <script src="https://kit.fontawesome.com/e2d71e4ca2.js" crossorigin="anonymous"></script>

    <!-- SweetAlert2-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Ubicacion -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- wireui --}}
    <wireui:scripts />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>

<body class="font-sans antialiased bg-gray-50">

    @include('layouts.includes.admin.navigation')

    @include('layouts.includes.admin.sidebar')

    <div class="p-4 sm:ml-64">
        
        <div class="mt-14 flex items-center">

            @include('layouts.includes.admin.breadcrumb')

            @isset($action)
                <div class="ml-auto">
                    {{ $action }}
                </div>
            @endisset
        </div>

        {{ $slot }}

    </div>

    @stack('modals')

    @livewireScripts


    <!-- Este script es para el funcionamiento de los componentes de Flowbite -->
    <script src="https://cdn.jsdelivr.net/npm/flowbite@3.1.2/dist/flowbite.min.js"></script>
 

    <!-- Es para preguntar si existe una variable de sesión de SweetAlert -->
    @if (session('swal'))
        <script>
            Swal.fire(@json(session('swal')));
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('form[data-prevent-double-submit]').forEach((formulario) => {
                formulario.addEventListener('submit', (event) => {
                    if (formulario.dataset.enviando === '1') {
                        event.preventDefault();
                        return;
                    }

                    formulario.dataset.enviando = '1';

                    // Si el boton tiene name/value, se guarda antes de desactivarlo para que llegue al controlador.
                    const botonEnviado = event.submitter;
                    if (botonEnviado?.name) {
                        let campoAccion = formulario.querySelector('input[data-submitter-value="true"]');

                        if (!campoAccion) {
                            campoAccion = document.createElement('input');
                            campoAccion.type = 'hidden';
                            campoAccion.dataset.submitterValue = 'true';
                            formulario.appendChild(campoAccion);
                        }

                        campoAccion.name = botonEnviado.name;
                        campoAccion.value = botonEnviado.value;
                    }

                    const textoBoton = formulario.dataset.loadingButton || 'Enviando...';
                    const botones = formulario.querySelectorAll('button[type="submit"], input[type="submit"]');

                    botones.forEach((boton) => {
                        boton.disabled = true;
                        boton.classList.add('opacity-75', 'cursor-not-allowed');

                        if (boton.tagName === 'BUTTON') {
                            boton.dataset.textoOriginal = boton.innerHTML;
                            boton.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> ${textoBoton}`;
                        } else {
                            boton.dataset.textoOriginal = boton.value;
                            boton.value = textoBoton;
                        }
                    });

                    if (formulario.dataset.loadingAlert === 'true' && window.Swal) {
                        Swal.fire({
                            title: formulario.dataset.loadingTitle || 'Enviando solicitud',
                            text: formulario.dataset.loadingMessage || 'Espere un momento.',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => Swal.showLoading(),
                        });
                    }
                });
            });
        });
    </script>

    
    <!-- Un stack sirve para incluir scripts adicionales en las vistas que extienden este layout -->
    @stack('js')


</body>

</html>
