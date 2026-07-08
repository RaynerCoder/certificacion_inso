<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectorTipo = document.querySelector('[data-plantilla-tipo]');
        const resumenTipo = document.querySelector('[data-plantilla-resumen-tipo]');
        const requisitosLista = document.querySelector('[data-plantilla-requisitos]');
        const fondoInput = document.querySelector('[data-plantilla-fondo-input]');
        const fondoPreview = document.querySelector('[data-plantilla-fondo-preview]');
        const fondoSeleccionar = document.querySelector('[data-plantilla-fondo-seleccionar]');
        const fondoVer = document.querySelector('[data-plantilla-fondo-ver]');
        const fondoQuitar = document.querySelector('[data-plantilla-fondo-quitar]');
        const fondoNombre = document.querySelector('[data-plantilla-fondo-nombre]');
        const fondoQuitarInput = document.querySelector('[data-plantilla-fondo-quitar-input]');
        const lienzo = document.querySelector('[data-plantilla-lienzo]');
        const mensajeVacio = document.querySelector('[data-plantilla-empty]');
        const propiedades = document.querySelector('[data-plantilla-propiedades]');
        const contadorCampos = document.querySelector('[data-plantilla-contador-campos]');
        const inputElementos = document.querySelector('[data-plantilla-elementos-input]');
        const formulario = inputElementos?.closest('form');
        const botonVistaPrevia = document.querySelector('[data-plantilla-vista-previa]');
        const botonImprimirPrueba = document.querySelector('[data-plantilla-imprimir-prueba]');
        const botonQuitarCampo = document.querySelector('[data-plantilla-quitar-campo]');
        const propiedadX = document.querySelector('[data-prop-x]');
        const propiedadY = document.querySelector('[data-prop-y]');
        const propiedadAncho = document.querySelector('[data-prop-ancho]');
        const propiedadAlto = document.querySelector('[data-prop-alto]');
        const propiedadTamanoLetra = document.querySelector('[data-prop-tamano-letra]');
        const propiedadAlineacion = document.querySelector('[data-prop-alineacion]');
        const propiedadNegrita = document.querySelector('[data-prop-negrita]');
        const propiedadCursiva = document.querySelector('[data-prop-cursiva]');
        const propiedadSubrayado = document.querySelector('[data-prop-subrayado]');
        const propiedadColorTexto = document.querySelector('[data-prop-color-texto]');

        const tiposCertificados = window.tiposCertificadosPlantilla || [];
        const nombresCampos = new Map(
            [...document.querySelectorAll('[data-plantilla-campo]')]
                .map((boton) => [boton.dataset.codigo, boton.dataset.nombre])
        );

        // Valores solo para previsualizar la plantilla antes de emitir un certificado real.
        const datosPrueba = {
            'certificado.codigo': 'CERT-2026-0001',
            'certificado.fecha_inicio': '06/07/2026',
            'certificado.fecha_fin': '06/07/2027',
            'certificado.descripcion': 'Certificado emitido por INSO',
            'certificado.estado': 'EMITIDO',
            'tipo_certificado.nombre': 'Registro de Producto Plaguicida',
            'area.nombre': 'Área de Plaguicidas',
            'beneficiario.nombre': 'AGROPARC EI S.R.L.',
            'beneficiario.documento': '4895621',
            'beneficiario.correo': 'contacto@agroparc.test',
            'beneficiario.domicilio': 'La Paz, Bolivia',
            'beneficiario.telefono': '+591 72000000',
            'beneficiario.territorio': 'LA PAZ',
            'beneficiario.tipo_persona': 'Empresa',
            'empresa.razon_social': 'AGROPARC EI S.R.L.',
            'empresa.matricula': 'MAT-2026-001',
            'empresa.tipo_empresa': 'S.R.L.',
            'natural.nombres': 'Mario Erwin',
            'natural.apellido_paterno': 'Pedraza',
            'natural.apellido_materno': 'Merida',
            'natural.ocupacion': 'Representante legal',
            'tramitador.nombre': 'Mario Erwin Pedraza Merida',
            'tramitador.documento': '4895621',
            'tramitador.correo': 'mario.pedraza@agroparc.test',
            'tramitador.telefono': '+591 72000000',
            'tramitador.rol': 'Tramitador',
            'tramitador.respaldo': 'Respaldo registrado',
            'producto.codigo': 'PROD-001',
            'producto.nombre_comercial': 'SPIROMAT',
            'producto.nombre_cientifico': 'Insecticida doméstico',
            'producto.clasificacion': 'Insecticida',
            'producto.fabricante': 'Fabricante de prueba',
            'tipo_producto.codigo': 'PUD',
            'producto.estado': 'ACTIVO',
            'registro.codigo': 'INSO-RP-2026-0001',
            'registro.fecha_vigencia': '06/07/2027',
            'registro.cantidad': '1',
            'registro.unidad': 'Unidad',
            'registro.estado': 'ACTIVO',
            'presentacion.descripcion': '1 x 360 ml frasco aerosol',
            'presentacion.cantidad': '360',
            'presentacion.unidad': 'ml',
            'presentacion.url_etiqueta': 'Etiqueta registrada',
            'presentacion.estado': 'ACTIVO',
            'ingrediente.nombre': 'Permetrina',
            'ingrediente.composicion': 'Composición de prueba',
            'ingrediente.riesgo_salud': 'Moderado',
            'ingrediente.porcentaje': '10%',
            'pago.resumen': 'Pago validado',
            'pago.tipo_pago': 'Depósito',
            'pago.fecha': '06/07/2026',
            'pago.monto': '150.00',
            'pago.comprobante': 'Comprobante registrado',
            'pago.factura': 'Factura registrada',
            'pago.procedencia': 'INSO',
            'pago.fecha_validacion': '06/07/2026',
            'pago.funcionario_validador': 'Caja Pagos',
            'requisito.descripcion': 'Solicitud firmada',
            'requisito.tipo_evidencia': 'PDF',
            'requisito.cumple': 'Sí cumple',
            'requisito.estado_revision': 'Aprobado',
            'requisito.observacion': 'Sin observación',
            'evidencia.valor': 'Documento registrado',
            'evidencia.estado': 'ACTIVO',
            'seguimiento.fecha_inicio': '06/07/2026',
            'seguimiento.fecha_derivacion': '06/07/2026',
            'seguimiento.referencia': 'Derivación inicial',
            'seguimiento.descripcion_final': 'Trámite revisado',
            'seguimiento.usuario_origen': 'Solicitante',
            'seguimiento.usuario_siguiente': 'Jefe de área',
            'seguimiento.estado': 'EN_REVISIÓN',
            'revision.usuario': 'Técnico revisor',
            'revision.resultado': 'Cumple',
            'revision.observacion': 'Sin observación',
            'firma.director': 'Director General Ejecutivo',
            'firma.responsable_area': 'Responsable de área',
            'qr.verificacion': 'QR-VERIFICACION',
        };

        let campoSeleccionado = null;
        let urlArchivoFondo = fondoInput?.dataset.plantillaFondoUrl || '';

        // Resumen del tipo seleccionado y sus requisitos.
        function tipoSeleccionado() {
            return tiposCertificados.find((tipo) => String(tipo.id) === String(selectorTipo?.value));
        }

        function actualizarResumenTipo() {
            const tipo = tipoSeleccionado();

            if (!resumenTipo) {
                return;
            }

            if (!tipo) {
                resumenTipo.innerHTML = '<span class="text-sm text-slate-500">Seleccione un tipo de certificado.</span>';
                requisitosLista.innerHTML = '<div class="text-sm text-slate-500">Sin requisitos para mostrar.</div>';
                return;
            }

            resumenTipo.innerHTML = `
                <div class="grid gap-1 text-sm">
                    <span class="font-bold text-emerald-700">${tipo.nombre}</span>
                    <span class="text-slate-600">${tipo.area || 'Sin área asignada'}</span>
                    <span class="plantilla-chip">${tipo.estado || 'ACTIVO'}</span>
                </div>
            `;

            const requisitos = tipo.requisitos || [];
            requisitosLista.innerHTML = requisitos.length
                ? requisitos.map((requisito) => `
                    <div class="rounded-lg border border-slate-200 bg-white p-2 text-sm text-slate-700">
                        <div class="font-semibold">${requisito.descripcion || 'Sin descripción'}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            ${requisito.evidencia || 'Sin evidencia'}
                            ${requisito.certificado_requerido ? ' · ' + requisito.certificado_requerido : ''}
                        </div>
                    </div>
                `).join('')
                : '<div class="text-sm text-slate-500">Sin requisitos para mostrar.</div>';
        }

        function nombreCampo(codigo) {
            return nombresCampos.get(codigo) || codigo || 'Texto fijo';
        }

        function textoElemento(codigo, textoFijo = '') {
            return textoFijo || nombreCampo(codigo);
        }

        function valorPrueba(codigo, textoFijo = '') {
            return textoFijo || datosPrueba[codigo] || nombreCampo(codigo);
        }

        function columnasPorDefecto(codigo) {
            const columnas = {
                'producto.tabla': [
                    { codigo_campo: 'producto.nombre_comercial', titulo_columna: 'Producto', ancho: 33, estado: 'ACTIVO' },
                    { codigo_campo: 'registro.codigo', titulo_columna: 'Registro', ancho: 33, estado: 'ACTIVO' },
                    { codigo_campo: 'presentacion.descripcion', titulo_columna: 'Presentación', ancho: 34, estado: 'ACTIVO' },
                ],
                'ingrediente.tabla': [
                    { codigo_campo: 'ingrediente.nombre', titulo_columna: 'Ingrediente', ancho: 30, estado: 'ACTIVO' },
                    { codigo_campo: 'ingrediente.composicion', titulo_columna: 'Composición', ancho: 45, estado: 'ACTIVO' },
                    { codigo_campo: 'ingrediente.porcentaje', titulo_columna: 'Porcentaje', ancho: 25, estado: 'ACTIVO' },
                ],
                'requisito.tabla': [
                    { codigo_campo: 'requisito.descripcion', titulo_columna: 'Requisito', ancho: 45, estado: 'ACTIVO' },
                    { codigo_campo: 'requisito.tipo_evidencia', titulo_columna: 'Evidencia', ancho: 25, estado: 'ACTIVO' },
                    { codigo_campo: 'requisito.estado_revision', titulo_columna: 'Estado', ancho: 30, estado: 'ACTIVO' },
                ],
                'seguimiento.tabla': [
                    { codigo_campo: 'seguimiento.fecha_derivacion', titulo_columna: 'Fecha', ancho: 25, estado: 'ACTIVO' },
                    { codigo_campo: 'seguimiento.usuario_origen', titulo_columna: 'Derivado por', ancho: 35, estado: 'ACTIVO' },
                    { codigo_campo: 'seguimiento.usuario_siguiente', titulo_columna: 'Derivado a', ancho: 40, estado: 'ACTIVO' },
                ],
            };

            return columnas[codigo] || [];
        }

        function actualizarEstadoVacio() {
            const total = document.querySelectorAll('[data-plantilla-elemento]').length;

            if (contadorCampos) {
                contadorCampos.textContent = String(total);
            }

            if (mensajeVacio) {
                mensajeVacio.style.display = total ? 'none' : 'grid';
            }
        }

        function aplicarPosicion(elemento) {
            const x = Number(elemento.dataset.x || 12);
            const y = Number(elemento.dataset.y || 12);
            const ancho = Number(elemento.dataset.ancho || 180);
            const alto = Number(elemento.dataset.alto || 30);
            const tamanoLetra = Number(elemento.dataset.tamanoLetra || 12);
            const alineacion = elemento.dataset.alineacion || 'IZQUIERDA';

            elemento.style.left = `${x}px`;
            elemento.style.top = `${y}px`;
            elemento.style.width = `${ancho}px`;
            elemento.style.height = `${alto}px`;
            elemento.style.fontSize = `${tamanoLetra}px`;
            elemento.style.color = elemento.dataset.colorTexto || '#0f172a';
            elemento.style.fontWeight = Number(elemento.dataset.negrita || 0) ? '900' : '700';
            elemento.style.fontStyle = Number(elemento.dataset.cursiva || 0) ? 'italic' : 'normal';
            elemento.style.textDecoration = Number(elemento.dataset.subrayado || 0) ? 'underline' : 'none';
            elemento.style.textAlign = alineacion === 'CENTRO'
                ? 'center'
                : (alineacion === 'DERECHA' ? 'right' : 'left');
        }

        function pintarTextoCampo(elemento, texto) {
            let contenedorTexto = elemento.querySelector('[data-plantilla-texto]');

            if (!contenedorTexto) {
                elemento.textContent = '';
                contenedorTexto = document.createElement('span');
                contenedorTexto.className = 'plantilla-element-text';
                contenedorTexto.dataset.plantillaTexto = '1';
                elemento.appendChild(contenedorTexto);
            }

            contenedorTexto.textContent = texto;
        }

        function actualizarPanelMedidas(elemento) {
            if (propiedadX) propiedadX.value = elemento.dataset.x || 0;
            if (propiedadY) propiedadY.value = elemento.dataset.y || 0;
            if (propiedadAncho) propiedadAncho.value = elemento.dataset.ancho || 180;
            if (propiedadAlto) propiedadAlto.value = elemento.dataset.alto || 30;
            if (propiedadTamanoLetra) propiedadTamanoLetra.value = elemento.dataset.tamanoLetra || 12;
            if (propiedadNegrita) propiedadNegrita.checked = Number(elemento.dataset.negrita || 0) === 1;
            if (propiedadCursiva) propiedadCursiva.checked = Number(elemento.dataset.cursiva || 0) === 1;
            if (propiedadSubrayado) propiedadSubrayado.checked = Number(elemento.dataset.subrayado || 0) === 1;
            if (propiedadColorTexto) propiedadColorTexto.value = elemento.dataset.colorTexto || '#0f172a';
        }

        function prepararRedimension(elemento) {
            if (elemento.querySelector('[data-plantilla-resize]')) {
                return;
            }

            const manija = document.createElement('span');
            manija.className = 'plantilla-resize-handle';
            manija.dataset.plantillaResize = '1';
            manija.setAttribute('aria-hidden', 'true');
            elemento.appendChild(manija);

            manija.addEventListener('pointerdown', function (evento) {
                evento.preventDefault();
                evento.stopPropagation();
                seleccionarElemento(elemento);

                const inicioX = evento.clientX;
                const inicioY = evento.clientY;
                const anchoInicial = Number(elemento.dataset.ancho || 180);
                const altoInicial = Number(elemento.dataset.alto || 30);

                function redimensionar(eventoMover) {
                    elemento.dataset.ancho = String(Math.max(35, anchoInicial + eventoMover.clientX - inicioX));
                    elemento.dataset.alto = String(Math.max(18, altoInicial + eventoMover.clientY - inicioY));
                    aplicarPosicion(elemento);
                    actualizarPanelMedidas(elemento);
                    actualizarInputElementos();
                }

                function soltar() {
                    document.removeEventListener('pointermove', redimensionar);
                    document.removeEventListener('pointerup', soltar);
                }

                document.addEventListener('pointermove', redimensionar);
                document.addEventListener('pointerup', soltar);
            });
        }

        function datosElemento(elemento, orden) {
            return {
                tipo_elemento: elemento.dataset.tipoElemento || 'CAMPO',
                codigo_campo: elemento.dataset.codigo || '',
                texto_fijo: elemento.dataset.textoFijo || '',
                pagina: Number(elemento.dataset.pagina || 1),
                posicion_x: Number(elemento.dataset.x || 0),
                posicion_y: Number(elemento.dataset.y || 0),
                ancho: Number(elemento.dataset.ancho || 180),
                alto: Number(elemento.dataset.alto || 30),
                tamano_letra: Number(elemento.dataset.tamanoLetra || 12),
                alineacion: elemento.dataset.alineacion || 'IZQUIERDA',
                negrita: Number(elemento.dataset.negrita || 0),
                cursiva: Number(elemento.dataset.cursiva || 0),
                subrayado: Number(elemento.dataset.subrayado || 0),
                color_texto: elemento.dataset.colorTexto || '#0f172a',
                orden,
                columnas: JSON.parse(elemento.dataset.columnas || '[]'),
            };
        }

        function actualizarInputElementos() {
            const elementos = [...document.querySelectorAll('[data-plantilla-elemento]')]
                .map((elemento, index) => datosElemento(elemento, index + 1));

            if (inputElementos) {
                inputElementos.value = JSON.stringify(elementos);
            }

            actualizarEstadoVacio();
        }

        // Permite seleccionar y mover campos dentro del lienzo.
        function seleccionarElemento(elemento) {
            document.querySelectorAll('[data-plantilla-elemento]').forEach((item) => item.classList.remove('is-selected'));

            campoSeleccionado = elemento;
            elemento.classList.add('is-selected');

            if (propiedades) {
                propiedades.querySelector('[data-prop-codigo]').textContent = elemento.dataset.codigo || 'Texto fijo';
                propiedades.querySelector('[data-prop-nombre]').textContent = textoElemento(elemento.dataset.codigo, elemento.dataset.textoFijo);
            }

            actualizarPanelMedidas(elemento);
            if (propiedadAlineacion) {
                const valor = elemento.dataset.alineacion || 'IZQUIERDA';
                propiedadAlineacion.value = valor.charAt(0) + valor.slice(1).toLowerCase();
            }

            if (botonQuitarCampo) {
                botonQuitarCampo.disabled = false;
            }
        }

        function aplicarPropiedadSeleccionada(campo, valor) {
            if (!campoSeleccionado) {
                return;
            }

            const minimos = { ancho: 35, alto: 18, tamanoLetra: 6 };
            campoSeleccionado.dataset[campo] = minimos[campo]
                ? String(Math.max(minimos[campo], Number(valor || minimos[campo])))
                : valor;

            aplicarPosicion(campoSeleccionado);
            actualizarPanelMedidas(campoSeleccionado);
            actualizarInputElementos();
        }

        function activarMovimiento(elemento) {
            aplicarPosicion(elemento);
            prepararRedimension(elemento);

            elemento.addEventListener('click', () => seleccionarElemento(elemento));
            elemento.addEventListener('pointerdown', function (evento) {
                if (evento.button !== 0 || evento.target.closest('[data-plantilla-resize]')) {
                    return;
                }

                seleccionarElemento(elemento);
                const inicioX = evento.clientX;
                const inicioY = evento.clientY;
                const origenX = Number(elemento.dataset.x || 0);
                const origenY = Number(elemento.dataset.y || 0);

                function mover(eventoMover) {
                    elemento.dataset.x = String(Math.max(0, origenX + eventoMover.clientX - inicioX));
                    elemento.dataset.y = String(Math.max(0, origenY + eventoMover.clientY - inicioY));
                    aplicarPosicion(elemento);
                    actualizarInputElementos();
                }

                function soltar() {
                    document.removeEventListener('pointermove', mover);
                    document.removeEventListener('pointerup', soltar);
                }

                document.addEventListener('pointermove', mover);
                document.addEventListener('pointerup', soltar);
            });
        }

        // Crea el bloque visual que luego se guarda como elemento de plantilla.
        function crearElemento({ codigo = '', nombre = '', textoFijo = '', tipo = 'CAMPO' }) {
            if (!lienzo) {
                return null;
            }

            const tipoElemento = codigo.endsWith('.tabla') ? 'TABLA' : tipo;
            const esTabla = tipoElemento === 'TABLA';
            const elemento = document.createElement(esTabla ? 'div' : 'button');
            const cantidad = document.querySelectorAll('[data-plantilla-elemento]').length;

            if (!esTabla) {
                elemento.type = 'button';
            }

            elemento.dataset.plantillaElemento = '1';
            elemento.dataset.tipoElemento = tipoElemento;
            elemento.dataset.codigo = codigo;
            elemento.dataset.nombre = nombre || textoElemento(codigo, textoFijo);
            elemento.dataset.textoFijo = textoFijo;
            elemento.dataset.pagina = '1';
            elemento.dataset.x = String(18 + (cantidad % 3) * 26);
            elemento.dataset.y = String(18 + cantidad * 42);
            elemento.dataset.ancho = esTabla ? '460' : '210';
            elemento.dataset.alto = esTabla ? '95' : '34';
            elemento.dataset.tamanoLetra = '12';
            elemento.dataset.alineacion = 'IZQUIERDA';
            elemento.dataset.negrita = '0';
            elemento.dataset.cursiva = '0';
            elemento.dataset.subrayado = '0';
            elemento.dataset.colorTexto = '#0f172a';
            elemento.dataset.columnas = JSON.stringify(columnasPorDefecto(codigo));
            elemento.className = esTabla ? 'plantilla-table-sample' : 'plantilla-element';

            if (esTabla) {
                elemento.innerHTML = tablaPrueba(elemento);
            } else {
                pintarTextoCampo(elemento, textoElemento(codigo, textoFijo));
            }

            lienzo.appendChild(elemento);
            activarMovimiento(elemento);
            seleccionarElemento(elemento);
            actualizarInputElementos();

            return elemento;
        }

        function elegirCampoDesdeLista() {
            const opciones = Object.fromEntries([...nombresCampos.entries()]);

            if (window.Swal) {
                Swal.fire({
                    title: 'Agregar campo',
                    input: 'select',
                    inputOptions: opciones,
                    inputPlaceholder: 'Seleccione un campo',
                    showCancelButton: true,
                    confirmButtonText: 'Agregar',
                    cancelButtonText: 'Cancelar',
                }).then((resultado) => {
                    if (resultado.isConfirmed && resultado.value) {
                        crearElemento({ codigo: resultado.value, nombre: nombreCampo(resultado.value) });
                    }
                });
                return;
            }

            const primerCampo = [...nombresCampos.keys()][0];
            if (primerCampo) {
                crearElemento({ codigo: primerCampo, nombre: nombreCampo(primerCampo) });
            }
        }

        function pedirTextoFijo() {
            if (window.Swal) {
                Swal.fire({
                    title: 'Agregar texto fijo',
                    input: 'text',
                    inputPlaceholder: 'Escriba el texto',
                    showCancelButton: true,
                    confirmButtonText: 'Agregar',
                    cancelButtonText: 'Cancelar',
                }).then((resultado) => {
                    if (resultado.isConfirmed && resultado.value) {
                        crearElemento({ textoFijo: resultado.value, tipo: 'TEXTO' });
                    }
                });
                return;
            }

            crearElemento({ textoFijo: 'Texto fijo', tipo: 'TEXTO' });
        }

        function tablaPrueba(elemento) {
            const columnas = JSON.parse(elemento.dataset.columnas || '[]');
            const columnasValidas = columnas.length ? columnas : columnasPorDefecto('producto.tabla');

            return `
                <table>
                    <thead>
                        <tr>
                            ${columnasValidas.map((columna) => `<th>${columna.titulo_columna || nombreCampo(columna.codigo_campo)}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            ${columnasValidas.map((columna) => `<td>${valorPrueba(columna.codigo_campo)}</td>`).join('')}
                        </tr>
                    </tbody>
                </table>
            `;
        }

        // Abre una muestra imprimible usando datos de prueba.
        function abrirVistaPrevia(imprimir = false) {
            if (!lienzo) {
                return;
            }

            const canvas = document.querySelector('.plantilla-canvas');
            const contenidoCompleto = canvas ? canvas.cloneNode(true) : lienzo.cloneNode(true);

            contenidoCompleto.querySelectorAll('[data-plantilla-elemento]').forEach((elemento) => {
                elemento.classList.remove('is-selected');

                if (elemento.classList.contains('plantilla-table-sample')) {
                    elemento.querySelector('[data-plantilla-resize]')?.remove();
                    elemento.innerHTML = tablaPrueba(elemento);
                    return;
                }

                elemento.textContent = valorPrueba(elemento.dataset.codigo, elemento.dataset.textoFijo);
            });

            contenidoCompleto.querySelector('[data-plantilla-empty]')?.remove();

            const ventana = window.open('', '_blank');
            if (!ventana) {
                return;
            }

            ventana.document.open();
            ventana.document.title = 'Vista previa de certificado';

            const estilos = ventana.document.createElement('style');
            estilos.textContent = `
                body { margin: 0; background: #e5e7eb; font-family: Arial, sans-serif; }
                .preview-page { width: 794px; min-height: 1123px; margin: 24px auto; background: #fff; padding: 0; box-shadow: 0 10px 28px rgba(15,23,42,.18); }
                .plantilla-canvas { position: relative; aspect-ratio: 794 / 1123; width: 794px; background: #fff; overflow: hidden; }
                .plantilla-fondo { position: absolute; inset: 0; display: block; width: 100%; height: 100%; border: 0; opacity: .92; object-fit: cover; pointer-events: none; }
                .plantilla-fondo { object-fit: cover; }
                .plantilla-paper-content { position: absolute; inset: 0; z-index: 1; padding: 0; }
                .plantilla-drop-zone { position: absolute; inset: 0; border: 0; background: transparent; }
                .plantilla-element { position: absolute; border: 0; background: transparent; color: #111827; font-size: 13px; font-weight: 700; }
                .plantilla-table-sample { position: absolute; border: 0; background: #fff; }
                table { width: 100%; border-collapse: collapse; font-size: 12px; }
                th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
                th { background: #f3f4f6; }
                @media print {
                    body { background: #fff; }
                    .preview-page { margin: 0; width: auto; min-height: auto; box-shadow: none; }
                }
            `;
            ventana.document.head.appendChild(estilos);

            const pagina = ventana.document.createElement('main');
            pagina.className = 'preview-page';
            pagina.appendChild(ventana.document.importNode(contenidoCompleto, true));
            ventana.document.body.appendChild(pagina);
            ventana.document.close();

            if (imprimir) {
                ventana.addEventListener('load', () => ventana.print());
            }
        }

        function mostrarFondoSeleccionado(nombre, url, esImagen) {
            urlArchivoFondo = url || '';
            fondoNombre.textContent = nombre || 'Sin archivo seleccionado';
            fondoVer.disabled = !urlArchivoFondo;
            fondoQuitar.disabled = !urlArchivoFondo;

            fondoPreview?.removeAttribute('src');
            if (fondoPreview) {
                fondoPreview.style.display = 'none';
            }

            if (esImagen && fondoPreview) {
                fondoPreview.src = urlArchivoFondo;
                fondoPreview.style.display = 'block';
            }
        }

        function limpiarFondo() {
            if (fondoInput) {
                fondoInput.value = '';
            }

            if (fondoQuitarInput) {
                fondoQuitarInput.value = '1';
            }

            mostrarFondoSeleccionado('Sin archivo seleccionado', '', false);
        }

        document.querySelectorAll('[data-plantilla-campo]').forEach((boton) => {
            boton.addEventListener('click', function () {
                crearElemento({ codigo: this.dataset.codigo, nombre: this.dataset.nombre });
            });
        });

        document.querySelectorAll('[data-plantilla-tool]').forEach((boton) => {
            boton.addEventListener('click', function () {
                const accion = this.dataset.plantillaTool;

                if (accion === 'texto') {
                    pedirTextoFijo();
                }

                if (accion === 'campo') {
                    elegirCampoDesdeLista();
                }

                if (accion === 'tabla') {
                    crearElemento({ codigo: 'producto.tabla', nombre: 'Tabla de productos', tipo: 'TABLA' });
                }

                if (accion === 'firma') {
                    crearElemento({ codigo: 'firma.responsable_area', nombre: 'Firma responsable de área', tipo: 'FIRMA' });
                }

                if (accion === 'qr') {
                    crearElemento({ codigo: 'qr.verificacion', nombre: 'QR de verificación', tipo: 'QR' });
                }
            });
        });

        document.querySelectorAll('[data-plantilla-elemento]').forEach((elemento, index) => {
            if (!elemento.dataset.x) {
                elemento.dataset.x = String(18 + (index % 3) * 26);
            }

            if (!elemento.dataset.y) {
                elemento.dataset.y = String(18 + index * 42);
            }

            if (!elemento.dataset.ancho) {
                elemento.dataset.ancho = elemento.dataset.tipoElemento === 'TABLA' ? '460' : '210';
            }

            if (!elemento.dataset.alto) {
                elemento.dataset.alto = elemento.dataset.tipoElemento === 'TABLA' ? '95' : '34';
            }

            if (!elemento.dataset.tipoElemento) {
                elemento.dataset.tipoElemento = 'CAMPO';
            }

            if (!elemento.dataset.columnas) {
                elemento.dataset.columnas = '[]';
            }

            if (!elemento.dataset.cursiva) {
                elemento.dataset.cursiva = '0';
            }

            if (!elemento.dataset.subrayado) {
                elemento.dataset.subrayado = '0';
            }

            if (!elemento.dataset.colorTexto) {
                elemento.dataset.colorTexto = '#0f172a';
            }

            if (elemento.dataset.tipoElemento === 'TABLA') {
                elemento.className = 'plantilla-table-sample';
                elemento.innerHTML = tablaPrueba(elemento);
            } else {
                elemento.className = 'plantilla-element';
                pintarTextoCampo(elemento, textoElemento(elemento.dataset.codigo, elemento.dataset.textoFijo));
            }

            activarMovimiento(elemento);
        });

        botonQuitarCampo?.addEventListener('click', function () {
            if (!campoSeleccionado) {
                return;
            }

            campoSeleccionado.remove();
            campoSeleccionado = null;
            this.disabled = true;

            if (propiedades) {
                propiedades.querySelector('[data-prop-codigo]').textContent = 'Sin campo seleccionado';
                propiedades.querySelector('[data-prop-nombre]').textContent = 'Seleccione un campo del lienzo.';
            }

            actualizarInputElementos();
        });

        fondoSeleccionar?.addEventListener('click', () => fondoInput?.click());
        fondoVer?.addEventListener('click', () => {
            if (urlArchivoFondo) {
                window.open(urlArchivoFondo, '_blank');
            }
        });
        fondoQuitar?.addEventListener('click', limpiarFondo);
        propiedadX?.addEventListener('input', () => aplicarPropiedadSeleccionada('x', propiedadX.value || '0'));
        propiedadY?.addEventListener('input', () => aplicarPropiedadSeleccionada('y', propiedadY.value || '0'));
        propiedadAncho?.addEventListener('input', () => aplicarPropiedadSeleccionada('ancho', propiedadAncho.value || '180'));
        propiedadAlto?.addEventListener('input', () => aplicarPropiedadSeleccionada('alto', propiedadAlto.value || '30'));
        propiedadTamanoLetra?.addEventListener('input', () => aplicarPropiedadSeleccionada('tamanoLetra', propiedadTamanoLetra.value || '12'));
        propiedadAlineacion?.addEventListener('change', () => {
            const valor = (propiedadAlineacion.value || 'Izquierda').toUpperCase();
            aplicarPropiedadSeleccionada('alineacion', valor);
        });
        propiedadNegrita?.addEventListener('change', () => {
            aplicarPropiedadSeleccionada('negrita', propiedadNegrita.checked ? '1' : '0');
        });
        propiedadCursiva?.addEventListener('change', () => {
            aplicarPropiedadSeleccionada('cursiva', propiedadCursiva.checked ? '1' : '0');
        });
        propiedadSubrayado?.addEventListener('change', () => {
            aplicarPropiedadSeleccionada('subrayado', propiedadSubrayado.checked ? '1' : '0');
        });
        propiedadColorTexto?.addEventListener('input', () => {
            aplicarPropiedadSeleccionada('colorTexto', propiedadColorTexto.value || '#0f172a');
        });

        document.querySelectorAll('[data-prop-accion]').forEach((boton) => {
            boton.addEventListener('click', function () {
                if (!campoSeleccionado) {
                    return;
                }

                const accion = this.dataset.propAccion;
                const tamanoActual = Number(campoSeleccionado.dataset.tamanoLetra || 12);

                if (accion === 'letra_menos') {
                    aplicarPropiedadSeleccionada('tamanoLetra', String(Math.max(6, tamanoActual - 1)));
                }

                if (accion === 'letra_mas') {
                    aplicarPropiedadSeleccionada('tamanoLetra', String(tamanoActual + 1));
                }

                if (accion === 'negrita') {
                    aplicarPropiedadSeleccionada('negrita', Number(campoSeleccionado.dataset.negrita || 0) ? '0' : '1');
                }

                if (accion === 'cursiva') {
                    aplicarPropiedadSeleccionada('cursiva', Number(campoSeleccionado.dataset.cursiva || 0) ? '0' : '1');
                }

                if (accion === 'subrayado') {
                    aplicarPropiedadSeleccionada('subrayado', Number(campoSeleccionado.dataset.subrayado || 0) ? '0' : '1');
                }
            });
        });

        if (fondoInput) {
            fondoInput.addEventListener('change', function () {
                const archivo = this.files && this.files[0];
                if (!archivo) {
                    return;
                }

                if (fondoQuitarInput) {
                    fondoQuitarInput.value = '0';
                }

                const url = URL.createObjectURL(archivo);
                mostrarFondoSeleccionado(archivo.name, url, archivo.type.startsWith('image/'));
            });

            if (fondoInput.dataset.plantillaFondoUrl) {
                const url = fondoInput.dataset.plantillaFondoUrl;
                const esImagen = /\.(jpg|jpeg|png|webp)$/i.test(url);
                mostrarFondoSeleccionado(fondoNombre?.textContent || 'Plantilla guardada', url, esImagen);
            }
        }

        selectorTipo?.addEventListener('change', actualizarResumenTipo);
        botonVistaPrevia?.addEventListener('click', () => abrirVistaPrevia(false));
        botonImprimirPrueba?.addEventListener('click', () => abrirVistaPrevia(true));
        formulario?.addEventListener('submit', actualizarInputElementos);

        actualizarResumenTipo();
        actualizarInputElementos();
    });
</script>
