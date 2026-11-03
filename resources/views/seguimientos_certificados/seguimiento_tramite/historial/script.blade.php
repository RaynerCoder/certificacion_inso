{{-- Script de filtros del historial general. --}}
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const raiz = document.querySelector('[data-hoja-ruta]');

        if (!raiz) {
            return;
        }

        const buscador = raiz.querySelector('[data-ruta-search]');
        const filtroEstado = raiz.querySelector('[data-ruta-status]');
        const filas = Array.from(raiz.querySelectorAll('[data-ruta-row]'));
        const mensajeVacio = raiz.querySelector('[data-ruta-empty]');

        function aplicarFiltros() {
            const texto = (buscador?.value || '').trim().toLowerCase();
            const estado = (filtroEstado?.value || '').trim().toLowerCase();
            let visibles = 0;

            filas.forEach((fila) => {
                const busqueda = fila.dataset.search || '';
                const estadoFila = fila.dataset.status || '';
                const coincideTexto = !texto || busqueda.includes(texto);
                const coincideEstado = !estado || estadoFila.includes(estado);
                const visible = coincideTexto && coincideEstado;

                fila.hidden = !visible;
                visibles += visible ? 1 : 0;
            });

            mensajeVacio?.classList.toggle('is-visible', visibles === 0);
        }

        [buscador, filtroEstado].forEach((control) => {
            control?.addEventListener('input', aplicarFiltros);
            control?.addEventListener('change', aplicarFiltros);
        });
    });
</script>
