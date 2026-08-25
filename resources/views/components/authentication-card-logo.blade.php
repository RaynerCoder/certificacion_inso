<a
    href="{{ url('/') }}"
    class="group flex flex-col items-center gap-3 rounded-xl px-4 py-2 text-center focus:outline-none focus:ring-2 focus:ring-green-700 focus:ring-offset-2"
    aria-label="Ir al inicio de Certificador"
>
    {{-- El mismo archivo se usa en el acceso, el favicon y las versiones descargables. --}}
    <img
        src="{{ asset('images/branding/certificador-icon.svg') }}"
        class="h-20 w-20 transition-transform duration-200 group-hover:scale-105"
        alt="Ícono de Certificador"
    >

    <span>
        <span class="block text-xl font-bold tracking-wide text-green-800">CERTIFICADOR</span>
        <span class="mt-1 block max-w-xs text-xs font-medium uppercase tracking-wider text-gray-500">
            Sistema de Gestión y Consulta de Certificados
        </span>
    </span>
</a>
