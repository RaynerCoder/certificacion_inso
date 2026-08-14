<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Requisitos disponibles por tipo de tramite; se cargan desde el controlador.
        const requisitosPorTipo = @json($requisitosPorTipoCertificado);
        const tiposCertificadosTramite = @json($tiposCertificadosSelect);
        const dependenciasPorTipo = @json($dependenciasPorTipoCertificado ?? []);
        const certificadosVigentesPorPersona = @json($certificadosVigentesPorPersona ?? []);
        const personasTramite = @json($personasSelect);
        const tramitadoresPorBeneficiario = @json($tramitadoresPorBeneficiario);
        const erroresLaravel = @json($errors->getBag('default')->getMessages());
        const tramitadorSeleccionadoServidor = @json((string) ($tramitadorSeleccionado ?? ''));
        const tipoSeleccionadoServidor = @json((string) ($tipoSeleccionado ?? ''));
        const requisitosAnteriores = Object.values(@json(old('requisitos_certificados', [])) || {});
        const longitudMaximaEvidenciaTexto = @json($longitudMaximaEvidenciaTexto ?? 50);

        // Campos principales del inicio de tramite.
        const tipoSelect = document.getElementById('form_id_tipo_certificado');
        const beneficiarioSelect = document.getElementById('form_id_persona_beneficiario');
        const tramitadorSelect = document.getElementById('form_id_persona_tramitador');
        const selectorBeneficiario = document.querySelector('[data-tramite-select="beneficiario"]');
        const selectorTramitador = document.querySelector('[data-tramite-select="tramitador"]');
        const selectorTipoCertificado = document.querySelector('[data-tramite-select="tipo-certificado"]');
        const mismoBeneficiario = document.getElementById('mismoBeneficiario');
        const contenedorRequisitos = document.getElementById('contenedorDocumentosTramite');
        const progresoTexto = document.getElementById('requisitosProgresoTexto');
        const progresoBarra = document.getElementById('requisitosProgresoBar');
        const progresoControl = progresoBarra?.closest('[role="progressbar"]');
        const resumenDocumentos = document.getElementById('resumenDocumentosTramite');
        const resumenDocumentosTexto = document.getElementById('resumenDocumentosTexto');
        const resumenDocumentosBarra = document.getElementById('resumenDocumentosBarra');
        let idBeneficiarioAnterior = '';

        // La validacion final queda en Laravel. Aqui solo evitamos que el navegador bloquee filas creadas por JS.
        const formTramite = document.getElementById('formIniciarTramite');
        if (formTramite) {
            formTramite.noValidate = true;

            formTramite.addEventListener('submit', (evento) => {
                if (formTramite.dataset.enviando === '1') {
                    evento.preventDefault();
                    evento.stopImmediatePropagation();
                    return;
                }

                if (!validarArchivosAntesDeEnviar() || !validarTextosAntesDeEnviar()) {
                    evento.preventDefault();
                    evento.stopImmediatePropagation();
                    return;
                }

                // El envio asincrono evita recargar la pagina cuando Laravel encuentra un error.
                // De esta manera el navegador conserva los archivos que ya fueron seleccionados.
                evento.preventDefault();
                limpiarErroresServidor();
                enviarTramiteSinRecargar(evento.submitter);
            }, true);
        }

        async function enviarTramiteSinRecargar(botonEnviado) {
            const datos = new FormData(formTramite);

            if (botonEnviado?.name) {
                datos.set(botonEnviado.name, botonEnviado.value);
            }

            try {
                const respuesta = await fetch(formTramite.action, {
                    method: 'POST',
                    body: datos,
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (respuesta.status === 422) {
                    const resultado = await respuesta.json();
                    restaurarFormularioTrasError();
                    mostrarErroresServidor(resultado.errors || { formulario: [resultado.message] });
                    return;
                }

                if (!respuesta.ok) {
                    restaurarFormularioTrasError();
                    mostrarErrorGeneral('No se pudo enviar el trámite. Revise la información e intente nuevamente.');
                    return;
                }

                const resultado = await respuesta.json();

                // Laravel entrega el destino únicamente cuando el trámite terminó correctamente.
                window.location.assign(resultado.redirect);
            } catch (error) {
                restaurarFormularioTrasError();
                mostrarErrorGeneral('No se pudo conectar con el servidor. Sus documentos continúan seleccionados.');
            }
        }

        function restaurarFormularioTrasError() {
            delete formTramite.dataset.enviando;

            formTramite.querySelectorAll('button[type="submit"], input[type="submit"]').forEach((boton) => {
                boton.disabled = false;
                boton.classList.remove('opacity-75', 'cursor-not-allowed');

                if (!boton.dataset.textoOriginal) {
                    return;
                }

                if (boton.tagName === 'BUTTON') {
                    boton.innerHTML = boton.dataset.textoOriginal;
                } else {
                    boton.value = boton.dataset.textoOriginal;
                }
            });

            window.Swal?.close();
        }

        function nombreInputDesdeError(campo) {
            const partes = String(campo).split('.');
            const nombreBase = partes.shift();

            return nombreBase + partes.map((parte) => `[${parte}]`).join('');
        }

        function mostrarErroresServidor(errores) {
            const mensajes = [];
            let primerCampo = null;

            Object.entries(errores || {}).forEach(([campo, listaMensajes]) => {
                const mensaje = Array.isArray(listaMensajes) ? listaMensajes[0] : String(listaMensajes || '');
                const nombreInput = nombreInputDesdeError(campo);
                const input = Array.from(formTramite.elements)
                    .find((elemento) => elemento.name === nombreInput);

                if (mensaje) {
                    mensajes.push(mensaje);
                }

                if (!input) {
                    return;
                }

                primerCampo ||= input;

                if (input.classList.contains('tramite-pdf-input')) {
                    mostrarErrorArchivoCliente(input, mensaje);
                    return;
                }

                if (input.classList.contains('tramite-texto-input')) {
                    mostrarErrorTextoCliente(input, mensaje);
                    return;
                }

                input.classList.add('is-invalid');
                const contenedor = input.closest('.tramite-inicio-field') || input.parentElement;
                if (contenedor) {
                    const error = document.createElement('p');
                    error.dataset.errorServidor = '1';
                    error.className = 'mt-2 text-sm text-red-600';
                    error.textContent = mensaje;
                    contenedor.appendChild(error);
                }
            });

            if (primerCampo) {
                const destino = primerCampo.closest('.tramite-requisito-item, .tramite-inicio-field') || primerCampo;
                destino.scrollIntoView({ behavior: 'smooth', block: 'center' });
                primerCampo.focus({ preventScroll: true });
            }

            mostrarErrorGeneral(mensajes[0] || 'Revise los campos señalados antes de volver a enviar.');
        }

        function limpiarErroresServidor() {
            formTramite.querySelectorAll('[data-error-servidor]').forEach((error) => error.remove());
            formTramite.querySelectorAll('.tramite-inicio-field .is-invalid').forEach((campo) => {
                campo.classList.remove('is-invalid');
            });
        }

        function mostrarErrorGeneral(mensaje) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Revise el formulario',
                    text: mensaje,
                    confirmButtonText: 'Entendido',
                });
                return;
            }

            window.alert(mensaje);
        }

        // Escapa texto antes de insertarlo en HTML generado.
        function escaparHtml(valor) {
            return String(valor ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Devuelve el primer mensaje que Laravel envio para un campo.
        function errorLaravel(campo) {
            return erroresLaravel[campo]?.[0] || '';
        }

        // Recupera el texto escrito si Laravel devolvio el formulario con errores.
        function valorTextoAnterior(requisito, index) {
            const idAsignacion = String(requisito.id_requisito_tipo_certificado || '');
            const anterior = requisitosAnteriores.find((item) => (
                String(item?.id_requisito_tipo_certificado || '') === idAsignacion
            )) || requisitosAnteriores[index] || {};

            return String(anterior.valor_texto || '');
        }

        // Acorta nombres largos para mantener alineado el control de archivo.
        function nombreArchivoCorto(nombre) {
            if (!nombre) {
                return 'Sin archivo seleccionado';
            }

            return nombre.length > 30 ? `${nombre.slice(0, 24)}...` : nombre;
        }

        // Mensaje breve para requisitos que no piden archivo al iniciar.
        function textoEvidenciaTramite(codigoEvidencia) {
            const codigo = String(codigoEvidencia || '').toUpperCase();

            if (codigo.includes('CERTIFICADO')) {
                return 'El sistema verificará el certificado vigente durante la revisión del trámite.';
            }

            if (codigo === 'PAGO') {
                return 'El pago registrado será validado durante la revisión del trámite.';
            }

            if (codigo === 'PRESENCIAL') {
                return 'El personal del INSO realizará la validación presencial del requisito.';
            }

            if (codigo === 'TRAMITADOR') {
                return 'La habilitación del tramitador será revisada por el técnico.';
            }

            if (codigo === 'TEXTO') {
                return 'Dato pendiente de revisión.';
            }

            if (codigo === 'IMAGEN') {
                return 'Imagen pendiente de revisión.';
            }

            return 'Pendiente de revisión.';
        }

        // Define que archivo permite cada tipo de evidencia que se puede adjuntar.
        function configuracionArchivoEvidencia(codigoEvidencia, tamanioMaximoMb) {
            const codigo = String(codigoEvidencia || '').toUpperCase();
            const tamanio = Number(tamanioMaximoMb || 0);
            const textoTamanio = tamanio > 0 ? `Maximo ${tamanio} MB` : 'Archivo opcional';

            if (codigo === 'PDF') {
                return {
                    permiteArchivo: true,
                    accept: 'application/pdf,.pdf',
                    icono: 'fa-regular fa-file-pdf',
                    textoVacio: 'Sin PDF seleccionado',
                    estado: textoTamanio,
                };
            }

            if (codigo === 'IMAGEN') {
                return {
                    permiteArchivo: true,
                    accept: 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp',
                    icono: 'fa-regular fa-image',
                    textoVacio: 'Sin imagen seleccionada',
                    estado: textoTamanio,
                };
            }

            return {
                permiteArchivo: false,
                accept: '',
                icono: 'fa-regular fa-file',
                textoVacio: 'Sin archivo seleccionado',
                estado: 'No requiere archivo',
            };
        }

        // Pinta los certificados previos que necesita el requisito, si corresponde.
        function certificadosRequeridosHtml(certificados) {
            if (!Array.isArray(certificados) || certificados.length === 0) {
                return '';
            }

            return `
                <span class="tramite-evidencia-certificados">
                    ${certificados.map((certificado) => `
                        <span>${escaparHtml(certificado.nombre)}</span>
                    `).join('')}
                </span>
            `;
        }

        // Busca el beneficiario seleccionado dentro de la lista enviada por el controlador.
        function personaSeleccionada(idPersona) {
            return personasTramite.find((persona) => String(persona.id) === String(idPersona));
        }

        // Devuelve solo los tipos de certificado que el beneficiario puede iniciar.
        function tiposPermitidosPorBeneficiario(idBeneficiario) {
            if (!idBeneficiario) {
                return [];
            }

            const certificadosVigentes = new Set(
                (certificadosVigentesPorPersona[idBeneficiario] || []).map((id) => String(id))
            );

            return tiposCertificadosTramite.filter((tipoCertificado) => {
                const dependencias = dependenciasPorTipo[tipoCertificado.id] || [];

                return dependencias.every((dependencia) => (
                    certificadosVigentes.has(String(dependencia.id_tipo_certificado))
                ));
            });
        }

        // Acomoda cualquier opcion al formato que usa el selector visual.
        function normalizarOpcionSelector(opcion) {
            return {
                id: String(opcion?.id ?? ''),
                nombre: opcion?.nombre || 'Sin nombre',
                detalle: opcion?.detalle || '',
                tipo: opcion?.tipo || '',
                busqueda: String(`${opcion?.nombre || ''} ${opcion?.detalle || ''}`).toLowerCase(),
            };
        }

        // Pinta la opcion seleccionada y actualiza el select real que se envia al backend.
        function pintarSelectorTramite(selector, valor) {
            const selectReal = selector?.querySelector('[data-tramite-native]');
            const textoNombre = selector?.querySelector('[data-tramite-label]');
            const textoAyuda = selector?.querySelector('[data-tramite-help]');
            const botonAbrir = selector?.querySelector('[data-tramite-toggle]');
            const opciones = Array.from(selector?.querySelectorAll('[data-tramite-option]') || []);
            const opcion = opciones.find((item) => String(item.dataset.value) === String(valor));

            if (!selectReal || !textoNombre || !textoAyuda || !botonAbrir) {
                return;
            }

            if (!opcion) {
                selectReal.value = '';
                textoNombre.textContent = botonAbrir.dataset.placeholder || 'Seleccione';
                textoAyuda.textContent = botonAbrir.dataset.help || 'Busque por nombre';
                opciones.forEach((item) => item.classList.remove('is-selected'));
                return;
            }

            selectReal.value = opcion.dataset.value || '';
            textoNombre.textContent = opcion.dataset.label || 'Seleccionado';
            textoAyuda.textContent = opcion.dataset.help || '';
            opciones.forEach((item) => item.classList.toggle('is-selected', item === opcion));
        }

        // Selecciona una opcion desde JS y avisa al resto del formulario si corresponde.
        function seleccionarOpcionTramite(selector, valor, emitirCambio = true) {
            const selectReal = selector?.querySelector('[data-tramite-native]');

            if (!selectReal) {
                return;
            }

            pintarSelectorTramite(selector, valor);

            if (emitirCambio) {
                selectReal.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // Reconstruye el selector cuando cambian sus opciones, por ejemplo los tramitadores de una empresa.
        function cargarOpcionesSelector(selector, opciones) {
            const selectReal = selector?.querySelector('[data-tramite-native]');
            const contenedorOpciones = selector?.querySelector('[data-tramite-options]');
            const mensajeVacio = selector?.querySelector('[data-tramite-empty]');
            const opcionesNormalizadas = opciones.map(normalizarOpcionSelector).filter((opcion) => opcion.id);

            if (!selectReal || !contenedorOpciones) {
                return;
            }

            selectReal.innerHTML = '<option value="">Seleccione</option>';
            contenedorOpciones.querySelectorAll('[data-tramite-option]').forEach((opcion) => opcion.remove());

            opcionesNormalizadas.forEach((opcion) => {
                const option = document.createElement('option');
                option.value = opcion.id;
                option.textContent = opcion.nombre;
                option.dataset.label = opcion.nombre;
                option.dataset.help = opcion.detalle;
                option.dataset.tipo = opcion.tipo;
                selectReal.appendChild(option);

                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'tramite-persona-select-option';
                boton.dataset.tramiteOption = '';
                boton.dataset.value = opcion.id;
                boton.dataset.label = opcion.nombre;
                boton.dataset.help = opcion.detalle;
                boton.dataset.tipo = opcion.tipo;
                boton.dataset.search = opcion.busqueda;
                boton.innerHTML = `
                    <span class="tramite-persona-select-option-main">
                        <strong>${escaparHtml(opcion.nombre)}</strong>
                        <small>${escaparHtml(opcion.detalle)}</small>
                    </span>
                `;
                contenedorOpciones.insertBefore(boton, mensajeVacio || null);
            });

            inicializarOpcionesSelector(selector);
        }

        // Conecta cada opcion del menu con el select real.
        function inicializarOpcionesSelector(selector) {
            const menu = selector?.querySelector('[data-tramite-menu]');

            selector?.querySelectorAll('[data-tramite-option]').forEach((opcion) => {
                opcion.onclick = () => {
                    if (selector.dataset.bloqueado === '1') {
                        return;
                    }

                    seleccionarOpcionTramite(selector, opcion.dataset.value || '');
                    if (menu) {
                        menu.hidden = true;
                    }
                    selector.querySelector('[data-tramite-toggle]')?.classList.remove('is-open');
                };
            });
        }

        // Deja listo un selector visual: abrir, buscar, cerrar y sincronizar con su select real.
        function inicializarSelectorTramite(selector) {
            const selectReal = selector?.querySelector('[data-tramite-native]');
            const botonAbrir = selector?.querySelector('[data-tramite-toggle]');
            const menu = selector?.querySelector('[data-tramite-menu]');
            const buscador = selector?.querySelector('[data-tramite-search]');
            const mensajeVacio = selector?.querySelector('[data-tramite-empty]');

            if (!selector || !selectReal || !botonAbrir || !menu || !buscador) {
                return;
            }

            const estaBloqueado = selector.dataset.bloqueado === '1';

            function cerrarMenu() {
                menu.hidden = true;
                botonAbrir.classList.remove('is-open');
            }

            function abrirMenu() {
                if (estaBloqueado) {
                    return;
                }

                menu.hidden = false;
                botonAbrir.classList.add('is-open');
                buscador.value = '';
                filtrarOpciones();
                buscador.focus();
            }

            function filtrarOpciones() {
                const termino = buscador.value.trim().toLowerCase();
                let visibles = 0;

                selector.querySelectorAll('[data-tramite-option]').forEach((opcion) => {
                    const coincide = !termino || (opcion.dataset.search || '').includes(termino);
                    opcion.classList.toggle('is-hidden', !coincide);
                    if (coincide) {
                        visibles++;
                    }
                });

                mensajeVacio?.classList.toggle('is-hidden', visibles > 0);
            }

            botonAbrir.addEventListener('click', () => {
                if (estaBloqueado) {
                    return;
                }

                menu.hidden ? abrirMenu() : cerrarMenu();
            });

            buscador.addEventListener('input', filtrarOpciones);
            selectReal.addEventListener('change', () => pintarSelectorTramite(selector, selectReal.value));
            inicializarOpcionesSelector(selector);

            document.addEventListener('click', (event) => {
                if (!selector.contains(event.target)) {
                    cerrarMenu();
                }
            });
        }

        // Ajusta el check que permite usar al beneficiario como tramitador.
        // Persona natural: siempre se usa la misma persona.
        // Empresa: el usuario decide si corresponde.
        function actualizarCheckMismoBeneficiario(beneficiario, cambioBeneficiario, valorTramitadorActual) {
            if (!mismoBeneficiario) {
                return;
            }

            const esPersonaNatural = beneficiario?.tipo === 'NATURAL';

            if (!beneficiario?.id) {
                mismoBeneficiario.checked = false;
                mismoBeneficiario.disabled = false;
                return;
            }

            if (esPersonaNatural) {
                mismoBeneficiario.checked = true;
                mismoBeneficiario.disabled = true;
                return;
            }

            // En empresas el check se reinicia al cambiar de beneficiario.
            mismoBeneficiario.disabled = false;
            if (cambioBeneficiario) {
                mismoBeneficiario.checked = String(valorTramitadorActual || '') === String(beneficiario.id);
            }
        }

        // Agrega el beneficiario a la lista de tramitadores cuando el check esta marcado.
        function opcionesConBeneficiario(opciones, beneficiario, incluirBeneficiario) {
            if (!incluirBeneficiario || !beneficiario?.id) {
                return opciones;
            }

            const yaExiste = opciones.some((opcion) => String(opcion.id) === String(beneficiario.id));

            if (yaExiste) {
                return opciones;
            }

            return [beneficiario, ...opciones];
        }

        // Carga tramitadores segun el beneficiario seleccionado.
        function cargarTramitadoresDelBeneficiario() {
            if (!beneficiarioSelect || !tramitadorSelect || !selectorTramitador) {
                return;
            }

            const idBeneficiario = String(beneficiarioSelect.value || '');
            const beneficiario = personaSeleccionada(idBeneficiario);
            const esPersonaNatural = beneficiario?.tipo === 'NATURAL';
            const opcionesServidor = tramitadoresPorBeneficiario[idBeneficiario] || [];
            const opcionesEmpresa = Array.isArray(opcionesServidor) ? opcionesServidor : Object.values(opcionesServidor);
            const valorActual = String(tramitadorSelect.value || tramitadorSeleccionadoServidor || '');
            const cambioBeneficiario = idBeneficiario !== idBeneficiarioAnterior;

            actualizarCheckMismoBeneficiario(beneficiario, cambioBeneficiario, valorActual);

            const usarMismoBeneficiario = Boolean(idBeneficiario && (esPersonaNatural || mismoBeneficiario?.checked));
            const opciones = opcionesConBeneficiario(opcionesEmpresa, beneficiario, usarMismoBeneficiario);
            const valorExiste = opciones.some((opcion) => String(opcion.id) === String(valorActual));

            cargarOpcionesSelector(selectorTramitador, opciones);

            if (usarMismoBeneficiario) {
                seleccionarOpcionTramite(selectorTramitador, idBeneficiario, false);
                idBeneficiarioAnterior = idBeneficiario;
                return;
            }

            seleccionarOpcionTramite(selectorTramitador, valorExiste ? valorActual : '', false);
            idBeneficiarioAnterior = idBeneficiario;
        }

        // Actualiza los tipos de certificado despues de elegir beneficiario.
        function cargarTiposCertificadosDelBeneficiario() {
            if (!beneficiarioSelect || !tipoSelect || !selectorTipoCertificado) {
                return;
            }

            const idBeneficiario = String(beneficiarioSelect.value || '');
            const valorActual = String(tipoSelect.value || tipoSeleccionadoServidor || '');
            const opciones = tiposPermitidosPorBeneficiario(idBeneficiario);
            const valorExiste = opciones.some((opcion) => String(opcion.id) === valorActual);

            cargarOpcionesSelector(selectorTipoCertificado, opciones);
            seleccionarOpcionTramite(selectorTipoCertificado, valorExiste ? valorActual : '', false);
            renderRequisitos();
        }

        // Activa los botones Seleccionar, Ver y Quitar para PDF o imagen.
        function prepararControlPdf(fila) {
            const inputArchivo = fila.querySelector('.tramite-pdf-input');
            const control = fila.querySelector('.tramite-pdf-control');
            const nombre = fila.querySelector('.tramite-pdf-name');
            const estado = fila.querySelector('.tramite-pdf-status');
            const botonVer = fila.querySelector('.tramite-pdf-button.is-view');
            const botonQuitar = fila.querySelector('.tramite-pdf-button.is-remove');
            const textoVacio = inputArchivo.dataset.textoVacio || 'Sin archivo seleccionado';
            const textoEstado = inputArchivo.dataset.estado || 'Archivo opcional';
            const textoInvalido = inputArchivo.dataset.invalido || 'Archivo no permitido';
            const textoSeleccionado = inputArchivo.dataset.seleccionado || 'Archivo seleccionado para enviar.';
            let urlTemporal = null;

            function limpiarArchivo() {
                if (urlTemporal) {
                    URL.revokeObjectURL(urlTemporal);
                    urlTemporal = null;
                }

                inputArchivo.value = '';
                nombre.textContent = textoVacio;
                estado.textContent = textoEstado;
                botonVer.disabled = true;
                botonQuitar.disabled = true;
                control.classList.remove('is-invalid');
                limpiarErrorArchivoCliente(inputArchivo);
                actualizarProgresoDocumentos();
            }

            inputArchivo.addEventListener('change', () => {
                const archivo = inputArchivo.files?.[0];

                if (urlTemporal) {
                    URL.revokeObjectURL(urlTemporal);
                    urlTemporal = null;
                }

                if (!archivo) {
                    limpiarArchivo();
                    return;
                }

                if (!archivoPermitidoPorInput(inputArchivo, archivo)) {
                    limpiarArchivo();
                    estado.textContent = textoInvalido;
                    control.classList.add('is-invalid');
                    return;
                }

                urlTemporal = URL.createObjectURL(archivo);
                nombre.textContent = nombreArchivoCorto(archivo.name);
                estado.textContent = textoSeleccionado;
                botonVer.disabled = false;
                botonQuitar.disabled = false;
                control.classList.remove('is-invalid');
                limpiarErrorArchivoCliente(inputArchivo);
                actualizarProgresoDocumentos();
            });

            botonVer.addEventListener('click', () => {
                if (urlTemporal) {
                    window.open(urlTemporal, '_blank');
                }
            });

            botonQuitar.addEventListener('click', limpiarArchivo);
        }

        // Resume únicamente los archivos que el solicitante debe adjuntar en este formulario.
        function actualizarProgresoDocumentos() {
            const archivos = Array.from(contenedorRequisitos?.querySelectorAll('.tramite-pdf-input[required]') || []);
            const total = archivos.length;
            const completados = archivos.filter(input => input.files?.length > 0).length;
            const porcentaje = total > 0 ? Math.round((completados / total) * 100) : 0;

            if (progresoTexto) {
                progresoTexto.textContent = `${completados} de ${total} documentos`;
            }

            if (progresoBarra) {
                progresoBarra.style.width = `${porcentaje}%`;
            }

            if (progresoControl) {
                progresoControl.setAttribute('aria-valuemax', String(total));
                progresoControl.setAttribute('aria-valuenow', String(completados));
            }

            if (resumenDocumentos) {
                resumenDocumentos.classList.toggle('is-hidden', total === 0);
            }

            if (resumenDocumentosTexto) {
                const pendientes = total - completados;
                resumenDocumentosTexto.textContent = pendientes === 1
                    ? '1 documento pendiente'
                    : `${pendientes} documentos pendientes`;
            }

            if (resumenDocumentosBarra) {
                resumenDocumentosBarra.style.width = `${porcentaje}%`;
            }

            actualizarEstadosRequisitos();
        }

        // Actualiza solo la presentación; los valores enviados a Laravel no cambian.
        function actualizarEstadosRequisitos() {
            contenedorRequisitos?.querySelectorAll('.tramite-requisito-item').forEach((requisito) => {
                const archivo = requisito.querySelector('.tramite-pdf-input[required]');
                const texto = requisito.querySelector('.tramite-texto-input[required]');
                const estado = requisito.querySelector('.tramite-requisito-status');

                if (!archivo && !texto) {
                    return;
                }

                const completado = archivo
                    ? Boolean(archivo.files?.length)
                    : texto.value.trim() !== '';

                requisito.classList.toggle('is-complete', completado);

                if (estado) {
                    estado.innerHTML = completado
                        ? '<i class="fa-solid fa-check"></i> Completado'
                        : '<i class="fa-regular fa-clock"></i> Pendiente';
                }
            });
        }

        // Evita enviar el formulario si falta un archivo obligatorio. Asi no se pierden los archivos ya elegidos.
        function validarArchivosAntesDeEnviar() {
            const archivosObligatorios = Array.from(document.querySelectorAll('.tramite-pdf-input[required]'));
            const primerFaltante = archivosObligatorios.find(input => !input.files || input.files.length === 0);

            archivosObligatorios.forEach(input => limpiarErrorArchivoCliente(input));

            if (!primerFaltante) {
                return true;
            }

            mostrarErrorArchivoCliente(primerFaltante, 'Seleccione la evidencia solicitada antes de enviar el tramite.');
            const requisitoFaltante = primerFaltante.closest('.tramite-requisito-item');
            if (requisitoFaltante) {
                requisitoFaltante.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            return false;
        }

        function mostrarErrorArchivoCliente(inputArchivo, mensaje) {
            const campo = inputArchivo.closest('.tramite-requisito-field');
            const control = inputArchivo.closest('.tramite-pdf-control');

            if (!campo) {
                return;
            }

            control?.classList.add('is-invalid');

            let error = campo.querySelector('[data-error-archivo-cliente]');
            if (!error) {
                error = document.createElement('p');
                error.dataset.errorArchivoCliente = '1';
                error.className = 'mt-2 text-sm text-red-600';
                campo.appendChild(error);
            }

            error.textContent = mensaje;
        }

        function limpiarErrorArchivoCliente(inputArchivo) {
            const campo = inputArchivo.closest('.tramite-requisito-field');
            const control = inputArchivo.closest('.tramite-pdf-control');
            const error = campo?.querySelector('[data-error-archivo-cliente]');

            control?.classList.remove('is-invalid');
            error?.remove();
        }

        // El tipo TEXTO es obligatorio y se valida sin mostrar contador de caracteres.
        function validarTextosAntesDeEnviar() {
            const textosObligatorios = Array.from(document.querySelectorAll('.tramite-texto-input[required]'));

            textosObligatorios.forEach(input => limpiarErrorTextoCliente(input));

            const primerFaltante = textosObligatorios.find(input => input.value.trim() === '');

            if (!primerFaltante) {
                return true;
            }

            mostrarErrorTextoCliente(primerFaltante, 'Ingrese el dato solicitado antes de enviar el tramite.');
            const requisitoFaltante = primerFaltante.closest('.tramite-requisito-item');
            if (requisitoFaltante) {
                requisitoFaltante.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            primerFaltante.focus();

            return false;
        }

        function prepararControlTexto(fila) {
            const inputTexto = fila.querySelector('.tramite-texto-input');

            inputTexto?.addEventListener('input', () => {
                if (inputTexto.value.trim() !== '') {
                    limpiarErrorTextoCliente(inputTexto);
                }

                actualizarEstadosRequisitos();
            });
        }

        function mostrarErrorTextoCliente(inputTexto, mensaje) {
            const campo = inputTexto.closest('.tramite-requisito-field');

            inputTexto.classList.add('is-invalid');

            let error = campo?.querySelector('[data-error-texto-cliente]');
            if (!error && campo) {
                error = document.createElement('p');
                error.dataset.errorTextoCliente = '1';
                error.className = 'mt-2 text-sm text-red-600';
                campo.appendChild(error);
            }

            if (error) {
                error.textContent = mensaje;
            }
        }

        function limpiarErrorTextoCliente(inputTexto) {
            const campo = inputTexto.closest('.tramite-requisito-field');

            inputTexto.classList.remove('is-invalid');
            campo?.querySelector('[data-error-texto-cliente]')?.remove();
        }

        // Valida visualmente contra el accept del input; la validacion definitiva queda en Laravel.
        function archivoPermitidoPorInput(inputArchivo, archivo) {
            const nombre = archivo.name.toLowerCase();
            const accepts = String(inputArchivo.getAttribute('accept') || '')
                .split(',')
                .map((item) => item.trim().toLowerCase())
                .filter(Boolean);

            if (accepts.length === 0) {
                return true;
            }

            return accepts.some((accept) => {
                if (accept.startsWith('.')) {
                    return nombre.endsWith(accept);
                }

                if (accept.endsWith('/*')) {
                    return archivo.type.startsWith(accept.replace('/*', '/'));
                }

                return archivo.type === accept;
            });
        }

        // Dibuja cada requisito como un campo del formulario, conservando los nombres que recibe Laravel.
        function renderRequisitos() {
            const requisitos = requisitosPorTipo[tipoSelect?.value] || [];

            if (!contenedorRequisitos) {
                return;
            }

            contenedorRequisitos.innerHTML = '';
            contenedorRequisitos.classList.toggle('is-empty', requisitos.length === 0);

            if (requisitos.length === 0) {
                contenedorRequisitos.innerHTML = `
                    <div class="tramite-requisitos-empty">
                        Seleccione un tipo de certificado para cargar los requisitos.
                    </div>
                `;
                actualizarProgresoDocumentos();
                return;
            }

            requisitos.forEach((requisito, index) => {
                const bloque = document.createElement('section');
                const inputId = `documento_requisito_${index}`;
                const errorRequisito = errorLaravel(`requisitos_certificados.${index}.id_requisito`);
                const errorDocumento = errorLaravel(`documentos_requisitos.${index}`);
                const errorTexto = errorLaravel(`requisitos_certificados.${index}.valor_texto`);
                const codigoEvidencia = String(requisito.tipo_evidencia_codigo || 'PDF').toUpperCase();
                const tipoEvidenciaNombre = requisito.tipo_evidencia_nombre || codigoEvidencia;
                const configArchivo = configuracionArchivoEvidencia(codigoEvidencia, requisito.tipo_evidencia_tamanio_maximo_mb);
                const certificadosHtml = certificadosRequeridosHtml(requisito.certificados_requeridos);
                const textoAnterior = valorTextoAnterior(requisito, index);
                const requiereRespuesta = codigoEvidencia === 'TEXTO' || configArchivo.permiteArchivo;
                const estadoInicial = requiereRespuesta ? 'Pendiente' : 'En revisión';
                const claseEstado = requiereRespuesta ? '' : 'is-review';
                const iconoEstado = requiereRespuesta ? 'fa-regular fa-clock' : 'fa-regular fa-hourglass-half';

                bloque.className = 'tramite-requisito-item';
                bloque.innerHTML = `
                    <span class="tramite-requisito-number">${index + 1}</span>

                    <div class="tramite-requisito-description">
                        <span class="tramite-requisito-title">${escaparHtml(requisito.descripcion)}</span>
                        ${errorRequisito ? `<p class="mt-2 text-sm text-red-600">${escaparHtml(errorRequisito)}</p>` : ''}
                    </div>

                    <div class="tramite-requisito-evidencia">
                        <span class="tramite-evidencia-info">
                            <span class="tramite-evidencia-chip">${escaparHtml(tipoEvidenciaNombre)}</span>
                            ${certificadosHtml}
                        </span>

                        <input type="hidden" name="requisitos_certificados[${index}][id_requisito_tipo_certificado]" value="${escaparHtml(requisito.id_requisito_tipo_certificado || '')}">
                        <input type="hidden" name="requisitos_certificados[${index}][id_requisito]" value="${requisito.id_requisito}">
                        <input type="hidden" name="requisitos_certificados[${index}][id_tipo_evidencia]" value="${escaparHtml(requisito.id_tipo_evidencia || '')}">

                        <div class="tramite-requisito-field">
                        ${codigoEvidencia === 'TEXTO' ? `
                            <input
                                type="text"
                                name="requisitos_certificados[${index}][valor_texto]"
                                class="tramite-texto-input ${errorTexto ? 'is-invalid' : ''}"
                                value="${escaparHtml(textoAnterior)}"
                                maxlength="${longitudMaximaEvidenciaTexto}"
                                placeholder="Ingrese el dato solicitado"
                                autocomplete="off"
                                required>
                        ` : configArchivo.permiteArchivo ? `
                            <div class="tramite-pdf-control ${errorDocumento ? 'is-invalid' : ''}">
                                <input class="tramite-pdf-input"
                                    id="${inputId}"
                                    type="file"
                                    name="documentos_requisitos[${index}]"
                                    accept="${escaparHtml(configArchivo.accept)}"
                                    required
                                    data-texto-vacio="${escaparHtml(configArchivo.textoVacio)}"
                                    data-estado="${escaparHtml(configArchivo.estado)}"
                                    data-invalido="Archivo no permitido para ${escaparHtml(tipoEvidenciaNombre)}"
                                    data-seleccionado="${escaparHtml(tipoEvidenciaNombre)} seleccionado para enviar.">

                                <div class="tramite-pdf-info">
                                    <i class="${escaparHtml(configArchivo.icono)}"></i>
                                    <div>
                                        <strong class="tramite-pdf-name">${escaparHtml(configArchivo.textoVacio)}</strong>
                                        <span class="tramite-pdf-status">${escaparHtml(configArchivo.estado)}</span>
                                    </div>
                                </div>

                                <div class="tramite-pdf-actions">
                                    <label for="${inputId}" class="tramite-pdf-button is-select">
                                        <i class="fa-solid fa-upload"></i> Seleccionar
                                    </label>
                                    <button type="button" class="tramite-pdf-button is-view" disabled>
                                        <i class="fa-regular fa-eye"></i> Ver
                                    </button>
                                    <button type="button" class="tramite-pdf-button is-remove" disabled>
                                        <i class="fa-regular fa-trash-can"></i> Quitar
                                    </button>
                                </div>
                            </div>
                        ` : `
                            <div class="tramite-evidencia-pendiente">
                                <span class="tramite-evidencia-pendiente-icon">
                                    <i class="fa-solid fa-circle-info"></i>
                                </span>
                                <span>${escaparHtml(textoEvidenciaTramite(codigoEvidencia))}</span>
                            </div>
                        `}
                        ${errorDocumento ? `<p class="mt-2 text-sm text-red-600">${escaparHtml(errorDocumento)}</p>` : ''}
                        ${errorTexto ? `<p class="mt-2 text-sm text-red-600">${escaparHtml(errorTexto)}</p>` : ''}
                        </div>
                    </div>

                    <span class="tramite-requisito-status ${claseEstado}">
                        <i class="${iconoEstado}"></i> ${estadoInicial}
                    </span>
                `;

                if (codigoEvidencia === 'TEXTO') {
                    prepararControlTexto(bloque);
                } else if (configArchivo.permiteArchivo) {
                    prepararControlPdf(bloque);
                }

                contenedorRequisitos.appendChild(bloque);
            });

            actualizarProgresoDocumentos();
        }

        inicializarSelectorTramite(selectorBeneficiario);
        inicializarSelectorTramite(selectorTramitador);
        inicializarSelectorTramite(selectorTipoCertificado);

        beneficiarioSelect?.addEventListener('change', () => {
            cargarTramitadoresDelBeneficiario();
            cargarTiposCertificadosDelBeneficiario();
        });
        tipoSelect?.addEventListener('change', renderRequisitos);
        mismoBeneficiario?.addEventListener('change', cargarTramitadoresDelBeneficiario);

        // Estado inicial del formulario al abrir la pagina.
        setTimeout(() => {
            pintarSelectorTramite(selectorBeneficiario, beneficiarioSelect?.value || '');
            pintarSelectorTramite(selectorTramitador, tramitadorSelect?.value || '');
            pintarSelectorTramite(selectorTipoCertificado, tipoSelect?.value || '');
            cargarTramitadoresDelBeneficiario();
            cargarTiposCertificadosDelBeneficiario();
            renderRequisitos();
        }, 50);
    });
</script>
