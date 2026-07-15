<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectorTipo = document.querySelector('[data-plantilla-tipo]');
        const selectorTamano = document.querySelector('[name="form_tamano_papel"]') || document.querySelector('[data-plantilla-tamano]');
        const selectorOrientacion = document.querySelector('[name="form_orientacion"]') || document.querySelector('[data-plantilla-orientacion]');
        const selectorAjusteFondo = document.querySelector('[name="form_ajuste_fondo"]') || document.querySelector('[data-plantilla-ajuste-fondo]');
        const selectorFondoTrabajo = document.querySelector('[name="form_fondo_trabajo"]') || document.querySelector('[data-plantilla-fondo-trabajo]');
        const resumenTipo = document.querySelector('[data-plantilla-resumen-tipo]');
        const buscadorCampo = document.querySelector('[data-plantilla-buscar-campo]');
        const lienzo = document.querySelector('[data-plantilla-lienzo]');
        const inputElementos = document.querySelector('[data-plantilla-elementos-input]');
        const formularioPlantilla = inputElementos?.closest('form');
        const contadorElementos = document.querySelector('[data-plantilla-contador-campos]');
        const panelPropiedades = document.querySelector('[data-plantilla-propiedades]');
        const panelCapas = document.querySelector('[data-plantilla-capas]');
        const zoomValor = document.querySelector('[data-plantilla-zoom-valor]');
        const fondoInput = document.querySelector('[data-plantilla-fondo-input]');
        const fondoPreview = document.querySelector('[data-plantilla-fondo-preview]');
        const fondoPlaceholder = document.querySelector('[data-plantilla-fondo-placeholder]');
        const fondoMedidas = document.querySelector('[data-plantilla-fondo-medidas]');
        const fondoSeleccionar = document.querySelector('[data-plantilla-fondo-seleccionar]');
        const fondoVer = document.querySelector('[data-plantilla-fondo-ver]');
        const fondoQuitar = document.querySelector('[data-plantilla-fondo-quitar]');
        const fondoNombre = document.querySelector('[data-plantilla-fondo-nombre]');
        const fondoQuitarInput = document.querySelector('[data-plantilla-fondo-quitar-input]');

        const tiposCertificados = window.tiposCertificadosPlantilla || [];
        const campos = [...document.querySelectorAll('[data-plantilla-campo]')].map((boton) => ({
            codigo: boton.dataset.codigo,
            nombre: boton.dataset.nombre,
            boton,
        }));

        let elementos = Array.isArray(window.elementosPlantillaIniciales)
            ? window.elementosPlantillaIniciales.map(normalizarElemento)
            : [];
        let indiceSeleccionado = elementos.length ? 0 : null;
        let urlArchivoPlantilla = fondoInput?.dataset.plantillaFondoUrl || '';
        let ultimoCampoSeleccionado = campos[0]?.codigo || null;
        let historial = [];
        let historialRehacer = [];
        let elementoCopiado = null;
        let zoomActual = 1;
        let cuadriculaActiva = true;
        let preparandoHistorialPropiedad = false;

        function tipoSeleccionado() {
            return tiposCertificados.find((tipo) => String(tipo.id) === String(selectorTipo?.value));
        }

        function campoPorCodigo(codigo) {
            return campos.find((campo) => campo.codigo === codigo);
        }

        function clonarElementos() {
            return JSON.parse(JSON.stringify(elementos));
        }

        function guardarHistorial() {
            historial.push(clonarElementos());

            if (historial.length > 50) {
                historial.shift();
            }

            historialRehacer = [];
        }

        function restaurarElementos(lista) {
            elementos = lista.map(normalizarElemento);
            indiceSeleccionado = elementos.length ? Math.min(indiceSeleccionado ?? 0, elementos.length - 1) : null;
            renderizarLienzo();
        }

        function deshacerCambio() {
            if (!historial.length) {
                return;
            }

            historialRehacer.push(clonarElementos());
            restaurarElementos(historial.pop());
        }

        function rehacerCambio() {
            if (!historialRehacer.length) {
                return;
            }

            historial.push(clonarElementos());
            restaurarElementos(historialRehacer.pop());
        }

        function marcadorDesdeCodigo(codigo) {
            const llave = String(codigo || 'campo')
                .replace(/[^a-zA-Z0-9]+/g, '_')
                .replace(/^_|_$/g, '');

            return '{' + '{' + llave + '}' + '}';
        }

        function nombreElemento(elemento) {
            if (elemento.tipo_elemento === 'TEXTO') {
                return 'Texto con marcadores';
            }

            if (elemento.tipo_elemento === 'FIRMA') {
                return 'Firma';
            }

            if (elemento.tipo_elemento === 'QR') {
                return 'QR de verificación';
            }

            return campoPorCodigo(elemento.codigo_elemento)?.nombre || elemento.codigo_elemento || 'Campo del sistema';
        }

        function columnasPorDefecto(codigo) {
            const columnas = {
                'producto.tabla': [
                    { codigo_campo: 'producto.nombres_comerciales', titulo_columna: 'Producto', ancho: 35, estado: 'ACTIVO' },
                    { codigo_campo: 'registro.codigos', titulo_columna: 'Registro', ancho: 30, estado: 'ACTIVO' },
                    { codigo_campo: 'presentacion.descripciones', titulo_columna: 'Presentación', ancho: 35, estado: 'ACTIVO' },
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
            };

            return columnas[codigo] || [];
        }

        function anchoInicialPorTipo(tipo) {
            if (tipo === 'TABLA') {
                return 520;
            }

            if (tipo === 'QR') {
                return 86;
            }

            if (tipo === 'FIRMA') {
                return 260;
            }

            if (tipo === 'IMAGEN') {
                return 160;
            }

            return tipo === 'TEXTO' ? 430 : 190;
        }

        function altoInicialPorTipo(tipo) {
            if (tipo === 'TABLA') {
                return 118;
            }

            if (tipo === 'QR') {
                return 86;
            }

            if (tipo === 'FIRMA') {
                return 58;
            }

            if (tipo === 'IMAGEN') {
                return 90;
            }

            return tipo === 'TEXTO' ? 88 : 34;
        }

        function normalizarElemento(item) {
            const tipo = item.tipo_elemento || (String(item.codigo_elemento || '').endsWith('.tabla') ? 'TABLA' : 'CAMPO');
            const textoInicial = ['TABLA', 'QR'].includes(tipo)
                ? ''
                : (item.codigo_elemento ? marcadorDesdeCodigo(item.codigo_elemento) : '');
            const usaPadding = !['IMAGEN', 'TABLA', 'QR'].includes(tipo);

            return {
                tipo_elemento: tipo,
                codigo_elemento: item.codigo_elemento || null,
                texto_fijo: item.texto_fijo || textoInicial,
                pagina: Number(item.pagina || 1),
                posicion_x: Number(item.posicion_x ?? 28),
                posicion_y: Number(item.posicion_y ?? 28),
                ancho: Number(item.ancho || anchoInicialPorTipo(tipo)),
                alto: Number(item.alto || altoInicialPorTipo(tipo)),
                tamano_letra: Number(item.tamano_letra || 12),
                alineacion: normalizarAlineacion(item.alineacion),
                padding_x: Number(item.padding_x ?? (usaPadding ? 7 : 0)),
                padding_y: Number(item.padding_y ?? (usaPadding ? 5 : 0)),
                interlineado: Number(item.interlineado ?? 1.25),
                negrita: Boolean(item.negrita),
                cursiva: Boolean(item.cursiva),
                subrayado: Boolean(item.subrayado),
                color_texto: item.color_texto || '#0f172a',
                tipo_letra: item.tipo_letra || 'Arial',
                z_index: Number(item.z_index || 3),
                estado: item.estado || 'ACTIVO',
                columnas: Array.isArray(item.columnas) && item.columnas.length ? item.columnas : columnasPorDefecto(item.codigo_elemento),
            };
        }

        function normalizarAlineacion(valor) {
            const alineacion = String(valor || 'IZQUIERDA').toUpperCase();

            return ['IZQUIERDA', 'CENTRO', 'DERECHA', 'JUSTIFICADO'].includes(alineacion)
                ? alineacion
                : 'IZQUIERDA';
        }

        function textoVisible(elemento) {
            if (elemento.tipo_elemento === 'TEXTO') {
                return elemento.texto_fijo || 'Escriba el texto del certificado aquí...';
            }

            if (elemento.tipo_elemento === 'FIRMA') {
                return elemento.texto_fijo || textoFirmaPlantilla(elemento.codigo_elemento);
            }

            if (elemento.tipo_elemento === 'QR') {
                return 'QR';
            }

            if (elemento.tipo_elemento === 'IMAGEN') {
                return elemento.texto_fijo || '';
            }

            return elemento.texto_fijo || marcadorDesdeCodigo(elemento.codigo_elemento);
        }

        function textoFirmaPlantilla(codigo) {
            if (codigo === 'firma.director') {
                return [
                    marcadorDesdeCodigo('funcionario.director_nombre'),
                    marcadorDesdeCodigo('funcionario.director_cargo'),
                ].join('\n');
            }

            return [
                marcadorDesdeCodigo('funcionario.responsable_area_nombre'),
                marcadorDesdeCodigo('funcionario.responsable_area_cargo'),
            ].join('\n');
        }

        function columnasTabla(elemento) {
            if (!Array.isArray(elemento.columnas) || !elemento.columnas.length) {
                elemento.columnas = columnasPorDefecto(elemento.codigo_elemento);
            }

            if (!elemento.columnas.length) {
                elemento.columnas = [
                    { codigo_campo: 'tabla.columna_1', titulo_columna: 'Columna 1', ancho: 50, estado: 'ACTIVO' },
                    { codigo_campo: 'tabla.columna_2', titulo_columna: 'Columna 2', ancho: 50, estado: 'ACTIVO' },
                ];
            }

            return elemento.columnas;
        }

        function filasTabla(elemento) {
            const columnas = columnasTabla(elemento);
            let filas = [];

            try {
                const datos = JSON.parse(elemento.texto_fijo || '{}');
                filas = Array.isArray(datos.filas) ? datos.filas : [];
            } catch (error) {
                filas = [];
            }

            if (!filas.length) {
                filas = [
                    columnas.map((columna) => columna.codigo_campo ? marcadorDesdeCodigo(columna.codigo_campo) : ''),
                ];
            }

            return filas.map((fila) => columnas.map((_, index) => fila?.[index] ?? ''));
        }

        function guardarFilasTabla(elemento, filas) {
            elemento.texto_fijo = JSON.stringify({ filas });
        }

        function ajustarFilasTabla(elemento, cantidad) {
            const columnas = columnasTabla(elemento);
            const totalFilas = Math.max(1, Math.min(30, Number(cantidad || 1)));
            const filas = filasTabla(elemento).slice(0, totalFilas);

            while (filas.length < totalFilas) {
                filas.push(columnas.map(() => ''));
            }

            guardarFilasTabla(elemento, filas);
        }

        function ajustarColumnasTabla(elemento, cantidad) {
            const totalColumnas = Math.max(1, Math.min(12, Number(cantidad || 1)));
            const columnas = columnasTabla(elemento).slice(0, totalColumnas);

            while (columnas.length < totalColumnas) {
                const numero = columnas.length + 1;
                columnas.push({
                    codigo_campo: `tabla.columna_${numero}`,
                    titulo_columna: `Columna ${numero}`,
                    ancho: Math.round(100 / totalColumnas),
                    estado: 'ACTIVO',
                });
            }

            elemento.columnas = columnas.map((columna) => ({
                ...columna,
                ancho: Number(columna.ancho || Math.round(100 / totalColumnas)),
            }));

            const filas = filasTabla(elemento).map((fila) => elemento.columnas.map((_, index) => fila[index] ?? ''));
            guardarFilasTabla(elemento, filas);
        }

        function htmlTablaPlantilla(elemento) {
            const columnas = columnasTabla(elemento);
            const filas = filasTabla(elemento);

            if (!columnas.length) {
                return '<table class="plantilla-word-table"><tbody><tr><td>Tabla</td><td>Dato</td></tr><tr><td>Fila 1</td><td>Valor</td></tr></tbody></table>';
            }

            const encabezado = columnas
                .map((columna) => `<th style="width:${Number(columna.ancho || 25)}%">${escaparHtml(columna.titulo_columna || columna.codigo_campo || 'Columna')}</th>`)
                .join('');
            const cuerpo = filas
                .map((fila) => `<tr>${fila.map((celda) => `<td>${textoConMarcadores(celda)}</td>`).join('')}</tr>`)
                .join('');

            return `<table class="plantilla-word-table"><thead><tr>${encabezado}</tr></thead><tbody>${cuerpo}</tbody></table>`;
        }

        function htmlQrPlantilla(elemento) {
            if (String(elemento.texto_fijo || '').startsWith('data:image')) {
                return `<img src="${escaparHtml(elemento.texto_fijo)}" alt="QR de plantilla">`;
            }

            return `<span>${escaparHtml(campoPorCodigo(elemento.codigo_elemento)?.nombre || 'QR')}</span>`;
        }

        function contenidoElementoPlantilla(elemento) {
            if (elemento.tipo_elemento === 'IMAGEN') {
                return `<img src="${escaparHtml(textoVisible(elemento))}" alt="Imagen de plantilla">`;
            }

            if (elemento.tipo_elemento === 'TABLA') {
                return htmlTablaPlantilla(elemento);
            }

            if (elemento.tipo_elemento === 'QR') {
                return htmlQrPlantilla(elemento);
            }

            return textoConMarcadores(textoVisible(elemento));
        }

        function textoConMarcadores(texto) {
            return escaparHtml(texto || '');
        }

        function escaparHtml(texto) {
            return String(texto)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function claseAlineacion(elemento) {
            const alineacion = normalizarAlineacion(elemento.alineacion);

            if (alineacion === 'CENTRO') {
                return 'is-center';
            }

            if (alineacion === 'DERECHA') {
                return 'is-right';
            }

            if (alineacion === 'JUSTIFICADO') {
                return 'is-justify';
            }

            return '';
        }

        function actualizarInput() {
            const elementosParaGuardar = elementos.map(normalizarElemento);

            if (inputElementos) {
                inputElementos.value = JSON.stringify(elementosParaGuardar);
            }

            if (contadorElementos) {
                contadorElementos.textContent = String(elementosParaGuardar.length);
            }
        }

        function renderizarLienzo() {
            if (!lienzo) {
                return;
            }

            lienzo.querySelectorAll('[data-plantilla-elemento]').forEach((nodo) => nodo.remove());
            actualizarFormaLienzo();

            elementos.forEach((elemento, index) => {
                const nodo = document.createElement('button');
                nodo.type = 'button';
                nodo.className = [
                    'plantilla-element',
                    elemento.tipo_elemento === 'TEXTO' ? 'is-texto' : '',
                    elemento.tipo_elemento === 'TABLA' ? 'is-tabla' : '',
                    elemento.tipo_elemento === 'FIRMA' ? 'is-firma' : '',
                    elemento.tipo_elemento === 'QR' ? 'is-qr' : '',
                    elemento.tipo_elemento === 'IMAGEN' ? 'is-imagen' : '',
                    claseAlineacion(elemento),
                    index === indiceSeleccionado ? 'is-selected' : '',
                ].filter(Boolean).join(' ');
                nodo.dataset.plantillaElemento = String(index);
                nodo.style.left = `${elemento.posicion_x}px`;
                nodo.style.top = `${elemento.posicion_y}px`;
                nodo.style.width = `${elemento.ancho}px`;
                nodo.style.height = `${elemento.alto}px`;
                nodo.style.fontSize = `${elemento.tamano_letra}px`;
                nodo.style.fontWeight = elemento.negrita ? '900' : '700';
                nodo.style.fontStyle = elemento.cursiva ? 'italic' : 'normal';
                nodo.style.textDecoration = elemento.subrayado ? 'underline' : 'none';
                nodo.style.color = elemento.color_texto || '#0f172a';
                nodo.style.fontFamily = elemento.tipo_letra || 'Arial';
                nodo.style.lineHeight = String(elemento.interlineado || 1.25);
                nodo.style.padding = `${elemento.padding_y}px ${elemento.padding_x}px`;
                nodo.style.zIndex = String(elemento.z_index || 3);
                nodo.innerHTML = contenidoElementoPlantilla(elemento);

                if (index === indiceSeleccionado) {
                    const controlTamano = document.createElement('span');
                    controlTamano.className = 'plantilla-resize-handle';
                    controlTamano.dataset.resizeHandle = '1';
                    nodo.appendChild(controlTamano);
                }

                lienzo.appendChild(nodo);
            });

            actualizarInput();
            renderizarPropiedades();
            renderizarCapas();
        }

        function actualizarNodoSeleccionado() {
            const elemento = elementoSeleccionado();
            const nodo = Number.isInteger(indiceSeleccionado)
                ? lienzo?.querySelector(`[data-plantilla-elemento="${indiceSeleccionado}"]`)
                : null;

            if (!elemento || !nodo) {
                return;
            }

            nodo.className = [
                'plantilla-element',
                elemento.tipo_elemento === 'TEXTO' ? 'is-texto' : '',
                elemento.tipo_elemento === 'TABLA' ? 'is-tabla' : '',
                elemento.tipo_elemento === 'FIRMA' ? 'is-firma' : '',
                elemento.tipo_elemento === 'QR' ? 'is-qr' : '',
                elemento.tipo_elemento === 'IMAGEN' ? 'is-imagen' : '',
                claseAlineacion(elemento),
                'is-selected',
            ].filter(Boolean).join(' ');
            nodo.style.left = `${elemento.posicion_x}px`;
            nodo.style.top = `${elemento.posicion_y}px`;
            nodo.style.width = `${elemento.ancho}px`;
            nodo.style.height = `${elemento.alto}px`;
            nodo.style.fontSize = `${elemento.tamano_letra}px`;
            nodo.style.fontWeight = elemento.negrita ? '900' : '700';
            nodo.style.fontStyle = elemento.cursiva ? 'italic' : 'normal';
            nodo.style.textDecoration = elemento.subrayado ? 'underline' : 'none';
            nodo.style.color = elemento.color_texto || '#0f172a';
            nodo.style.fontFamily = elemento.tipo_letra || 'Arial';
            nodo.style.lineHeight = String(elemento.interlineado || 1.25);
            nodo.style.padding = `${elemento.padding_y}px ${elemento.padding_x}px`;
            nodo.style.zIndex = String(elemento.z_index || 3);
            nodo.innerHTML = contenidoElementoPlantilla(elemento);
            const controlTamano = document.createElement('span');
            controlTamano.className = 'plantilla-resize-handle';
            controlTamano.dataset.resizeHandle = '1';
            nodo.appendChild(controlTamano);
            actualizarInput();
            renderizarCapas();
        }

        function actualizarFormaLienzo() {
            if (!lienzo) {
                return;
            }

            const esOficio = selectorTamano?.value === 'OFICIO';
            const esHorizontal = selectorOrientacion?.value === 'HORIZONTAL';
            const medidas = esOficio
                ? { ancho: 816, alto: 1248 }
                : { ancho: 816, alto: 1056 };
            const anchoFinal = esHorizontal ? medidas.alto : medidas.ancho;
            const altoFinal = esHorizontal ? medidas.ancho : medidas.alto;

            lienzo.classList.toggle('is-oficio', esOficio);
            lienzo.classList.toggle('is-horizontal', esHorizontal);
            lienzo.classList.toggle('is-grid', cuadriculaActiva);
            lienzo.classList.toggle('is-work-white', selectorFondoTrabajo?.value === 'BLANCO');
            lienzo.style.width = `${anchoFinal}px`;
            lienzo.style.height = `${altoFinal}px`;
            lienzo.style.zoom = zoomActual;

            if (zoomValor) {
                zoomValor.textContent = `${Math.round(zoomActual * 100)}%`;
            }
        }

        function actualizarPapelPlantilla() {
            actualizarFormaLienzo();
            aplicarFondoTrabajo();
            actualizarInput();
        }

        function ajusteFondoCss() {
            const ajuste = selectorAjusteFondo?.value || 'ESTIRAR';

            if (ajuste === 'CONTENER') {
                return 'contain';
            }

            if (ajuste === 'CUBRIR') {
                return 'cover';
            }

            return 'fill';
        }

        function seleccionarElemento(index) {
            indiceSeleccionado = Number.isInteger(index) ? index : null;
            renderizarLienzo();
        }

        function elementoSeleccionado() {
            return Number.isInteger(indiceSeleccionado) ? elementos[indiceSeleccionado] : null;
        }

        function renderizarCapas() {
            if (!panelCapas) {
                return;
            }

            if (!elementos.length) {
                panelCapas.innerHTML = '<div class="plantilla-editor-ayuda">Todavía no hay bloques en el lienzo.</div>';
                return;
            }

            panelCapas.innerHTML = elementos.map((elemento, index) => `
                <button type="button" class="plantilla-layer-item ${index === indiceSeleccionado ? 'is-active' : ''}" data-capa-index="${index}">
                    <span>${index + 1}</span>
                    <div>
                        <strong>${escaparHtml(nombreElemento(elemento))}</strong>
                        <small>${escaparHtml(elemento.codigo_elemento || elemento.tipo_elemento)}</small>
                    </div>
                </button>
            `).join('');
        }

        function eliminarElementoSeleccionado() {
            if (!Number.isInteger(indiceSeleccionado)) {
                return;
            }

            guardarHistorial();
            elementos.splice(indiceSeleccionado, 1);
            indiceSeleccionado = elementos.length ? Math.max(0, indiceSeleccionado - 1) : null;
            renderizarLienzo();
        }

        function duplicarElementoSeleccionado() {
            const elemento = elementoSeleccionado();

            if (!elemento) {
                return;
            }

            guardarHistorial();
            const copia = normalizarElemento({
                ...JSON.parse(JSON.stringify(elemento)),
                posicion_x: Number(elemento.posicion_x || 0) + 18,
                posicion_y: Number(elemento.posicion_y || 0) + 18,
            });

            elementos.push(copia);
            seleccionarElemento(elementos.length - 1);
        }

        function moverCapaSeleccionada(direccion) {
            if (!Number.isInteger(indiceSeleccionado)) {
                return;
            }

            guardarHistorial();
            const [elemento] = elementos.splice(indiceSeleccionado, 1);

            if (direccion === 'frente') {
                elementos.push(elemento);
                indiceSeleccionado = elementos.length - 1;
            } else {
                elementos.unshift(elemento);
                indiceSeleccionado = 0;
            }

            renderizarLienzo();
        }

        function cambiarZoom(accion) {
            if (accion === 'mas') {
                zoomActual = Math.min(1.6, zoomActual + 0.1);
            }

            if (accion === 'menos') {
                zoomActual = Math.max(0.55, zoomActual - 0.1);
            }

            actualizarFormaLienzo();
        }

        function htmlEditorTabla(elemento) {
            const columnas = columnasTabla(elemento);
            const filas = filasTabla(elemento);
            const ejemploCelda = marcadorDesdeCodigo('campo');
            const ejemploBeneficiario = marcadorDesdeCodigo('beneficiario.nombre');
            const encabezados = columnas.map((columna, index) => `
                <input
                    class="plantilla-table-editor-input is-header"
                    type="text"
                    data-tabla-encabezado="${index}"
                    value="${escaparHtml(columna.titulo_columna || `Columna ${index + 1}`)}"
                    title="Encabezado de columna">
            `).join('');
            const celdas = filas.map((fila, filaIndex) => fila.map((celda, columnaIndex) => `
                <input
                    class="plantilla-table-editor-input"
                    type="text"
                    data-tabla-celda="1"
                    data-fila="${filaIndex}"
                    data-columna="${columnaIndex}"
                    value="${escaparHtml(celda)}"
                    placeholder="Texto o ${escaparHtml(ejemploCelda)}">
            `).join('')).join('');

            return `
                <div class="plantilla-prop-full plantilla-table-editor">
                    <div class="plantilla-table-editor-head">
                        <label>
                            <span class="plantilla-prop-label">Filas</span>
                            <input class="plantilla-prop-input" type="number" min="1" max="30" data-tabla-filas value="${filas.length}">
                        </label>
                        <label>
                            <span class="plantilla-prop-label">Columnas</span>
                            <input class="plantilla-prop-input" type="number" min="1" max="12" data-tabla-columnas value="${columnas.length}">
                        </label>
                    </div>
                    <div class="plantilla-table-editor-note">
                        Puede escribir texto normal o marcadores como ${escaparHtml(ejemploBeneficiario)} dentro de una celda.
                    </div>
                    <div class="plantilla-table-editor-scroll">
                        <div class="plantilla-table-editor-grid" style="grid-template-columns: repeat(${columnas.length}, minmax(120px, 1fr));">
                            ${encabezados}${celdas}
                        </div>
                    </div>
                </div>
            `;
        }

        function htmlPropiedadesQr(elemento) {
            const camposQr = campos.filter((campo) => String(campo.codigo || '').startsWith('qr.'));
            const opcionesQr = (camposQr.length ? camposQr : [{ codigo: 'qr.verificacion', nombre: 'QR de verificación' }])
                .map((campo) => `
                    <option value="${escaparHtml(campo.codigo)}" ${campo.codigo === elemento.codigo_elemento ? 'selected' : ''}>
                        ${escaparHtml(campo.nombre)}
                    </option>
                `).join('');
            const modoActual = String(elemento.texto_fijo || '').startsWith('data:image')
                ? 'Imagen cargada'
                : 'Dato del sistema';

            return `
                <div class="plantilla-prop-full plantilla-qr-editor">
                    <span class="plantilla-prop-label">Origen del QR</span>
                    <select class="plantilla-prop-select" data-qr-codigo>
                        ${opcionesQr}
                    </select>
                    <div class="plantilla-qr-actions">
                        <button type="button" class="plantilla-action-btn is-primary" data-qr-usar-campo>
                            Usar dato del sistema
                        </button>
                        <button type="button" class="plantilla-action-btn" data-qr-imagen>
                            Seleccionar imagen
                        </button>
                    </div>
                    <div class="plantilla-table-editor-note">Actual: ${modoActual}</div>
                </div>
            `;
        }

        function renderizarPropiedades() {
            if (!panelPropiedades) {
                return;
            }

            const elemento = elementoSeleccionado();

            if (!elemento) {
                panelPropiedades.innerHTML = `
                    <div class="plantilla-editor-ayuda">
                        Seleccione un bloque del lienzo para editar su texto, tamaño, posición y formato.
                    </div>
                `;
                return;
            }

            panelPropiedades.innerHTML = `
                <div class="plantilla-prop-grid">
                    <div class="plantilla-prop-full">
                        <div class="text-xs font-black uppercase text-emerald-700">${nombreElemento(elemento)}</div>
                        <div class="mt-1 text-xs font-semibold text-slate-500">${elemento.codigo_elemento || elemento.tipo_elemento}</div>
                    </div>

                    <label>
                        <span class="plantilla-prop-label">Posición X</span>
                        <input class="plantilla-prop-input" type="number" data-prop="posicion_x" value="${elemento.posicion_x}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Posición Y</span>
                        <input class="plantilla-prop-input" type="number" data-prop="posicion_y" value="${elemento.posicion_y}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Ancho</span>
                        <input class="plantilla-prop-input" type="number" min="20" data-prop="ancho" value="${elemento.ancho}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Alto</span>
                        <input class="plantilla-prop-input" type="number" min="20" data-prop="alto" value="${elemento.alto}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Tamaño</span>
                        <input class="plantilla-prop-input" type="number" min="6" max="72" data-prop="tamano_letra" value="${elemento.tamano_letra}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Espacio X</span>
                        <input class="plantilla-prop-input" type="number" min="0" max="60" data-prop="padding_x" value="${elemento.padding_x}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Espacio Y</span>
                        <input class="plantilla-prop-input" type="number" min="0" max="60" data-prop="padding_y" value="${elemento.padding_y}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Interlineado</span>
                        <input class="plantilla-prop-input" type="number" min="0.8" max="3" step="0.05" data-prop="interlineado" value="${elemento.interlineado}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Nivel</span>
                        <input class="plantilla-prop-input" type="number" min="1" max="99" data-prop="z_index" value="${elemento.z_index}">
                    </label>
                    <label>
                        <span class="plantilla-prop-label">Color</span>
                        <input class="plantilla-prop-input" type="color" data-prop="color_texto" value="${elemento.color_texto || '#0f172a'}">
                    </label>
                    <label class="plantilla-prop-full">
                        <span class="plantilla-prop-label">Tipo de letra</span>
                        <select class="plantilla-prop-select" data-prop="tipo_letra">
                            <option value="Arial" ${elemento.tipo_letra === 'Arial' ? 'selected' : ''}>Arial</option>
                            <option value="Calibri" ${elemento.tipo_letra === 'Calibri' ? 'selected' : ''}>Calibri</option>
                            <option value="Times New Roman" ${elemento.tipo_letra === 'Times New Roman' ? 'selected' : ''}>Times New Roman</option>
                            <option value="Georgia" ${elemento.tipo_letra === 'Georgia' ? 'selected' : ''}>Georgia</option>
                            <option value="Verdana" ${elemento.tipo_letra === 'Verdana' ? 'selected' : ''}>Verdana</option>
                            <option value="Tahoma" ${elemento.tipo_letra === 'Tahoma' ? 'selected' : ''}>Tahoma</option>
                            <option value="Courier New" ${elemento.tipo_letra === 'Courier New' ? 'selected' : ''}>Courier New</option>
                        </select>
                    </label>
                    <label class="plantilla-prop-full">
                        <span class="plantilla-prop-label">Alineación</span>
                        <select class="plantilla-prop-select" data-prop="alineacion">
                            <option value="IZQUIERDA" ${elemento.alineacion === 'IZQUIERDA' ? 'selected' : ''}>Izquierda</option>
                            <option value="CENTRO" ${elemento.alineacion === 'CENTRO' ? 'selected' : ''}>Centro</option>
                            <option value="DERECHA" ${elemento.alineacion === 'DERECHA' ? 'selected' : ''}>Derecha</option>
                            <option value="JUSTIFICADO" ${elemento.alineacion === 'JUSTIFICADO' ? 'selected' : ''}>Justificado</option>
                        </select>
                    </label>
                    <div class="plantilla-prop-full">
                        <span class="plantilla-prop-label">Formato</span>
                        <div class="plantilla-format-row">
                            <button type="button" class="plantilla-format-btn ${elemento.negrita ? 'is-active' : ''}" data-toggle-formato="negrita">B</button>
                            <button type="button" class="plantilla-format-btn ${elemento.cursiva ? 'is-active' : ''}" data-toggle-formato="cursiva">I</button>
                            <button type="button" class="plantilla-format-btn ${elemento.subrayado ? 'is-active' : ''}" data-toggle-formato="subrayado">U</button>
                        </div>
                    </div>
                    ${elemento.tipo_elemento === 'TABLA' ? htmlEditorTabla(elemento) : ''}
                    ${elemento.tipo_elemento === 'QR' ? htmlPropiedadesQr(elemento) : ''}
                    ${elemento.tipo_elemento !== 'TABLA' && elemento.tipo_elemento !== 'QR' ? `
                    <label class="plantilla-prop-full">
                        <span class="plantilla-prop-label">Texto del bloque</span>
                        <textarea class="plantilla-prop-textarea" data-prop-texto>${escaparHtml(elemento.texto_fijo || '')}</textarea>
                    </label>
                    ` : ''}
                    <div class="plantilla-prop-full plantilla-format-row">
                        <button type="button" class="plantilla-action-btn is-primary" data-insertar-marcador>
                            Insertar campo seleccionado
                        </button>
                        <button type="button" class="plantilla-action-btn is-danger" data-eliminar-elemento>
                            Quitar bloque
                        </button>
                    </div>
                </div>
            `;
        }

        function seleccionarImagen(callback) {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'image/png,image/jpeg,image/webp';

            input.addEventListener('change', function () {
                const archivo = this.files && this.files[0];

                if (!archivo) {
                    return;
                }

                const lector = new FileReader();
                lector.onload = () => callback(String(lector.result || ''));
                lector.readAsDataURL(archivo);
            });

            input.click();
        }

        function agregarElemento(tipo, codigo = null, datos = {}) {
            guardarHistorial();
            const textoBase = tipo === 'FIRMA'
                ? textoFirmaPlantilla(codigo)
                : (tipo === 'TEXTO'
                    ? textoInicialCertificado()
                    : (['TABLA', 'QR'].includes(tipo) ? '' : (codigo ? marcadorDesdeCodigo(codigo) : '')));

            const base = normalizarElemento({
                tipo_elemento: tipo,
                codigo_elemento: codigo,
                texto_fijo: datos.texto_fijo || textoBase,
                posicion_x: 36 + (elementos.length % 2) * 220,
                posicion_y: 48 + Math.floor(elementos.length / 2) * 72,
                ancho: anchoInicialPorTipo(tipo),
                alto: altoInicialPorTipo(tipo),
                tamano_letra: tipo === 'TEXTO' ? 12 : 11,
            });

            if (tipo === 'TABLA') {
                guardarFilasTabla(base, filasTabla(base));
            }

            elementos.push(base);
            seleccionarElemento(elementos.length - 1);
        }

        function insertarMarcadorEnTexto(codigo) {
            const elemento = elementoSeleccionado();
            const marcador = marcadorDesdeCodigo(codigo);

            if (!elemento || elemento.tipo_elemento !== 'TEXTO') {
                agregarElemento('CAMPO', codigo);
                return;
            }

            guardarHistorial();
            elemento.texto_fijo = `${elemento.texto_fijo || ''} ${marcador}`.trim();
            renderizarLienzo();
        }

        function actualizarResumenTipo() {
            const tipo = tipoSeleccionado();

            if (!resumenTipo) {
                return;
            }

            if (!tipo) {
                resumenTipo.textContent = 'Seleccione un tipo de certificado.';
                return;
            }

            resumenTipo.innerHTML = `
                <div class="font-bold text-emerald-800">${tipo.nombre}</div>
                <div class="mt-1 text-sm text-slate-600">${tipo.area || 'Sin área asignada'}</div>
                <span class="plantilla-chip">${tipo.estado || 'ACTIVO'}</span>
            `;

        }

        function filtrarCampos() {
            const texto = (buscadorCampo?.value || '').toLowerCase().trim();

            campos.forEach((campo) => {
                const coincide = !texto
                    || campo.codigo.toLowerCase().includes(texto)
                    || campo.nombre.toLowerCase().includes(texto);

                campo.boton.style.display = coincide ? '' : 'none';
            });
        }

        function mostrarArchivoSeleccionado(nombre, url, esImagen = false) {
            urlArchivoPlantilla = url || '';

            if (fondoNombre) {
                fondoNombre.textContent = nombre || 'Sin archivo seleccionado';
            }

            if (fondoVer) {
                fondoVer.disabled = !urlArchivoPlantilla;
            }

            if (fondoQuitar) {
                fondoQuitar.disabled = !urlArchivoPlantilla;
            }

            if (!fondoPreview || !fondoPlaceholder) {
                return;
            }

            if (urlArchivoPlantilla && esImagen) {
                fondoPreview.onload = function () {
                    mostrarMedidasImagen(this.naturalWidth, this.naturalHeight);
                };
                fondoPreview.src = urlArchivoPlantilla;
                fondoPreview.style.display = 'block';
                fondoPlaceholder.style.display = 'none';
                aplicarFondoTrabajo();
                return;
            }

            fondoPreview.removeAttribute('src');
            fondoPreview.style.display = 'none';
            fondoPlaceholder.style.display = 'grid';
            mostrarMedidasImagen(null, null);
            aplicarFondoTrabajo();
        }

        function aplicarFondoTrabajo() {
            if (!lienzo || !fondoPreview || !fondoPlaceholder) {
                return;
            }

            const usarHojaBlanca = selectorFondoTrabajo?.value === 'BLANCO';
            lienzo.classList.toggle('is-work-white', usarHojaBlanca);
            fondoPreview.style.objectFit = ajusteFondoCss();

            if (usarHojaBlanca) {
                fondoPreview.style.display = 'none';
                fondoPlaceholder.style.display = 'none';
                return;
            }

            if (urlArchivoPlantilla && fondoPreview.getAttribute('src')) {
                fondoPreview.style.display = 'block';
                fondoPlaceholder.style.display = 'none';
                return;
            }

            fondoPreview.style.display = 'none';
            fondoPlaceholder.style.display = 'grid';
        }

        function mostrarMedidasImagen(ancho, alto) {
            if (!fondoMedidas) {
                return;
            }

            if (!ancho || !alto) {
                fondoMedidas.textContent = 'Sin imagen cargada para medir.';
                return;
            }

            const proporcion = (ancho / alto).toFixed(4);
            fondoMedidas.innerHTML = `
                Tamaño real de la imagen:
                <strong>${ancho} x ${alto} px</strong>
                · Proporción: <strong>${proporcion}</strong>.
                Use una imagen con la misma proporción del papel para evitar diferencias al imprimir.
            `;
        }

        function limpiarArchivoPlantilla() {
            if (fondoInput) {
                fondoInput.value = '';
            }

            if (fondoQuitarInput) {
                fondoQuitarInput.value = '1';
            }

            mostrarArchivoSeleccionado('Sin archivo seleccionado', '', false);
        }

        function textoInicialCertificado() {
            const marcadorBeneficiario = marcadorDesdeCodigo('beneficiario.nombre');
            const marcadorDocumento = marcadorDesdeCodigo('beneficiario.documento');
            const marcadorProductos = marcadorDesdeCodigo('producto.nombres_comerciales');

            return `Se certifica que ${marcadorBeneficiario}, con CI/NIT ${marcadorDocumento}, cuenta con ${marcadorProductos}.`;
        }

        document.querySelectorAll('[data-plantilla-tool]').forEach((boton) => {
            boton.addEventListener('click', function () {
                const accion = this.dataset.plantillaTool;

                if (accion === 'texto') {
                    agregarElemento('TEXTO');
                }

                if (accion === 'tabla') {
                    agregarElemento('TABLA', 'producto.tabla');
                }

                if (accion === 'firma') {
                    agregarElemento('FIRMA', 'firma.responsable_area');
                }

                if (accion === 'qr') {
                    agregarElemento('QR', 'qr.verificacion');
                }

                if (accion === 'imagen') {
                    seleccionarImagen((imagen) => {
                        agregarElemento('IMAGEN', null, { texto_fijo: imagen });
                    });
                }

                if (accion === 'deshacer') {
                    deshacerCambio();
                }

                if (accion === 'rehacer') {
                    rehacerCambio();
                }

                if (accion === 'duplicar') {
                    duplicarElementoSeleccionado();
                }

                if (accion === 'eliminar') {
                    eliminarElementoSeleccionado();
                }

                if (accion === 'frente' || accion === 'fondo') {
                    moverCapaSeleccionada(accion);
                }

                if (accion === 'grid') {
                    cuadriculaActiva = !cuadriculaActiva;
                    actualizarFormaLienzo();
                }
            });
        });

        document.querySelectorAll('[data-plantilla-campo]').forEach((boton) => {
            boton.addEventListener('click', () => {
                ultimoCampoSeleccionado = boton.dataset.codigo;
                insertarMarcadorEnTexto(boton.dataset.codigo);
            });
            boton.draggable = true;
            boton.addEventListener('dragstart', (event) => {
                ultimoCampoSeleccionado = boton.dataset.codigo;
                event.dataTransfer.setData('text/plain', boton.dataset.codigo);
            });
        });

        lienzo?.addEventListener('dragover', (event) => event.preventDefault());
        lienzo?.addEventListener('drop', function (event) {
            event.preventDefault();
            const codigo = event.dataTransfer.getData('text/plain');

            if (!codigo) {
                return;
            }

            const caja = lienzo.getBoundingClientRect();
            agregarElemento('CAMPO', codigo);
            const elemento = elementos[elementos.length - 1];
            elemento.posicion_x = Math.round((event.clientX - caja.left) / zoomActual);
            elemento.posicion_y = Math.round((event.clientY - caja.top) / zoomActual);
            renderizarLienzo();
        });

        lienzo?.addEventListener('mousedown', function (event) {
            const nodo = event.target.closest('[data-plantilla-elemento]');

            if (!nodo || !lienzo) {
                return;
            }

            event.preventDefault();

            const index = Number(nodo.dataset.plantillaElemento);
            indiceSeleccionado = index;
            lienzo.querySelectorAll('[data-plantilla-elemento]').forEach((item) => {
                item.classList.toggle('is-selected', item === nodo);
            });

            if (!nodo.querySelector('[data-resize-handle]')) {
                const controlTamano = document.createElement('span');
                controlTamano.className = 'plantilla-resize-handle';
                controlTamano.dataset.resizeHandle = '1';
                nodo.appendChild(controlTamano);
            }

            renderizarPropiedades();
            renderizarCapas();
            guardarHistorial();

            const elemento = elementos[index];
            const estaRedimensionando = Boolean(event.target.closest('[data-resize-handle]'));
            const inicioX = event.clientX;
            const inicioY = event.clientY;
            const posicionInicialX = Number(elemento.posicion_x || 0);
            const posicionInicialY = Number(elemento.posicion_y || 0);
            const anchoInicial = Number(elemento.ancho || nodo.offsetWidth);
            const altoInicial = Number(elemento.alto || nodo.offsetHeight);

            function mover(eventMove) {
                eventMove.preventDefault();

                if (estaRedimensionando) {
                    const nuevoAncho = Math.max(28, anchoInicial + (eventMove.clientX - inicioX));
                    const nuevoAlto = Math.max(24, altoInicial + (eventMove.clientY - inicioY));

                    elemento.ancho = Math.round(nuevoAncho);
                    elemento.alto = Math.round(nuevoAlto);
                    nodo.style.width = `${elemento.ancho}px`;
                    nodo.style.minHeight = `${elemento.alto}px`;
                    actualizarInput();
                    renderizarPropiedades();
                    return;
                }

                const maxX = Math.max(0, lienzo.clientWidth - nodo.offsetWidth);
                const maxY = Math.max(0, lienzo.clientHeight - nodo.offsetHeight);
                const nuevaX = posicionInicialX + (eventMove.clientX - inicioX);
                const nuevaY = posicionInicialY + (eventMove.clientY - inicioY);

                elemento.posicion_x = Math.round(Math.min(Math.max(0, nuevaX), maxX));
                elemento.posicion_y = Math.round(Math.min(Math.max(0, nuevaY), maxY));
                nodo.style.left = `${elemento.posicion_x}px`;
                nodo.style.top = `${elemento.posicion_y}px`;
                actualizarInput();
                renderizarPropiedades();
            }

            function soltar() {
                document.removeEventListener('mousemove', mover);
                document.removeEventListener('mouseup', soltar);
                renderizarLienzo();
            }

            document.addEventListener('mousemove', mover);
            document.addEventListener('mouseup', soltar);
        });

        function actualizarPropiedadDesdePanel(event) {
            const elemento = elementoSeleccionado();

            if (!elemento) {
                return;
            }

            if (event.target.matches('[data-tabla-filas]')) {
                ajustarFilasTabla(elemento, event.target.value);
                renderizarPropiedades();
                actualizarNodoSeleccionado();
                return;
            }

            if (event.target.matches('[data-tabla-columnas]')) {
                ajustarColumnasTabla(elemento, event.target.value);
                renderizarPropiedades();
                actualizarNodoSeleccionado();
                return;
            }

            if (event.target.matches('[data-tabla-encabezado]')) {
                const columnaIndex = Number(event.target.dataset.tablaEncabezado);
                const columnas = columnasTabla(elemento);

                if (columnas[columnaIndex]) {
                    columnas[columnaIndex].titulo_columna = event.target.value;
                    actualizarNodoSeleccionado();
                }

                return;
            }

            if (event.target.matches('[data-tabla-celda]')) {
                const filaIndex = Number(event.target.dataset.fila);
                const columnaIndex = Number(event.target.dataset.columna);
                const filas = filasTabla(elemento);

                if (filas[filaIndex]) {
                    filas[filaIndex][columnaIndex] = event.target.value;
                    guardarFilasTabla(elemento, filas);
                    actualizarNodoSeleccionado();
                }

                return;
            }

            if (event.target.matches('[data-qr-codigo]')) {
                elemento.codigo_elemento = event.target.value;
                elemento.texto_fijo = '';
                actualizarNodoSeleccionado();
                renderizarPropiedades();
                return;
            }

            const prop = event.target.dataset.prop;

            if (prop) {
                if (['posicion_x', 'posicion_y', 'ancho', 'alto', 'tamano_letra', 'padding_x', 'padding_y', 'interlineado', 'z_index'].includes(prop)) {
                    elemento[prop] = Number(event.target.value || 0);
                } else if (prop === 'alineacion') {
                    elemento[prop] = normalizarAlineacion(event.target.value);
                } else {
                    elemento[prop] = event.target.value;
                }

                actualizarNodoSeleccionado();
            }

            if (event.target.matches('[data-prop-texto]')) {
                elemento.texto_fijo = event.target.value;
                actualizarNodoSeleccionado();
            }
        }

        panelPropiedades?.addEventListener('input', actualizarPropiedadDesdePanel);
        panelPropiedades?.addEventListener('change', actualizarPropiedadDesdePanel);

        panelPropiedades?.addEventListener('focusin', function (event) {
            if (!preparandoHistorialPropiedad && event.target.matches('[data-prop], [data-prop-texto], [data-tabla-filas], [data-tabla-columnas], [data-tabla-encabezado], [data-tabla-celda], [data-qr-codigo]')) {
                guardarHistorial();
                preparandoHistorialPropiedad = true;
            }
        });

        panelPropiedades?.addEventListener('focusout', function () {
            preparandoHistorialPropiedad = false;
        });

        panelPropiedades?.addEventListener('click', function (event) {
            const elemento = elementoSeleccionado();

            if (!elemento) {
                return;
            }

            const formato = event.target.closest('[data-toggle-formato]');
            const eliminar = event.target.closest('[data-eliminar-elemento]');
            const insertar = event.target.closest('[data-insertar-marcador]');
            const qrImagen = event.target.closest('[data-qr-imagen]');
            const qrCampo = event.target.closest('[data-qr-usar-campo]');

            if (formato) {
                const propiedad = formato.dataset.toggleFormato;
                guardarHistorial();
                elemento[propiedad] = !elemento[propiedad];
                renderizarLienzo();
            }

            if (eliminar) {
                eliminarElementoSeleccionado();
            }

            if (insertar) {
                if (ultimoCampoSeleccionado) {
                    insertarMarcadorEnTexto(ultimoCampoSeleccionado);
                }
            }

            if (qrImagen) {
                seleccionarImagen((imagen) => {
                    guardarHistorial();
                    elemento.texto_fijo = imagen;
                    elemento.codigo_elemento = null;
                    elemento.ancho = Math.max(Number(elemento.ancho || 0), 86);
                    elemento.alto = Math.max(Number(elemento.alto || 0), 86);
                    renderizarLienzo();
                });
            }

            if (qrCampo) {
                guardarHistorial();
                elemento.texto_fijo = '';
                elemento.codigo_elemento = elemento.codigo_elemento || 'qr.verificacion';
                renderizarLienzo();
            }
        });

        panelCapas?.addEventListener('click', function (event) {
            const boton = event.target.closest('[data-capa-index]');

            if (!boton) {
                return;
            }

            seleccionarElemento(Number(boton.dataset.capaIndex));
        });

        document.querySelectorAll('[data-plantilla-zoom]').forEach((boton) => {
            boton.addEventListener('click', () => cambiarZoom(boton.dataset.plantillaZoom));
        });

        document.addEventListener('keydown', function (event) {
            const estaEscribiendo = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName);

            if (estaEscribiendo) {
                return;
            }

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'z' && !event.shiftKey) {
                event.preventDefault();
                deshacerCambio();
            }

            if ((event.ctrlKey || event.metaKey) && (event.key.toLowerCase() === 'y' || (event.shiftKey && event.key.toLowerCase() === 'z'))) {
                event.preventDefault();
                rehacerCambio();
            }

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'c' && elementoSeleccionado()) {
                event.preventDefault();
                elementoCopiado = JSON.parse(JSON.stringify(elementoSeleccionado()));
            }

            if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'v' && elementoCopiado) {
                event.preventDefault();
                guardarHistorial();
                elementos.push(normalizarElemento({
                    ...JSON.parse(JSON.stringify(elementoCopiado)),
                    posicion_x: Number(elementoCopiado.posicion_x || 0) + 18,
                    posicion_y: Number(elementoCopiado.posicion_y || 0) + 18,
                }));
                seleccionarElemento(elementos.length - 1);
            }

            if (event.key === 'Delete' || event.key === 'Backspace') {
                event.preventDefault();
                eliminarElementoSeleccionado();
            }
        });

        buscadorCampo?.addEventListener('input', filtrarCampos);
        selectorTipo?.addEventListener('change', actualizarResumenTipo);
        ['input', 'change'].forEach((evento) => {
            selectorTamano?.addEventListener(evento, actualizarPapelPlantilla);
            selectorOrientacion?.addEventListener(evento, actualizarPapelPlantilla);
            selectorAjusteFondo?.addEventListener(evento, actualizarPapelPlantilla);
            selectorFondoTrabajo?.addEventListener(evento, actualizarPapelPlantilla);
        });
        document.addEventListener('change', function (event) {
            if (event.target.matches('[name="form_tamano_papel"], [name="form_orientacion"], [name="form_ajuste_fondo"], [name="form_fondo_trabajo"]')) {
                actualizarPapelPlantilla();
            }
        });
        fondoSeleccionar?.addEventListener('click', () => fondoInput?.click());
        fondoVer?.addEventListener('click', () => {
            if (urlArchivoPlantilla) {
                window.open(urlArchivoPlantilla, '_blank');
            }
        });
        fondoQuitar?.addEventListener('click', limpiarArchivoPlantilla);

        fondoInput?.addEventListener('change', function () {
            const archivo = this.files && this.files[0];

            if (!archivo) {
                return;
            }

            if (fondoQuitarInput) {
                fondoQuitarInput.value = '0';
            }

            mostrarArchivoSeleccionado(archivo.name, URL.createObjectURL(archivo), archivo.type.startsWith('image/'));
        });

        formularioPlantilla?.addEventListener('submit', actualizarInput);

        if (urlArchivoPlantilla) {
            mostrarArchivoSeleccionado(
                fondoNombre?.textContent || 'Plantilla guardada',
                urlArchivoPlantilla,
                /\.(jpg|jpeg|png|webp)$/i.test(urlArchivoPlantilla)
            );
        }

        renderizarLienzo();
        actualizarResumenTipo();
    });
</script>
