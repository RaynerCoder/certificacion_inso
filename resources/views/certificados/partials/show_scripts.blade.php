{{-- Scripts del detalle del tramite: agrupa interacciones de requisitos, correcciones y pagos. --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Historial lateral de requisitos: se pinta desde datos ya cargados por Laravel.
            const historialRequisitos = @json($historialRequisitos);
            const vistaDetalleActiva = document.querySelector('[data-tramite-detail-active]') || document;
            const tituloHistorial = vistaDetalleActiva.querySelector('[data-requirement-history-title]');
            const listaHistorial = vistaDetalleActiva.querySelector('[data-requirement-history-list]');
            const botonesHistorial = vistaDetalleActiva.querySelectorAll('[data-requirement-history-button]');
            const swalDisponible = () => Boolean(window.Swal);

            // Muestra el archivo que el solicitante acaba de seleccionar antes de devolver la correccion.
            vistaDetalleActiva.querySelectorAll('[data-correction-file-input]').forEach((input) => {
                input.addEventListener('change', () => {
                    const archivo = input.files && input.files[0];
                    const contenedor = input.closest('td')?.querySelector('[data-correction-file-preview]');
                    const nombre = contenedor?.querySelector('[data-correction-file-name]');
                    const enlace = contenedor?.querySelector('[data-correction-file-link]');

                    if (!contenedor || !nombre || !enlace) {
                        return;
                    }

                    if (!archivo) {
                        contenedor.hidden = true;
                        nombre.textContent = '';
                        enlace.removeAttribute('href');
                        return;
                    }

                    nombre.textContent = archivo.name;
                    enlace.href = URL.createObjectURL(archivo);
                    contenedor.hidden = false;
                });
            });

            // Selector visual de funcionario: busca por nombre/cargo/area y sincroniza el select real del formulario.
            vistaDetalleActiva.querySelectorAll('[data-technical-selector]').forEach((selector) => {
                const selectReal = selector.querySelector('[data-technical-native]');
                const botonAbrir = selector.querySelector('[data-technical-toggle]');
                const menu = selector.querySelector('[data-technical-menu]');
                const buscador = selector.querySelector('[data-technical-search]');
                const opciones = Array.from(selector.querySelectorAll('[data-technical-option]'));
                const mensajeVacio = selector.querySelector('[data-technical-empty]');
                const textoNombre = selector.querySelector('[data-technical-label]');
                const textoAyuda = selector.querySelector('[data-technical-help]');
                const chipCarga = selector.querySelector('[data-technical-chip]');

                if (!selectReal || !botonAbrir || !menu || !buscador) {
                    return;
                }

                function cerrarMenu() {
                    menu.hidden = true;
                    botonAbrir.classList.remove('is-open');
                    botonAbrir.setAttribute('aria-expanded', 'false');
                }

                function abrirMenu() {
                    menu.hidden = false;
                    botonAbrir.classList.add('is-open');
                    botonAbrir.setAttribute('aria-expanded', 'true');
                    buscador.focus();
                    buscador.select();
                }

                function pintarSeleccion(opcion) {
                    if (!opcion) {
                        textoNombre.textContent = botonAbrir.dataset.placeholder || 'Seleccione funcionario';
                        textoAyuda.textContent = botonAbrir.dataset.help || 'Busque por nombre, cargo o area';
                        chipCarga.textContent = '';
                        chipCarga.classList.add('is-hidden');
                        opciones.forEach((item) => item.classList.remove('is-selected'));
                        return;
                    }

                    textoNombre.textContent = opcion.dataset.label || 'Funcionario seleccionado';
                    textoAyuda.textContent = opcion.dataset.help || '';
                    chipCarga.textContent = opcion.dataset.chip || '';
                    chipCarga.classList.toggle('is-hidden', !opcion.dataset.chip);
                    opciones.forEach((item) => item.classList.toggle('is-selected', item === opcion));
                }

                function seleccionarOpcion(opcion) {
                    selectReal.value = opcion.dataset.value || '';
                    selectReal.dispatchEvent(new Event('change', { bubbles: true }));
                    pintarSeleccion(opcion);
                    cerrarMenu();
                }

                function filtrarOpciones() {
                    const termino = buscador.value.trim().toLowerCase();
                    let visibles = 0;

                    opciones.forEach((opcion) => {
                        const coincide = !termino || (opcion.dataset.search || '').includes(termino);
                        opcion.classList.toggle('is-hidden', !coincide);
                        if (coincide) {
                            visibles++;
                        }
                    });

                    mensajeVacio?.classList.toggle('is-hidden', visibles > 0);
                }

                botonAbrir.addEventListener('click', () => {
                    if (menu.hidden) {
                        abrirMenu();
                    } else {
                        cerrarMenu();
                    }
                });

                buscador.addEventListener('input', filtrarOpciones);

                opciones.forEach((opcion) => {
                    opcion.addEventListener('click', () => seleccionarOpcion(opcion));
                });

                document.addEventListener('click', (event) => {
                    if (!selector.contains(event.target)) {
                        cerrarMenu();
                    }
                });

                selector.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        cerrarMenu();
                        botonAbrir.focus();
                    }
                });

                pintarSeleccion(opciones.find((opcion) => opcion.dataset.value === selectReal.value));
            });

            // Correccion del solicitante: muestra el nombre del PDF elegido sin enviarlo todavia.
            vistaDetalleActiva.querySelectorAll('[data-correction-file-input]').forEach((inputArchivo) => {
                const contenedor = inputArchivo.closest('.cert-correction-document-cell');
                const vistaPrevia = contenedor?.querySelector('[data-correction-file-preview]');
                const nombreArchivo = contenedor?.querySelector('[data-correction-file-name]');
                const botonQuitar = contenedor?.querySelector('[data-correction-file-clear]');

                if (!contenedor || !vistaPrevia || !nombreArchivo || !botonQuitar) {
                    return;
                }

                inputArchivo.addEventListener('change', () => {
                    const archivo = inputArchivo.files?.[0];

                    if (!archivo) {
                        vistaPrevia.classList.remove('is-visible');
                        nombreArchivo.textContent = '';
                        return;
                    }

                    nombreArchivo.textContent = `Nuevo archivo seleccionado: ${archivo.name}`;
                    vistaPrevia.classList.add('is-visible');
                });

                botonQuitar.addEventListener('click', () => {
                    inputArchivo.value = '';
                    nombreArchivo.textContent = '';
                    vistaPrevia.classList.remove('is-visible');
                });
            });

            // Correccion presencial: confirma antes de devolver el tramite al revisor tecnico.
            vistaDetalleActiva.querySelectorAll('[data-confirm-received-correction]').forEach((formulario) => {
                formulario.addEventListener('submit', async (evento) => {
                    if (formulario.dataset.enviado === '1') {
                        return;
                    }

                    evento.preventDefault();

                    if (!swalDisponible()) {
                        if (confirm('Se registrara la correccion presencial y el tramite volvera al revisor tecnico. ¿Desea continuar?')) {
                            formulario.dataset.enviado = '1';
                            formulario.submit();
                        }

                        return;
                    }

                    const respuesta = await Swal.fire({
                        title: 'Registrar correccion recibida',
                        text: 'El tramite volvera al revisor tecnico para una nueva evaluacion.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Si, registrar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#64748b',
                    });

                    if (respuesta.isConfirmed) {
                        formulario.dataset.enviado = '1';
                        formulario.submit();
                    }
                });
            });

            function escaparHtml(valor) {
                return String(valor ?? '')
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }

            function obtenerTituloRequisito(fila) {
                if (fila?.dataset?.requirementTitle) {
                    return fila.dataset.requirementTitle;
                }

                const celda = fila?.querySelector('[data-requirement-title-cell]') || fila?.querySelector('td:nth-child(2)');

                if (!celda) {
                    return 'Requisito seleccionado';
                }

                const copia = celda.cloneNode(true);
                copia.querySelectorAll('input, button, select, textarea, label').forEach((elemento) => elemento.remove());

                return copia.textContent.replace(/\s+/g, ' ').trim() || 'Requisito seleccionado';
            }

            function contenidoModalRequisito(fila, textoAyuda) {
                const requisito = escaparHtml(obtenerTituloRequisito(fila));

                return `
                    <div class="cert-swal-requirement">
                        <p>${escaparHtml(textoAyuda)}</p>
                        <p class="cert-swal-requirement-line">
                            <span>Requisito a revisar:</span>
                            <strong>${requisito}</strong>
                        </p>
                    </div>
                `;
            }

            function pintarHistorialRequisito(idRequisito) {
                const historial = historialRequisitos[idRequisito];

                botonesHistorial.forEach((boton) => {
                    boton.classList.toggle('is-active', boton.dataset.requirementId === String(idRequisito));
                });

                if (!tituloHistorial || !listaHistorial) {
                    return;
                }

                if (!historial) {
                    tituloHistorial.textContent = 'Sin historial';
                    listaHistorial.innerHTML = '<div class="cert-history-empty">Sin movimientos</div>';
                    return;
                }

                tituloHistorial.textContent = `Requisito: ${historial.titulo}`;
                listaHistorial.innerHTML = historial.items.map((item) => {
                    const cargo = item.cargo
                        ? `<div class="cert-history-item-cargo">${escaparHtml(item.cargo)}</div>`
                        : '';

                    return `
                        <article class="tramite-history-item is-${escaparHtml(item.estado)}">
                            <div class="tramite-history-title">${escaparHtml(item.tipo)}</div>
                            <div class="cert-history-item-meta">${escaparHtml(item.fecha)}</div>
                            <div class="cert-history-item-user">${escaparHtml(item.usuario)}</div>
                            ${cargo}
                            <div class="tramite-history-text">${escaparHtml(item.texto)}</div>
                        </article>
                    `;
                }).join('');
            }

            botonesHistorial.forEach((boton) => {
                boton.addEventListener('click', () => pintarHistorialRequisito(boton.dataset.requirementId));
            });

            if (botonesHistorial.length) {
                pintarHistorialRequisito(botonesHistorial[0].dataset.requirementId);
            }

            function marcarFilaTocada(fila, tocada = true) {
                const inputTocado = fila.querySelector('[data-review-touched]');

                if (inputTocado) {
                    inputTocado.value = tocada ? '1' : '0';
                }
            }

            function limpiarObservacion(fila) {
                const input = fila.querySelector('[data-observation-input]');
                const caja = fila.querySelector('[data-observation-box]');
                const texto = fila.querySelector('[data-observation-text]');
                const visual = fila.querySelector('[data-observation-display]');

                if (input) {
                    input.value = '';
                }

                if (texto) {
                    texto.textContent = '';
                }

                caja?.classList.remove('is-visible');

                if (visual) {
                    visual.textContent = 'Sin observación';
                    visual.classList.remove('is-danger');
                }
            }

            function mostrarObservacion(fila, observacion) {
                const input = fila.querySelector('[data-observation-input]');
                const caja = fila.querySelector('[data-observation-box]');
                const texto = fila.querySelector('[data-observation-text]');
                const visual = fila.querySelector('[data-observation-display]');

                if (input) {
                    input.value = observacion;
                }

                if (texto) {
                    texto.textContent = observacion;
                }

                caja?.classList.add('is-visible');

                if (visual) {
                    visual.textContent = observacion || 'Sin observación';
                    visual.classList.toggle('is-danger', Boolean(observacion));
                }
            }

            async function confirmarCumple(radio, fila) {
                if (!swalDisponible()) {
                    if (!confirm('¿Confirma que este requisito cumple?')) {
                        radio.checked = false;
                        return;
                    }

                    marcarFilaTocada(fila);
                    limpiarObservacion(fila);
                    return;
                }

                const respuesta = await Swal.fire({
                    title: '¿Confirmar cumplimiento?',
                    html: contenidoModalRequisito(fila, 'Confirme solo si reviso el requisito y el documento cumple correctamente.'),
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, cumple',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#059669',
                    cancelButtonColor: '#64748b',
                });

                if (!respuesta.isConfirmed) {
                    radio.checked = false;
                    return;
                }

                marcarFilaTocada(fila);
                limpiarObservacion(fila);
            }

            async function pedirObservacion(radio, fila, valorActual = '', limpiarSiCancela = true) {
                if (!swalDisponible()) {
                    const observacion = prompt('Explique por qué este requisito no cumple:', valorActual);

                    if (!observacion || !observacion.trim()) {
                        if (limpiarSiCancela) {
                            radio.checked = false;
                        }
                        return;
                    }

                    mostrarObservacion(fila, observacion.trim());
                    marcarFilaTocada(fila);
                    return;
                }

                const respuesta = await Swal.fire({
                    title: 'Registrar observación',
                    html: contenidoModalRequisito(fila, 'Explique con claridad que debe corregir el solicitante.'),
                    input: 'textarea',
                    inputValue: valorActual,
                    inputPlaceholder: 'Detalle la observación técnica del requisito...',
                    inputAttributes: {
                        maxlength: 1000,
                    },
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar observación',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return 'Debe registrar la observación para marcar No cumple.';
                        }

                        return undefined;
                    },
                });

                if (!respuesta.isConfirmed) {
                    if (limpiarSiCancela) {
                        radio.checked = false;
                    }
                    return;
                }

                const observacion = respuesta.value.trim();

                const confirmacion = await Swal.fire({
                    title: '¿Guardar esta observación?',
                    html: contenidoModalRequisito(fila, 'Revise la observacion antes de guardarla. Luego podra modificarla con el boton Editar antes de guardar la revision.'),
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, guardar',
                    cancelButtonText: 'Volver',
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                });

                if (!confirmacion.isConfirmed) {
                    if (limpiarSiCancela) {
                        radio.checked = false;
                    }
                    return;
                }

                mostrarObservacion(fila, observacion);
                marcarFilaTocada(fila);
            }

            function actualizarEstadoRequisito(fila, estado, observacion = null) {
                const decision = fila.querySelector('[data-review-decision]');
                const enlaceDocumento = fila.querySelector('[data-document-link]');
                const textoDocumento = fila.querySelector('[data-document-text]');
                const textoDocumentoNormal = enlaceDocumento?.dataset.documentDefault || 'Ver evidencia';
                const textoDocumentoObservado = enlaceDocumento?.dataset.documentObserved || 'Evidencia observada';

                if (decision) {
                    decision.value = estado;
                }

                fila.querySelectorAll('[data-review-check-option]').forEach((check) => {
                    check.checked = check.value === estado;
                    check.closest('.tramite-check-option')?.classList.toggle('is-selected', check.checked);
                });

                marcarFilaTocada(fila, Boolean(estado));

                if (estado === 'SI') {
                    enlaceDocumento?.classList.remove('tramite-pill-danger');
                    fila.classList.remove('tramite-row-observed');

                    if (textoDocumento) {
                        textoDocumento.textContent = textoDocumentoNormal;
                    }

                    limpiarObservacion(fila);
                    return;
                }

                if (estado === 'NO') {
                    enlaceDocumento?.classList.add('tramite-pill-danger');
                    fila.classList.add('tramite-row-observed');

                    if (textoDocumento) {
                        textoDocumento.textContent = textoDocumentoObservado;
                    }

                    if (observacion !== null) {
                        mostrarObservacion(fila, observacion);
                    }

                    return;
                }

                enlaceDocumento?.classList.remove('tramite-pill-danger');
                fila.classList.remove('tramite-row-observed');

                if (textoDocumento) {
                    textoDocumento.textContent = textoDocumentoNormal;
                }
            }

            document.querySelectorAll('[data-review-check-option]').forEach((casilla) => {
                casilla.addEventListener('change', async () => {
                    const fila = casilla.closest('tr');

                    if (!fila) {
                        return;
                    }

                    const decisionAnterior = fila.querySelector('[data-review-decision]')?.value || '';
                    const observacionActual = fila.querySelector('[data-observation-input]')?.value || '';

                    if (!casilla.checked) {
                        actualizarEstadoRequisito(fila, decisionAnterior);
                        return;
                    }

                    if (!swalDisponible()) {
                        if (casilla.value === 'SI') {
                            if (confirm('¿Confirma que este requisito cumple?')) {
                                actualizarEstadoRequisito(fila, 'SI');
                                return;
                            }
                        } else {
                            const observacion = prompt('Explique por qué este requisito no cumple:', observacionActual);

                            if (observacion && observacion.trim()) {
                                actualizarEstadoRequisito(fila, 'NO', observacion.trim());
                                return;
                            }
                        }

                        actualizarEstadoRequisito(fila, decisionAnterior);
                        return;
                    }

                    if (casilla.value === 'SI') {
                        const respuesta = await Swal.fire({
                            title: '¿Confirmar cumplimiento?',
                            html: contenidoModalRequisito(fila, 'Confirme solo si reviso el requisito y el documento cumple correctamente.'),
                            icon: 'success',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, cumple',
                            cancelButtonText: 'Cancelar',
                            confirmButtonColor: '#059669',
                            cancelButtonColor: '#64748b',
                        });

                        if (respuesta.isConfirmed) {
                            actualizarEstadoRequisito(fila, 'SI');
                            return;
                        }

                        actualizarEstadoRequisito(fila, decisionAnterior);
                        return;
                    }

                    const respuesta = await Swal.fire({
                        title: '¿Confirmar No cumple?',
                        html: contenidoModalRequisito(fila, 'Debe registrar la observacion que vera el solicitante.'),
                        input: 'textarea',
                        inputValue: observacionActual,
                        inputPlaceholder: 'Detalle por qué el requisito no cumple...',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Guardar observación',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#64748b',
                        inputValidator: (value) => {
                            if (!value || !value.trim()) {
                                return 'Debe registrar la observación para marcar NO.';
                            }

                            return undefined;
                        },
                    });

                    if (respuesta.isConfirmed) {
                        actualizarEstadoRequisito(fila, 'NO', respuesta.value.trim());
                        return;
                    }

                    actualizarEstadoRequisito(fila, decisionAnterior);
                });
            });

            document.querySelectorAll('[data-edit-observation]').forEach((boton) => {
                boton.addEventListener('click', async () => {
                    const fila = boton.closest('tr');
                    const checkNo = fila?.querySelector('[data-review-check-option][value="NO"]');
                    const observacionActual = fila?.querySelector('[data-observation-input]')?.value || '';

                    if (!fila || !checkNo) {
                        return;
                    }

                    checkNo.checked = true;
                    await pedirObservacion(checkNo, fila, observacionActual, false);
                });
            });

            const modalPago = document.querySelector('[data-payment-modal]');

            function abrirModalPago() {
                if (!modalPago) return;

                modalPago.classList.add('is-open');
                modalPago.setAttribute('aria-hidden', 'false');
            }

            function cerrarModalPago() {
                if (!modalPago) return;

                modalPago.classList.remove('is-open');
                modalPago.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('[data-open-payment-modal]').forEach((boton) => {
                boton.addEventListener('click', abrirModalPago);
            });

            document.querySelectorAll('[data-close-payment-modal]').forEach((boton) => {
                boton.addEventListener('click', cerrarModalPago);
            });

            document.addEventListener('keydown', (evento) => {
                if (evento.key === 'Escape') {
                    cerrarModalPago();
                }
            });

            if (modalPago?.dataset.openOnError === '1') {
                abrirModalPago();
            }

            // Vista previa local del comprobante de pago antes de registrar.
            const inputPagoPdf = document.querySelector('[data-payment-pdf-input]');
            const nombrePagoPdf = document.querySelector('[data-payment-pdf-name]');
            const botonSeleccionarPagoPdf = document.querySelector('[data-payment-pdf-select]');
            const botonVerPagoPdf = document.querySelector('[data-payment-pdf-view]');
            const botonQuitarPagoPdf = document.querySelector('[data-payment-pdf-remove]');
            let urlTemporalPagoPdf = null;

            function limpiarPdfPagoTemporal() {
                if (urlTemporalPagoPdf) {
                    URL.revokeObjectURL(urlTemporalPagoPdf);
                    urlTemporalPagoPdf = null;
                }

                if (inputPagoPdf) {
                    inputPagoPdf.value = '';
                }

                if (nombrePagoPdf) {
                    nombrePagoPdf.textContent = 'Sin PDF seleccionado';
                }

                botonVerPagoPdf?.setAttribute('disabled', 'disabled');
                botonQuitarPagoPdf?.setAttribute('disabled', 'disabled');
            }

            botonSeleccionarPagoPdf?.addEventListener('click', () => inputPagoPdf?.click());

            inputPagoPdf?.addEventListener('change', () => {
                const archivo = inputPagoPdf.files?.[0];

                if (!archivo) {
                    limpiarPdfPagoTemporal();
                    return;
                }

                if (archivo.type !== 'application/pdf') {
                    limpiarPdfPagoTemporal();
                    Swal.fire({
                        title: 'Archivo no válido',
                        text: 'Seleccione un comprobante en formato PDF.',
                        icon: 'warning',
                        confirmButtonColor: '#059669',
                    });
                    return;
                }

                if (urlTemporalPagoPdf) {
                    URL.revokeObjectURL(urlTemporalPagoPdf);
                }

                urlTemporalPagoPdf = URL.createObjectURL(archivo);
                if (nombrePagoPdf) {
                    nombrePagoPdf.textContent = archivo.name;
                }

                botonVerPagoPdf?.removeAttribute('disabled');
                botonQuitarPagoPdf?.removeAttribute('disabled');
            });

            botonVerPagoPdf?.addEventListener('click', () => {
                if (urlTemporalPagoPdf) {
                    window.open(urlTemporalPagoPdf, '_blank');
                }
            });

            botonQuitarPagoPdf?.addEventListener('click', limpiarPdfPagoTemporal);
        });
    </script>

