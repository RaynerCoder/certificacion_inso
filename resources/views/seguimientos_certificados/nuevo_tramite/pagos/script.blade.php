<script>
    document.addEventListener('DOMContentLoaded', () => {
        const botonGenerarQr = document.getElementById('botonGenerarQr');

        if (!botonGenerarQr) {
            return;
        }

        botonGenerarQr.addEventListener('click', async () => {
            const textoBoton = botonGenerarQr.querySelector('span');

            // Evita generar varias solicitudes mientras esperamos la respuesta.
            botonGenerarQr.disabled = true;
            textoBoton.textContent = 'Generando QR...';

            try {
                // Solicita al controlador que genere y guarde el QR.
                const respuesta = await fetch(botonGenerarQr.dataset.url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json',
                    },
                });

                const resultado = await respuesta.json();

                if (!respuesta.ok || !resultado.finalizado) {
                    throw new Error(
                        resultado.mensaje || 'No se pudo generar el código QR.'
                    );
                }

                // Recarga la pantalla para mostrar la imagen guardada.
                window.location.reload();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'No se pudo generar el QR',
                    text: error.message,
                    confirmButtonColor: '#0f766e',
                });

                botonGenerarQr.disabled = false;
                textoBoton.textContent = 'Generar código QR';
            }
        });
    });
</script>
