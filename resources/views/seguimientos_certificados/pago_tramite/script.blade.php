<script>
    (() => {
        const modalPago = document.getElementById('modalPagoTramite');
        const contenidoModal = modalPago?.querySelector('[data-modal-pago-contenido]');

        const contenidoCargando = `
            <div class="pago-modal-loading" role="status">
                <span class="pago-spinner" aria-hidden="true"></span>
                <strong>Cargando información del pago</strong>
            </div>
        `;

        const cargarContenidoModal = async (url, abrirModal = true) => {
            if (!modalPago || !contenidoModal) {
                window.location.href = url.replace(/[?&]modal=1/, '');
                return;
            }

            contenidoModal.innerHTML = contenidoCargando;

            if (abrirModal && !modalPago.open) {
                modalPago.showModal();
            }

            try {
                const respuesta = await fetch(url, {
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!respuesta.ok) {
                    throw new Error('No se pudo cargar la información del pago.');
                }

                contenidoModal.innerHTML = await respuesta.text();
                inicializarContenidoPago(contenidoModal);
            } catch (error) {
                contenidoModal.innerHTML = `
                    <div class="pago-modal-loading is-error" role="alert">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                        <strong>No se pudo abrir el pago</strong>
                        <p></p>
                        <button type="button" class="pago-action" data-cerrar-modal-pago>Cerrar</button>
                    </div>
                `;
                contenidoModal.querySelector('p').textContent = error.message;
            }
        };

        const iniciarContador = (raiz) => {
            const vencimientoQr = raiz.querySelector('[data-qr-vencimiento]');
            const contadorQr = raiz.querySelector('[data-qr-contador]');
            const botonNuevoQr = raiz.querySelector('[data-generar-nuevo-qr]');

            if (!vencimientoQr || !contadorQr) {
                return;
            }

            const fechaVencimiento = new Date(vencimientoQr.dataset.qrVencimiento);

            const actualizarContadorQr = () => {
                const milisegundosRestantes = fechaVencimiento.getTime() - Date.now();

                if (milisegundosRestantes <= 0) {
                    contadorQr.textContent = 'Código QR vencido';

                    if (botonNuevoQr) {
                        botonNuevoQr.disabled = false;
                        botonNuevoQr.title = 'Generar un código QR para reemplazar el vencido';
                    }

                    return false;
                }

                const totalSegundos = Math.floor(milisegundosRestantes / 1000);
                const horas = Math.floor(totalSegundos / 3600);
                const minutos = Math.floor((totalSegundos % 3600) / 60);
                const segundos = totalSegundos % 60;

                contadorQr.textContent = `${horas} h ${String(minutos).padStart(2, '0')} min ${String(segundos).padStart(2, '0')} s`;
                return true;
            };

            actualizarContadorQr();
            const intervaloQr = window.setInterval(() => {
                if (!raiz.isConnected || !actualizarContadorQr()) {
                    window.clearInterval(intervaloQr);
                }
            }, 1000);
        };

        const iniciarGeneracionQr = (raiz) => {
            const botonGenerarQr = raiz.querySelector('[data-auto-generar-qr]');
            const estadoGeneracion = raiz.querySelector('[data-qr-estado]');

            if (!botonGenerarQr || !estadoGeneracion) {
                return;
            }

            let generandoQr = false;

            const mostrarErrorGeneracion = (mensaje) => {
                estadoGeneracion.classList.add('is-error');
                estadoGeneracion.innerHTML = `
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
                    <strong>No se pudo generar el QR</strong>
                    <p></p>
                `;
                estadoGeneracion.querySelector('p').textContent = mensaje;
                botonGenerarQr.hidden = false;
                botonGenerarQr.disabled = false;
            };

            const generarQr = async () => {
                if (generandoQr) {
                    return;
                }

                generandoQr = true;
                botonGenerarQr.hidden = true;
                botonGenerarQr.disabled = true;

                try {
                    const respuesta = await fetch(botonGenerarQr.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                        },
                    });

                    const resultado = await respuesta.json();

                    if (!respuesta.ok || !resultado.finalizado) {
                        throw new Error(resultado.mensaje || 'OpenBCB no respondió correctamente.');
                    }

                    const contenidoPago = botonGenerarQr.closest('[data-contenido-pago]');
                    const urlModal = contenidoPago?.dataset.urlModal;

                    if (modalPago?.open && urlModal) {
                        await cargarContenidoModal(urlModal, false);
                    } else {
                        window.location.reload();
                    }
                } catch (error) {
                    generandoQr = false;
                    mostrarErrorGeneracion(error.message || 'Intenta nuevamente en unos momentos.');
                }
            };

            botonGenerarQr.addEventListener('click', generarQr);
            generarQr();
        };

        const iniciarNuevoQr = (raiz) => {
            const botonNuevoQr = raiz.querySelector('[data-generar-nuevo-qr]');

            if (!botonNuevoQr) {
                return;
            }

            botonNuevoQr.addEventListener('click', async () => {
                if (botonNuevoQr.disabled) {
                    return;
                }

                botonNuevoQr.disabled = true;

                try {
                    const respuesta = await fetch(botonNuevoQr.dataset.url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': @json(csrf_token()),
                            'Accept': 'application/json',
                        },
                    });
                    const resultado = await respuesta.json();

                    if (!respuesta.ok || !resultado.finalizado) {
                        throw new Error(resultado.mensaje || 'No se pudo generar un nuevo código QR.');
                    }

                    const contenidoPago = botonNuevoQr.closest('[data-contenido-pago]');
                    const urlModal = contenidoPago?.dataset.urlModal;

                    if (modalPago?.open && urlModal) {
                        await cargarContenidoModal(urlModal, false);
                    } else {
                        window.location.reload();
                    }
                } catch (error) {
                    botonNuevoQr.disabled = false;

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo generar el QR',
                            text: error.message,
                            confirmButtonColor: '#0f766e',
                        });
                    }
                }
            });
        };

        const iniciarCompartirQr = (raiz) => {
            const botonCompartir = raiz.querySelector('[data-compartir-qr]');

            if (!botonCompartir) {
                return;
            }

            botonCompartir.addEventListener('click', async () => {
                botonCompartir.disabled = true;

                try {
                    const respuestaImagen = await fetch(botonCompartir.dataset.url);

                    if (!respuestaImagen.ok) {
                        throw new Error('No se pudo preparar la imagen del QR.');
                    }

                    const imagenQr = await respuestaImagen.blob();
                    const archivoQr = new File(
                        [imagenQr],
                        botonCompartir.dataset.nombre,
                        { type: imagenQr.type || 'image/jpeg' }
                    );

                    if (navigator.share && navigator.canShare?.({ files: [archivoQr] })) {
                        await navigator.share({
                            title: 'Código QR del trámite',
                            text: 'Código QR para realizar el pago del trámite.',
                            files: [archivoQr],
                        });
                        return;
                    }

                    // En equipos sin menú para compartir, descarga la imagen como respaldo.
                    const enlaceDescarga = document.createElement('a');
                    enlaceDescarga.href = URL.createObjectURL(imagenQr);
                    enlaceDescarga.download = botonCompartir.dataset.nombre;
                    enlaceDescarga.click();
                    URL.revokeObjectURL(enlaceDescarga.href);

                    if (window.Swal) {
                        Swal.fire({
                            icon: 'info',
                            title: 'QR descargado',
                            text: 'Este navegador no permite compartir archivos directamente.',
                            confirmButtonColor: '#0f766e',
                        });
                    }
                } catch (error) {
                    if (error.name !== 'AbortError' && window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'No se pudo compartir el QR',
                            text: error.message,
                            confirmButtonColor: '#0f766e',
                        });
                    }
                } finally {
                    botonCompartir.disabled = false;
                }
            });
        };

        const inicializarContenidoPago = (raiz) => {
            iniciarContador(raiz);
            iniciarGeneracionQr(raiz);
            iniciarNuevoQr(raiz);
            iniciarCompartirQr(raiz);
        };

        document.addEventListener('click', (evento) => {
            const botonAbrir = evento.target.closest('[data-abrir-modal-pago]');

            if (botonAbrir) {
                evento.preventDefault();
                cargarContenidoModal(botonAbrir.dataset.url);
                return;
            }

            const botonCerrar = evento.target.closest('[data-cerrar-modal-pago]');

            if (!botonCerrar) {
                return;
            }

            const dialogo = botonCerrar.closest('dialog');

            if (dialogo) {
                dialogo.close();
                return;
            }

            window.location.href = botonCerrar.dataset.urlRetorno;
        });

        modalPago?.addEventListener('close', () => {
            contenidoModal.innerHTML = '';
        });

        document.addEventListener('DOMContentLoaded', () => {
            const contenidoDirecto = document.querySelector('.pago-modal-backdrop [data-contenido-pago]');

            if (contenidoDirecto) {
                inicializarContenidoPago(contenidoDirecto);
            }
        });
    })();
</script>
