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

            // Permite revisar o quitar el archivo antes de enviar la correccion al tecnico.
            vistaDetalleActiva.querySelectorAll('[data-correction-file-input]').forEach((inputArchivo) => {
                const control = inputArchivo.closest('[data-correction-file-control]');
                const nombreArchivo = control?.querySelector('[data-correction-file-name]');
                const estadoArchivo = control?.querySelector('[data-correction-file-status]');
                const botonVer = control?.querySelector('[data-correction-file-view]');
                const botonQuitar = control?.querySelector('[data-correction-file-clear]');
                let urlTemporal = null;

                if (!control || !nombreArchivo || !estadoArchivo || !botonVer || !botonQuitar) {
                    return;
                }

                const limpiarSeleccion = () => {
                    if (urlTemporal) {
                        URL.revokeObjectURL(urlTemporal);
                        urlTemporal = null;
                    }

                    inputArchivo.value = '';
                    nombreArchivo.textContent = nombreArchivo.dataset.emptyName || 'Sin archivo seleccionado';
                    estadoArchivo.textContent = estadoArchivo.dataset.emptyStatus || '';
                    botonVer.disabled = true;
                    botonQuitar.disabled = true;
                };

                inputArchivo.addEventListener('change', () => {
                    const archivo = inputArchivo.files?.[0];

                    if (!archivo) {
                        limpiarSeleccion();
                        return;
                    }

                    if (urlTemporal) {
                        URL.revokeObjectURL(urlTemporal);
                    }

                    urlTemporal = URL.createObjectURL(archivo);
                    nombreArchivo.textContent = archivo.name;
                    estadoArchivo.textContent = 'Archivo listo para enviar';
                    botonVer.disabled = false;
                    botonQuitar.disabled = false;
                });

                botonVer.addEventListener('click', () => {
                    if (!urlTemporal) {
                        return;
                    }

                    const ventana = window.open(urlTemporal, '_blank');
                    if (ventana) {
                        ventana.opener = null;
                    }
                });

                botonQuitar.addEventListener('click', limpiarSeleccion);
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

            // La mesa de revisión mantiene lista y detalle sincronizados sin alterar el formulario del servidor.
            document.querySelectorAll('[data-review-workbench]').forEach((mesa) => {
                const registros = Array.from(mesa.querySelectorAll('[data-review-record]'));
                const paneles = Array.from(mesa.querySelectorAll('[data-review-panel]'));
                const buscador = mesa.querySelector('[data-review-search]');
                const filtros = Array.from(mesa.querySelectorAll('[data-review-filter]'));
                const mensajeVacio = mesa.querySelector('[data-review-empty]');
                const claveSeleccion = mesa.dataset.reviewStorageKey;
                let filtroActivo = 'all';
                let requisitoActivo = null;

                const normalizar = (texto) => String(texto || '')
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .toLowerCase();

                function panelDe(id) {
                    return paneles.find((panel) => panel.dataset.reviewPanel === String(id));
                }

                function registroDe(id) {
                    return registros.find((registro) => registro.dataset.reviewRecord === String(id));
                }

                function seleccionarRequisito(id, desplazar = false) {
                    const registro = registroDe(id);
                    const panel = panelDe(id);

                    if (!registro || !panel) {
                        return;
                    }

                    requisitoActivo = String(id);
                    registros.forEach((item) => item.classList.toggle('is-active', item === registro));
                    paneles.forEach((item) => { item.hidden = item !== panel; });
                    pintarHistorialRequisito(id);

                    if (desplazar && window.matchMedia('(max-width: 900px)').matches) {
                        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                function estadoVisual(estado) {
                    if (estado === 'SI') {
                        return { texto: 'Cumple', clase: 'is-si', icono: 'fa-regular fa-circle-check' };
                    }

                    if (estado === 'NO') {
                        return { texto: 'No cumple', clase: 'is-no', icono: 'fa-regular fa-circle-xmark' };
                    }

                    return { texto: 'Pendiente', clase: 'is-pending', icono: 'fa-solid fa-circle' };
                }

                function pintarEstado(elemento, estado) {
                    if (!elemento) return;

                    const visual = estadoVisual(estado);
                    elemento.classList.remove('is-si', 'is-no', 'is-pending');
                    elemento.classList.add(visual.clase);
                    elemento.querySelector('span').textContent = visual.texto;
                    elemento.querySelector('i').className = visual.icono;
                }

                // El número resume el resultado sin recargar la lista con textos adicionales.
                function pintarNumeroEstado(elemento, estado) {
                    if (!elemento) return;

                    const clase = estado === 'SI' ? 'is-si' : (estado === 'NO' ? 'is-no' : 'is-pending');
                    elemento.classList.remove('is-si', 'is-no', 'is-pending');
                    elemento.classList.add(clase);
                }

                function actualizarResumen() {
                    const conteos = { all: registros.length, pending: 0, SI: 0, NO: 0 };

                    registros.forEach((registro) => {
                        const estado = registro.dataset.reviewState || 'pending';
                        conteos[estado] = (conteos[estado] || 0) + 1;
                    });

                    Object.entries(conteos).forEach(([estado, cantidad]) => {
                        const contador = mesa.querySelector(`[data-review-filter-count="${estado}"]`);
                        if (contador) contador.textContent = cantidad;
                    });

                    const revisados = conteos.SI + conteos.NO;
                    const porcentaje = registros.length ? Math.round((revisados / registros.length) * 100) : 0;
                    const textoRevisados = mesa.querySelector('[data-review-count-reviewed]');
                    const barra = mesa.querySelector('[data-review-progress]');
                    const progreso = barra?.parentElement;

                    if (textoRevisados) textoRevisados.textContent = revisados;
                    if (barra) barra.style.width = `${porcentaje}%`;
                    progreso?.setAttribute('aria-valuenow', String(porcentaje));
                }

                function aplicarFiltro() {
                    const termino = normalizar(buscador?.value);
                    let visibles = 0;

                    registros.forEach((registro) => {
                        const coincideEstado = filtroActivo === 'all' || registro.dataset.reviewState === filtroActivo;
                        const coincideTexto = !termino || normalizar(registro.dataset.reviewSearchText).includes(termino);
                        const visible = coincideEstado && coincideTexto;
                        registro.hidden = !visible;
                        if (visible) visibles++;
                    });

                    if (mensajeVacio) mensajeVacio.hidden = visibles > 0;

                    const activoVisible = registroDe(requisitoActivo);
                    if (!activoVisible || activoVisible.hidden) {
                        const siguiente = registros.find((registro) => !registro.hidden);
                        if (siguiente) seleccionarRequisito(siguiente.dataset.reviewRecord);
                    }
                }

                function cambiarDecision(registro, estado) {
                    const panel = panelDe(registro.dataset.reviewRecord);
                    const decision = registro.querySelector('[data-review-decision]');
                    const tocado = registro.querySelector('[data-review-touched]');
                    const observacion = panel?.querySelector('[data-review-observation]');
                    const textoObservacion = panel?.querySelector('[data-observation-input]');
                    const ayuda = panel?.querySelector('[data-review-decision-help]');

                    if (!panel || !decision || !tocado) return;

                    decision.value = estado;
                    tocado.value = '1';
                    registro.dataset.reviewState = estado;
                    panel.classList.toggle('is-observed', estado === 'NO');
                    panel.querySelectorAll('[data-review-choice]').forEach((opcion) => {
                        opcion.classList.toggle('is-selected', opcion.value === estado);
                    });

                    const pideMotivo = estado === 'NO';
                    if (observacion) observacion.hidden = !pideMotivo;
                    if (textoObservacion) {
                        textoObservacion.disabled = !pideMotivo;
                        textoObservacion.classList.remove('is-invalid');
                    }
                    if (ayuda) ayuda.textContent = pideMotivo
                        ? 'Indique qué debe corregir el solicitante.'
                        : 'El requisito quedará marcado como cumplido.';

                    pintarEstado(panel.querySelector('[data-review-detail-state]'), estado);
                    pintarNumeroEstado(registro.querySelector('[data-review-number-state]'), estado);
                    actualizarResumen();
                    aplicarFiltro();

                    if (pideMotivo) textoObservacion?.focus();
                }

                registros.forEach((registro) => {
                    registro.querySelector('[data-review-select]')?.addEventListener('click', () => {
                        seleccionarRequisito(registro.dataset.reviewRecord, true);
                    });
                });

                paneles.forEach((panel) => {
                    panel.querySelectorAll('[data-review-choice]').forEach((opcion) => {
                        opcion.addEventListener('click', () => {
                            const registro = registroDe(panel.dataset.reviewPanel);
                            if (registro) cambiarDecision(registro, opcion.value);
                        });
                    });

                    panel.querySelector('[data-observation-input]')?.addEventListener('input', (evento) => {
                        evento.currentTarget.classList.remove('is-invalid');
                    });
                });

                filtros.forEach((filtro) => {
                    filtro.addEventListener('click', () => {
                        filtroActivo = filtro.dataset.reviewFilter;
                        filtros.forEach((item) => item.classList.toggle('is-active', item === filtro));
                        aplicarFiltro();
                    });
                });

                buscador?.addEventListener('input', aplicarFiltro);

                mesa.addEventListener('submit', (evento) => {
                    const tocados = registros.filter((registro) => registro.querySelector('[data-review-touched]')?.value === '1');
                    const sinMotivo = tocados.find((registro) => {
                        const decision = registro.querySelector('[data-review-decision]')?.value;
                        const texto = panelDe(registro.dataset.reviewRecord)?.querySelector('[data-observation-input]');
                        return decision === 'NO' && !texto?.value.trim();
                    });

                    if (!tocados.length || sinMotivo) {
                        evento.preventDefault();

                        const mensaje = sinMotivo
                            ? 'Explique el motivo del requisito marcado como No cumple.'
                            : 'Revise al menos un requisito antes de guardar.';

                        if (sinMotivo) {
                            seleccionarRequisito(sinMotivo.dataset.reviewRecord, true);
                            const textarea = panelDe(sinMotivo.dataset.reviewRecord)?.querySelector('[data-observation-input]');
                            textarea?.classList.add('is-invalid');
                            textarea?.focus();
                        }

                        if (swalDisponible()) {
                            Swal.fire({ icon: 'warning', title: 'Revisión incompleta', text: mensaje, confirmButtonColor: '#059669' });
                        } else {
                            alert(mensaje);
                        }
                    }
                });

                mesa.querySelector('[data-review-save]')?.addEventListener('click', () => {
                    if (!claveSeleccion || !requisitoActivo) return;
                    try { sessionStorage.setItem(claveSeleccion, requisitoActivo); } catch (error) {}
                });

                mesa.querySelector('[data-review-save-next]')?.addEventListener('click', () => {
                    if (!claveSeleccion) return;
                    const posicion = registros.findIndex((registro) => registro.dataset.reviewRecord === requisitoActivo);
                    const ordenados = [...registros.slice(posicion + 1), ...registros.slice(0, posicion + 1)];
                    const siguiente = ordenados.find((registro) => registro.dataset.reviewState === 'pending');
                    if (!siguiente) return;
                    try { sessionStorage.setItem(claveSeleccion, siguiente.dataset.reviewRecord); } catch (error) {}
                });

                let seleccionGuardada = null;
                try {
                    seleccionGuardada = claveSeleccion ? sessionStorage.getItem(claveSeleccion) : null;
                    if (claveSeleccion) sessionStorage.removeItem(claveSeleccion);
                } catch (error) {}

                const inicial = registroDe(seleccionGuardada)
                    || registros.find((registro) => registro.dataset.reviewState === 'pending')
                    || registros[0];

                if (inicial) seleccionarRequisito(inicial.dataset.reviewRecord);
                actualizarResumen();
                aplicarFiltro();
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
            const formularioPago = document.querySelector('[data-payment-form]');
            const metodoPago = formularioPago?.querySelector('[data-payment-method]');
            const idPago = formularioPago?.querySelector('[data-payment-id]');
            const tituloModalPago = document.querySelector('[data-payment-modal-title]');
            const descripcionModalPago = document.querySelector('[data-payment-modal-description]');
            const ayudaPagoPdf = document.querySelector('[data-payment-pdf-help]');
            const etiquetaSubmitPago = document.querySelector('[data-payment-submit-label]');
            let urlTemporalPagoPdf = null;
            let urlComprobanteActual = formularioPago?.dataset.currentPdfUrl || null;

            if (urlComprobanteActual) {
                botonVerPagoPdf?.removeAttribute('disabled');
            }

            function limpiarPdfPagoTemporal() {
                if (urlTemporalPagoPdf) {
                    URL.revokeObjectURL(urlTemporalPagoPdf);
                    urlTemporalPagoPdf = null;
                }

                if (inputPagoPdf) {
                    inputPagoPdf.value = '';
                }

                if (nombrePagoPdf) {
                    nombrePagoPdf.textContent = urlComprobanteActual
                        ? 'Comprobante actual registrado'
                        : 'Sin PDF seleccionado';
                }

                if (urlComprobanteActual) {
                    botonVerPagoPdf?.removeAttribute('disabled');
                } else {
                    botonVerPagoPdf?.setAttribute('disabled', 'disabled');
                }
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
                const url = urlTemporalPagoPdf || urlComprobanteActual;
                if (url) {
                    window.open(url, '_blank');
                }
            });

            botonQuitarPagoPdf?.addEventListener('click', limpiarPdfPagoTemporal);

            // Prepara el mismo modal con los datos del pago seleccionado.
            document.querySelectorAll('[data-edit-payment]').forEach((boton) => {
                boton.addEventListener('click', () => {
                    if (!formularioPago) return;

                    formularioPago.action = boton.dataset.updateUrl;
                    metodoPago.name = '_method';
                    metodoPago.value = 'PUT';
                    idPago.value = boton.dataset.paymentId;
                    formularioPago.querySelector('[name="form_id_procedencia_pago"]').value = boton.dataset.procedencia || '';
                    formularioPago.querySelector('[name="form_tipo_pago"]').value = boton.dataset.tipo || '';
                    formularioPago.querySelector('[name="form_fecha_pago"]').value = boton.dataset.fecha || '';
                    formularioPago.querySelector('[name="form_monto_pago"]').value = boton.dataset.monto || '';
                    formularioPago.querySelector('[name="form_factura_pago"]').value = boton.dataset.factura || '';

                    urlComprobanteActual = boton.dataset.comprobante || null;
                    limpiarPdfPagoTemporal();
                    nombrePagoPdf.textContent = urlComprobanteActual
                        ? 'Comprobante actual registrado'
                        : 'Sin PDF registrado';
                    ayudaPagoPdf.textContent = 'Seleccione otro PDF solo para reemplazarlo';
                    tituloModalPago.textContent = 'Editar pago';
                    descripcionModalPago.textContent = 'Corrija los datos del pago relacionado a este trámite.';
                    etiquetaSubmitPago.textContent = 'Actualizar pago';
                    abrirModalPago();
                });
            });

            const modalDestinoCorreccion = document.querySelector('[data-correction-recipient-modal]');

            function abrirModalDestinoCorreccion() {
                if (!modalDestinoCorreccion) return;

                modalDestinoCorreccion.classList.add('is-open');
                modalDestinoCorreccion.setAttribute('aria-hidden', 'false');
            }

            function cerrarModalDestinoCorreccion() {
                if (!modalDestinoCorreccion) return;

                modalDestinoCorreccion.classList.remove('is-open');
                modalDestinoCorreccion.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('[data-open-correction-recipient-modal]').forEach((boton) => {
                boton.addEventListener('click', abrirModalDestinoCorreccion);
            });

            document.querySelectorAll('[data-close-correction-recipient-modal]').forEach((boton) => {
                boton.addEventListener('click', cerrarModalDestinoCorreccion);
            });

            document.querySelectorAll('[data-correction-recipient-selector]').forEach((selector) => {
                const input = selector.querySelector('[data-correction-recipient-value]');
                const boton = selector.querySelector('[data-correction-recipient-toggle]');
                const menu = selector.querySelector('[data-correction-recipient-menu]');
                const buscador = selector.querySelector('[data-correction-recipient-search]');
                const nombre = selector.querySelector('[data-correction-recipient-name]');
                const tipo = selector.querySelector('[data-correction-recipient-type]');
                const opciones = Array.from(selector.querySelectorAll('[data-correction-recipient-option]'));
                const mensajeVacio = selector.querySelector('[data-correction-recipient-empty]');

                if (!input || !boton || !menu || !buscador) return;

                const cerrarMenu = () => {
                    menu.hidden = true;
                    boton.setAttribute('aria-expanded', 'false');
                };

                boton.addEventListener('click', () => {
                    menu.hidden = !menu.hidden;
                    boton.setAttribute('aria-expanded', String(!menu.hidden));

                    if (!menu.hidden) {
                        buscador.focus();
                    }
                });

                opciones.forEach((opcion) => {
                    opcion.addEventListener('click', () => {
                        input.value = opcion.dataset.value || '';
                        nombre.textContent = opcion.dataset.nombre || '';
                        tipo.textContent = opcion.dataset.tipo || '';
                        cerrarMenu();
                    });
                });

                buscador.addEventListener('input', () => {
                    const criterio = buscador.value.trim().toLocaleLowerCase();
                    let visibles = 0;

                    opciones.forEach((opcion) => {
                        const mostrar = (opcion.dataset.busqueda || '').includes(criterio);
                        opcion.hidden = !mostrar;
                        visibles += mostrar ? 1 : 0;
                    });

                    mensajeVacio?.classList.toggle('is-hidden', visibles > 0);
                });
            });
        });
    </script>

