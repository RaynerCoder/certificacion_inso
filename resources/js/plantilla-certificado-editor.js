import {
    ActiveSelection,
    Canvas,
    Ellipse,
    FabricImage,
    FabricObject,
    Group,
    Line,
    Rect,
    Textbox,
    config,
} from 'fabric';

const SELECTOR_LIENZO = '[data-plantilla-lienzo]';
const COLOR_PRINCIPAL = '#0f766e';
const COLOR_TEXTO = '#0f172a';
const PASO_CUADRICULA_PREDETERMINADO = 10;
const PASOS_CUADRICULA_PERMITIDOS = [5, 10, 20, 25, 40, 50, 75, 100, 150, 200];
const TOLERANCIA_GUIA = 15;
const CALIDAD_PREVISUALIZACION = 2;
const CALIDAD_EXPORTACION = 3;
const FUENTES_CERTIFICADO = [
    'Arial',
    'Arial Narrow',
    'Agency FB',
    'Bahnschrift SemiBold Condensed',
    'Calibri',
    'Cambria',
    'Times New Roman',
    'Lucida Bright',
    'Monotype Corsiva',
    'Georgia',
    'Verdana',
    'Tahoma',
    'Courier New',
];
const PROPIEDADES_FABRIC_PLANTILLA = [
    'plantillaIndice',
    'plantillaTipoElemento',
    'plantillaCodigoElemento',
    'plantillaTextoPlantilla',
];

/**
 * Inicia el editor solamente en las pantallas de creación y edición de plantillas.
 * Así Fabric.js no interviene en los demás módulos del sistema.
 */
function iniciarEditorPlantilla() {
    const elementoCanvas = document.querySelector(SELECTOR_LIENZO);

    if (!elementoCanvas || elementoCanvas.dataset.fabricInicializado === '1') {
        return;
    }

    elementoCanvas.dataset.fabricInicializado = '1';
    const editor = crearEditorPlantilla(elementoCanvas);
    editor.iniciar();
}

function crearEditorPlantilla(elementoCanvas) {
    // El lienzo usa al menos el doble de densidad para conservar los detalles finos del fondo.
    config.configure({
        devicePixelRatio: Math.max(window.devicePixelRatio || 1, CALIDAD_PREVISUALIZACION),
    });

    const lienzo = new Canvas(elementoCanvas, {
        enableRetinaScaling: true,
        imageSmoothingEnabled: true,
        preserveObjectStacking: true,
        selection: true,
        selectionKey: ['shiftKey', 'ctrlKey'],
        selectionColor: 'rgba(13, 148, 136, .08)',
        selectionBorderColor: '#0d9488',
        selectionDashArray: [6, 4],
        stopContextMenu: true,
    });

    FabricObject.prototype.set({
        borderColor: '#0d9488',
        cornerColor: '#ffffff',
        cornerStrokeColor: '#0d9488',
        cornerStyle: 'circle',
        cornerSize: 10,
        padding: 3,
        transparentCorners: false,
    });

    function buscarSelect(selectorMarcado, nombre) {
        const elementoMarcado = document.querySelector(selectorMarcado);

        // WireUI coloca los atributos data-* en el contenedor y el valor en el select interno.
        if (elementoMarcado instanceof HTMLSelectElement) return elementoMarcado;

        return elementoMarcado?.querySelector(`select[name="${nombre}"]`)
            || document.querySelector(`select[name="${nombre}"]`);
    }

    const controles = {
        formulario: elementoCanvas.closest('form'),
        disenador: elementoCanvas.closest('.plantilla-designer'),
        inputElementos: document.querySelector('[data-plantilla-elementos-input]'),
        inputDisenoFabric: document.querySelector('[data-plantilla-diseno-fabric-input]'),
        contadorCapas: document.querySelector('[data-plantilla-contador-capas]'),
        propiedades: document.querySelector('[data-plantilla-propiedades]'),
        capas: document.querySelector('[data-plantilla-capas]'),
        pestanasLaterales: [...document.querySelectorAll('[data-plantilla-sidebar-tab]')],
        panelesLaterales: [...document.querySelectorAll('[data-plantilla-sidebar-pane]')],
        escenario: document.querySelector('[data-plantilla-stage]'),
        inputFondo: document.querySelector('[data-plantilla-fondo-input]'),
        nombreFondo: document.querySelector('[data-plantilla-fondo-nombre]'),
        seleccionarFondo: document.querySelector('[data-plantilla-fondo-seleccionar]'),
        verFondo: document.querySelector('[data-plantilla-fondo-ver]'),
        quitarFondo: document.querySelector('[data-plantilla-fondo-quitar]'),
        quitarFondoInput: document.querySelector('[data-plantilla-fondo-quitar-input]'),
        selectorTamano: buscarSelect('[data-plantilla-tamano]', 'form_tamano_papel'),
        selectorOrientacion: buscarSelect('[data-plantilla-orientacion]', 'form_orientacion'),
        selectorAjusteFondo: buscarSelect('[data-plantilla-ajuste-fondo]', 'form_ajuste_fondo'),
        selectorFondoTrabajo: buscarSelect('[data-plantilla-fondo-trabajo]', 'form_fondo_trabajo'),
        selectorTipo: buscarSelect('[data-plantilla-tipo]', 'form_id_tipo_certificado'),
        resumenTipo: document.querySelector('[data-plantilla-resumen-tipo]'),
        buscarCampo: document.querySelector('[data-plantilla-buscar-campo]'),
        zoom: document.querySelector('[data-plantilla-zoom-valor]'),
        envolturaLienzo: document.querySelector('.plantilla-canvas-wrap'),
        selectorSeleccion: document.querySelector('[data-plantilla-seleccion]'),
        selectorDistribucion: document.querySelector('[data-plantilla-distribuir]'),
        selectorExportacion: document.querySelector('[data-plantilla-exportar]'),
        selectorTipoCuadricula: document.querySelector('[data-plantilla-grid-type]'),
        selectorTamanoCuadricula: document.querySelector('[data-plantilla-grid-size]'),
    };

    const campos = [...document.querySelectorAll('[data-plantilla-campo]')].map((boton) => ({
        boton,
        codigo: boton.dataset.codigo,
        nombre: boton.dataset.nombre,
        grupo: boton.dataset.grupo || 'Otros datos',
    }));
    const tiposCertificados = Array.isArray(window.tiposCertificadosPlantilla)
        ? window.tiposCertificadosPlantilla
        : [];

    let elementos = Array.isArray(window.elementosPlantillaIniciales)
        ? window.elementosPlantillaIniciales.map(normalizarElemento)
        : [];
    const disenoFabricInicial = window.disenoFabricPlantillaInicial
        && typeof window.disenoFabricPlantillaInicial === 'object'
        ? window.disenoFabricPlantillaInicial
        : null;
    let indiceSeleccionado = elementos.length ? 0 : null;
    let indicesSeleccionadosEstado = elementos.length ? [0] : [];
    let campoSeleccionado = campos[0]?.codigo || null;
    let urlFondo = controles.inputFondo?.dataset.plantillaFondoUrl || '';
    let zoom = 1;
    // La cuadrícula y el imán son independientes: se puede usar la guía visual sin forzar movimientos.
    let cuadriculaVisible = true;
    let imanActivo = true;
    let tipoCuadricula = 'LINEAS';
    let pasoCuadricula = PASO_CUADRICULA_PREDETERMINADO;
    let historial = [];
    let historialRehacer = [];
    let elementoCopiado = null;
    let cicloRender = 0;
    let actualizandoDesdeLienzo = false;
    let editandoPropiedad = false;
    let renderizandoLienzo = false;
    // El editor abre a tamaño real. "Ajustar" continúa disponible como acción manual.
    let zoomAjustadoAutomaticamente = false;
    let fondoFabric = null;
    let urlFondoFabric = '';
    let cicloCargaFondo = 0;
    let codigoCampoArrastrado = '';
    let teclaEspacioActiva = false;
    const guias = crearGuiasAlineacion();
    const reglas = crearReglasLienzo();

    async function iniciar() {
        await cargarFuentesDelSistema();
        configurarEventosLienzo();
        configurarCamposDisponibles();
        configurarBarraHerramientas();
        configurarPaneles();
        configurarFondo();
        configurarFormulario();
        actualizarTipoSeleccionado();
        actualizarPapel();
        actualizarCuadricula();

        if (urlFondo) {
            mostrarFondo(
                controles.nombreFondo?.textContent || 'Plantilla guardada',
                urlFondo,
                /\.(png|jpe?g|webp)(\?.*)?$/i.test(urlFondo),
            );
        }

        const cargoDisenoExacto = await cargarDisenoFabricGuardado();
        if (!cargoDisenoExacto) await renderizarTodo();
        requestAnimationFrame(aplicarZoom);
    }

    async function cargarFuentesDelSistema() {
        if (!document.fonts?.load) return;

        // Se cargan antes de medir los textos para que Fabric conserve su ancho y salto de línea.
        await Promise.allSettled(
            FUENTES_CERTIFICADO.map((fuente) => document.fonts.load(`16px "${fuente}"`)),
        );
    }

    // Mantiene el mismo JSON que ya recibe Laravel; no se cambia el contrato del módulo.
    function normalizarElemento(item = {}) {
        const tipo = String(item.tipo_elemento || (String(item.codigo_elemento || '').endsWith('.tabla') ? 'TABLA' : 'CAMPO')).toUpperCase();
        const usaEspaciado = !['IMAGEN', 'TABLA', 'QR', 'FORMA', 'LINEA'].includes(tipo);
        const configuracion = normalizarConfiguracion(item.configuracion, tipo);

        return {
            tipo_elemento: tipo,
            codigo_elemento: item.codigo_elemento || null,
            texto_fijo: item.texto_fijo ?? textoInicial(tipo, item.codigo_elemento),
            pagina: numero(item.pagina, 1),
            posicion_x: numero(item.posicion_x, 28),
            posicion_y: numero(item.posicion_y, 28),
            ancho: Math.max(usaEspaciado ? 40 : 1, numero(item.ancho, anchoInicial(tipo))),
            alto: Math.max(usaEspaciado ? 20 : 1, numero(item.alto, altoInicial(tipo))),
            tamano_letra: limitar(numero(item.tamano_letra, tipo === 'TEXTO' ? 12 : 11), 8, 160),
            alineacion: normalizarAlineacion(item.alineacion),
            padding_x: numero(item.padding_x, usaEspaciado ? 7 : 0),
            padding_y: numero(item.padding_y, usaEspaciado ? 5 : 0),
            interlineado: limitar(numero(item.interlineado, 1.25), 0.8, 3),
            negrita: Boolean(item.negrita),
            cursiva: Boolean(item.cursiva),
            subrayado: Boolean(item.subrayado),
            color_texto: normalizarColorTexto(item.color_texto),
            tipo_letra: item.tipo_letra || 'Arial',
            z_index: numero(item.z_index, 3),
            configuracion,
            estado: item.estado || 'ACTIVO',
            columnas: Array.isArray(item.columnas) && item.columnas.length
                ? item.columnas.map((columna) => ({ ...columna }))
                : columnasPredeterminadas(item.codigo_elemento),
        };
    }

    function normalizarConfiguracion(configuracion = {}, tipo = 'CAMPO') {
        const tabla = configuracion.tabla && typeof configuracion.tabla === 'object'
            ? configuracion.tabla
            : {};

        return {
            rotacion: numero(configuracion.rotacion, 0),
            opacidad: limitar(numero(configuracion.opacidad, 1), 0.05, 1),
            fondo_color: configuracion.fondo_color || (tipo === 'FORMA' ? '#d1fae5' : 'transparent'),
            borde_color: configuracion.borde_color || COLOR_PRINCIPAL,
            borde_ancho: numero(configuracion.borde_ancho, ['FORMA', 'LINEA'].includes(tipo) ? 2 : 0),
            borde_estilo: configuracion.borde_estilo || 'SOLIDO',
            radio_borde: numero(configuracion.radio_borde, tipo === 'FORMA' ? 6 : 0),
            bloqueado: Boolean(configuracion.bloqueado),
            visible: configuracion.visible !== false,
            nombre_capa: String(configuracion.nombre_capa || '').trim(),
            grupo: String(configuracion.grupo || '').trim(),
            forma: configuracion.forma || 'RECTANGULO',
            ajuste_imagen: configuracion.ajuste_imagen || 'CONTENER',
            estilos_texto: normalizarEstilosTexto(configuracion.estilos_texto),
            tabla: {
                tipo: tabla.tipo === 'CAMPO_VALOR' ? 'CAMPO_VALOR' : 'AUTOMATICA',
                pares_por_fila: numero(tabla.pares_por_fila, 1) === 2 ? 2 : 1,
                mostrar_encabezado: tabla.mostrar_encabezado !== false,
                etiquetas_negrita: tabla.etiquetas_negrita !== false,
                ajustar_texto: tabla.ajustar_texto !== false,
                tamano_minimo: limitar(numero(tabla.tamano_minimo, 7), 5, 24),
                bordes: tabla.bordes || 'TODOS',
                borde_color: tabla.borde_color || COLOR_TEXTO,
                borde_ancho: numero(tabla.borde_ancho, 1),
                borde_estilo: tabla.borde_estilo || 'SOLIDO',
                encabezado_fondo: tabla.encabezado_fondo || '#f1f5f9',
                encabezado_texto: tabla.encabezado_texto || COLOR_TEXTO,
                celdas_fondo: tabla.tipo === 'CAMPO_VALOR' ? 'transparent' : (tabla.celdas_fondo || '#ffffff'),
                filas_alternas: Boolean(tabla.filas_alternas),
                filas_alternas_color: tabla.filas_alternas_color || '#f8fafc',
                padding_x: numero(tabla.padding_x, 6),
                padding_y: numero(tabla.padding_y, 4),
                alineacion_vertical: tabla.alineacion_vertical || 'MEDIO',
            },
        };
    }

    function normalizarEstilosTexto(estilos = {}) {
        if (!estilos || typeof estilos !== 'object' || Array.isArray(estilos)) return {};

        const resultado = {};
        Object.entries(estilos).forEach(([linea, caracteres]) => {
            if (!/^\d+$/.test(linea) || !caracteres || typeof caracteres !== 'object' || Array.isArray(caracteres)) return;

            Object.entries(caracteres).forEach(([caracter, estilo]) => {
                if (!/^\d+$/.test(caracter) || !estilo || typeof estilo !== 'object' || Array.isArray(estilo)) return;
                const estiloSeguro = {};
                if (['bold', 'normal'].includes(estilo.fontWeight)) estiloSeguro.fontWeight = estilo.fontWeight;
                if (['italic', 'normal'].includes(estilo.fontStyle)) estiloSeguro.fontStyle = estilo.fontStyle;
                if (typeof estilo.underline === 'boolean') estiloSeguro.underline = estilo.underline;
                if (!Object.keys(estiloSeguro).length) return;
                resultado[linea] ??= {};
                resultado[linea][caracter] = estiloSeguro;
            });
        });

        return resultado;
    }

    function clonarEstilosTexto(estilos = {}) {
        return normalizarEstilosTexto(JSON.parse(JSON.stringify(estilos || {})));
    }

    function patronTrazoLinea(estilo) {
        if (estilo === 'PUNTEADO') return [2, 5];
        if (estilo === 'DISCONTINUO') return [12, 7];
        return null;
    }

    function numero(valor, predeterminado = 0) {
        const convertido = Number(valor);
        return Number.isFinite(convertido) ? convertido : predeterminado;
    }

    function limitar(valor, minimo, maximo) {
        return Math.max(minimo, Math.min(maximo, valor));
    }

    function normalizarColorTexto(valor) {
        const color = String(valor || '').trim();
        return !color || color.toLowerCase() === 'transparent' ? COLOR_TEXTO : color;
    }

    function marcador(codigo) {
        const llave = String(codigo || 'campo')
            .replace(/[^a-zA-Z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
        return `{{${llave}}}`;
    }

    function textoInicial(tipo, codigo) {
        if (['TABLA', 'QR', 'IMAGEN', 'FORMA', 'LINEA'].includes(tipo)) {
            return '';
        }

        if (tipo === 'FIRMA') {
            return codigo === 'firma.director'
                ? `${marcador('funcionario.director_nombre')}\n${marcador('funcionario.director_cargo')}`
                : `${marcador('funcionario.responsable_area_nombre')}\n${marcador('funcionario.responsable_area_cargo')}`;
        }

        return codigo ? marcador(codigo) : 'Escriba el texto del certificado aquí...';
    }

    function anchoInicial(tipo) {
        return ({ TABLA: 520, QR: 90, FIRMA: 260, IMAGEN: 160, FORMA: 180, LINEA: 220, TEXTO: 430 })[tipo] || 190;
    }

    function altoInicial(tipo) {
        return ({ TABLA: 118, QR: 90, FIRMA: 58, IMAGEN: 90, FORMA: 100, LINEA: 8, TEXTO: 88 })[tipo] || 34;
    }

    function normalizarAlineacion(valor) {
        const alineacion = String(valor || 'IZQUIERDA').toUpperCase();
        return ['IZQUIERDA', 'CENTRO', 'DERECHA', 'JUSTIFICADO'].includes(alineacion)
            ? alineacion
            : 'IZQUIERDA';
    }

    function columnasPredeterminadas(codigo) {
        const tablas = {
            'producto.tabla': [
                { codigo_campo: 'producto.nombres_comerciales', titulo_columna: 'Producto', ancho: 35, estado: 'ACTIVO' },
                { codigo_campo: 'registro.codigos', titulo_columna: 'Registro', ancho: 30, estado: 'ACTIVO' },
                { codigo_campo: 'presentacion.descripciones', titulo_columna: 'Presentación', ancho: 35, estado: 'ACTIVO' },
            ],
            'ingrediente.tabla': [
                { codigo_campo: 'ingrediente.nombre', titulo_columna: 'Ingrediente', ancho: 65, estado: 'ACTIVO' },
                { codigo_campo: 'ingrediente.porcentaje', titulo_columna: 'Porcentaje', ancho: 35, estado: 'ACTIVO' },
            ],
            'requisito.tabla': [
                { codigo_campo: 'requisito.descripcion', titulo_columna: 'Requisito', ancho: 45, estado: 'ACTIVO' },
                { codigo_campo: 'requisito.tipo_evidencia', titulo_columna: 'Evidencia', ancho: 25, estado: 'ACTIVO' },
                { codigo_campo: 'requisito.cumple', titulo_columna: 'Cumple', ancho: 30, estado: 'ACTIVO' },
            ],
        };
        return (tablas[codigo] || []).map((columna) => ({ ...columna }));
    }

    function columnasTablaDatos(paresPorFila = 1) {
        const pares = paresPorFila === 2 ? 2 : 1;
        const anchos = pares === 2 ? [20, 30, 20, 30] : [32, 68];

        return anchos.map((ancho, indice) => ({
            codigo_campo: `tabla.columna_${indice + 1}`,
            titulo_columna: indice % 2 === 0 ? 'Campo' : 'Valor',
            ancho,
            estado: 'ACTIVO',
        }));
    }

    function esTablaDatos(elemento) {
        return elemento?.tipo_elemento === 'TABLA'
            && elemento.configuracion?.tabla?.tipo === 'CAMPO_VALOR';
    }

    function codigoDesdeMarcador(valor) {
        const texto = String(valor || '').trim();
        return campos.find((campo) => marcador(campo.codigo) === texto)?.codigo || '';
    }

    function filasConfiguradasTabla(elemento) {
        try {
            const datos = JSON.parse(elemento.texto_fijo || '{}');
            return Array.isArray(datos.filas) ? datos.filas.map((fila) => [...fila]) : [];
        } catch (error) {
            return [];
        }
    }

    function estilosConfiguradosTabla(elemento) {
        try {
            const datos = JSON.parse(elemento.texto_fijo || '{}');
            return Array.isArray(datos.estilos)
                ? datos.estilos.map((fila) => (Array.isArray(fila) ? fila.map((estilo) => ({ ...(estilo || {}) })) : []))
                : [];
        } catch (error) {
            return [];
        }
    }

    function estiloCeldaTabla(elemento, filaIndice, columnaIndice, encabezado = false) {
        const tabla = elemento.configuracion.tabla;
        const esEtiqueta = tabla.tipo === 'CAMPO_VALOR' && columnaIndice % 2 === 0;
        const estiloGuardado = estilosConfiguradosTabla(elemento)?.[filaIndice]?.[columnaIndice] || {};
        const tipoLetra = FUENTES_CERTIFICADO.includes(estiloGuardado.tipo_letra)
            ? estiloGuardado.tipo_letra
            : elemento.tipo_letra;

        return {
            tipo_letra: tipoLetra || 'Arial',
            tamano_letra: limitar(numero(estiloGuardado.tamano_letra, elemento.tamano_letra), 6, 160),
            negrita: encabezado
                ? true
                : (typeof estiloGuardado.negrita === 'boolean'
                    ? estiloGuardado.negrita
                    : Boolean(elemento.negrita || (esEtiqueta && tabla.etiquetas_negrita))),
            cursiva: typeof estiloGuardado.cursiva === 'boolean'
                ? estiloGuardado.cursiva
                : Boolean(elemento.cursiva),
            subrayado: typeof estiloGuardado.subrayado === 'boolean'
                ? estiloGuardado.subrayado
                : Boolean(elemento.subrayado),
            color_texto: normalizarColorTexto(estiloGuardado.color_texto || elemento.color_texto),
            alineacion: normalizarAlineacion(estiloGuardado.alineacion || elemento.alineacion),
        };
    }

    function guardarFilasTabla(elemento, filas, estilos = estilosConfiguradosTabla(elemento)) {
        const estilosNormalizados = filas.map((fila, filaIndice) => fila.map((_, columnaIndice) => (
            { ...(estilos?.[filaIndice]?.[columnaIndice] || {}) }
        )));
        elemento.texto_fijo = JSON.stringify({ filas, estilos: estilosNormalizados });
        const cantidadFilas = Math.max(1, filas.length);
        const encabezado = elemento.configuracion.tabla.mostrar_encabezado ? 1 : 0;
        const alturaFilaMinima = elemento.configuracion.tabla.pares_por_fila === 2 ? 42 : 36;
        elemento.alto = Math.max(elemento.alto, cantidadFilas * alturaFilaMinima + encabezado * 32);
    }

    function entradasTablaDatos(elemento) {
        const estilos = estilosConfiguradosTabla(elemento);
        return filasConfiguradasTabla(elemento).flatMap((fila, filaIndice) => {
            const entradas = [];
            for (let indice = 0; indice < fila.length; indice += 2) {
                entradas.push({
                    etiqueta: String(fila[indice] || ''),
                    codigo: codigoDesdeMarcador(fila[indice + 1]),
                    estiloEtiqueta: { ...(estilos?.[filaIndice]?.[indice] || {}) },
                    estiloValor: { ...(estilos?.[filaIndice]?.[indice + 1] || {}) },
                });
            }
            return entradas;
        });
    }

    function reagruparTablaDatos(elemento, entradas, paresPorFila, actualizarColumnas = true) {
        const pares = paresPorFila === 2 ? 2 : 1;
        const filas = [];
        const estilos = [];

        for (let indice = 0; indice < entradas.length; indice += pares) {
            const fila = [];
            const estilosFila = [];
            for (let par = 0; par < pares; par += 1) {
                const entrada = entradas[indice + par];
                if (!entrada) continue;
                fila.push(entrada.etiqueta || '');
                fila.push(entrada.codigo ? marcador(entrada.codigo) : '');
                estilosFila.push({ ...(entrada.estiloEtiqueta || {}) });
                estilosFila.push({ ...(entrada.estiloValor || {}) });
            }
            filas.push(fila);
            estilos.push(estilosFila);
        }

        elemento.configuracion.tabla.pares_por_fila = pares;
        if (actualizarColumnas) elemento.columnas = columnasTablaDatos(pares);
        guardarFilasTabla(elemento, filas.length ? filas : [['', '']], estilos.length ? estilos : [[{}, {}]]);
    }

    function crearTablaDatos() {
        agregarElemento('TABLA', null, {
            ancho: 620,
            alto: 64,
            tamano_letra: 11,
            columnas: columnasTablaDatos(1),
            texto_fijo: JSON.stringify({ filas: [['', '']], estilos: [[{}, {}]] }),
            configuracion: {
                nombre_capa: 'Tabla de datos',
                tabla: {
                    tipo: 'CAMPO_VALOR',
                    pares_por_fila: 1,
                    mostrar_encabezado: false,
                    etiquetas_negrita: true,
                    ajustar_texto: true,
                    tamano_minimo: 7,
                    bordes: 'NINGUNO',
                    borde_color: COLOR_TEXTO,
                    borde_ancho: 1,
                    borde_estilo: 'SOLIDO',
                    encabezado_fondo: '#f1f5f9',
                    encabezado_texto: COLOR_TEXTO,
                    celdas_fondo: 'transparent',
                    filas_alternas: false,
                    filas_alternas_color: '#f8fafc',
                    padding_x: 6,
                    padding_y: 4,
                    alineacion_vertical: 'SUPERIOR',
                },
            },
        });
    }

    function nombreElemento(elemento) {
        if (elemento.configuracion.nombre_capa) {
            return elemento.configuracion.nombre_capa;
        }

        const nombres = {
            TEXTO: 'Texto',
            TABLA: 'Tabla',
            FIRMA: 'Firma',
            QR: 'Código QR',
            IMAGEN: 'Imagen',
            FORMA: elemento.configuracion.forma === 'ELIPSE' ? 'Elipse' : 'Rectángulo',
            LINEA: 'Línea',
        };
        return nombres[elemento.tipo_elemento]
            || campos.find((campo) => campo.codigo === elemento.codigo_elemento)?.nombre
            || elemento.codigo_elemento
            || 'Elemento';
    }

    function guardarHistorial() {
        historial.push(JSON.stringify(elementos));
        if (historial.length > 50) historial.shift();
        historialRehacer = [];
    }

    function restaurarDesdeHistorial(contenido, destino) {
        destino.push(JSON.stringify(elementos));
        elementos = JSON.parse(contenido).map(normalizarElemento);
        indiceSeleccionado = elementos.length ? Math.min(indiceSeleccionado ?? 0, elementos.length - 1) : null;
        indicesSeleccionadosEstado = Number.isInteger(indiceSeleccionado) ? [indiceSeleccionado] : [];
        renderizarTodo();
    }

    function deshacer() {
        if (historial.length) restaurarDesdeHistorial(historial.pop(), historialRehacer);
    }

    function rehacer() {
        if (historialRehacer.length) restaurarDesdeHistorial(historialRehacer.pop(), historial);
    }

    async function renderizarTodo() {
        const lienzoActualizado = await renderizarLienzo();
        if (!lienzoActualizado) return;
        renderizarCapas();
        renderizarPropiedades();
        actualizarInput();
    }

    async function cargarDisenoFabricGuardado() {
        if (!Array.isArray(disenoFabricInicial?.objects)
            || disenoFabricInicial.objects.length !== elementos.length
            // La tabla Campo/Valor se recompone para medir el contenido y conservar sus filas autoajustables.
            || elementos.some((elemento) => esTablaDatos(elemento))) {
            return false;
        }

        renderizandoLienzo = true;
        try {
            await lienzo.loadFromJSON({
                version: disenoFabricInicial.version,
                objects: disenoFabricInicial.objects,
            });

            lienzo.getObjects().forEach((objeto, posicion) => {
                const indiceGuardado = Number(objeto.plantillaIndice);
                const indice = Number.isInteger(indiceGuardado) && elementos[indiceGuardado]
                    ? indiceGuardado
                    : posicion;
                const elemento = elementos[indice];

                objeto.set({
                    selectable: true,
                    evented: true,
                    lockMovementX: elemento.configuracion.bloqueado,
                    lockMovementY: elemento.configuracion.bloqueado,
                    lockScalingX: elemento.configuracion.bloqueado,
                    lockScalingY: elemento.configuracion.bloqueado,
                    lockRotation: elemento.configuracion.bloqueado,
                    hasControls: !elemento.configuracion.bloqueado,
                    hoverCursor: elemento.configuracion.bloqueado ? 'not-allowed' : 'move',
                    visible: elemento.configuracion.visible,
                });
                objeto.plantillaIndice = indice;
                objeto.plantillaTipoElemento = elemento.tipo_elemento;
                objeto.plantillaCodigoElemento = elemento.codigo_elemento;
                objeto.plantillaTextoPlantilla = elemento.texto_fijo;

                if (objeto instanceof Textbox) {
                    objeto.set({ editable: true, objectCaching: false });
                    objeto.setControlsVisibility({ mt: true, mb: true, tl: true, tr: true, bl: true, br: true, ml: true, mr: true, mtr: true });
                }
                objeto.setCoords();
            });

            await aplicarFondoVisual();
            restaurarSeleccionEnLienzo();
            lienzo.requestRenderAll();
            renderizarCapas();
            renderizarPropiedades();
            actualizarInput();
            return true;
        } catch (error) {
            lienzo.remove(...lienzo.getObjects());
            return false;
        } finally {
            renderizandoLienzo = false;
        }
    }

    async function renderizarLienzo() {
        const cicloActual = ++cicloRender;
        const objetos = await Promise.all(
            elementos.map((elemento, indice) => fabricarObjeto(elemento, indice)),
        );

        // Un render anterior nunca debe reemplazar uno más reciente ni dejar capas a medio cargar.
        if (cicloActual !== cicloRender) return false;

        renderizandoLienzo = true;
        try {
            lienzo.discardActiveObject();
            lienzo.remove(...lienzo.getObjects());
            lienzo.backgroundColor = 'rgba(255,255,255,0)';
            lienzo.add(...objetos);
            restaurarSeleccionEnLienzo();
            await aplicarFondoVisual();

            if (cicloActual !== cicloRender) return false;
            lienzo.requestRenderAll();
            return true;
        } finally {
            if (cicloActual === cicloRender) renderizandoLienzo = false;
        }
    }

    async function fabricarObjeto(elemento, indice) {
        let objeto;

        if (elemento.tipo_elemento === 'FORMA') {
            objeto = elemento.configuracion.forma === 'ELIPSE'
                ? new Ellipse({ rx: elemento.ancho / 2, ry: elemento.alto / 2 })
                : new Rect({ width: elemento.ancho, height: elemento.alto, rx: elemento.configuracion.radio_borde, ry: elemento.configuracion.radio_borde });
            objeto.set({
                fill: elemento.configuracion.fondo_color,
                stroke: elemento.configuracion.borde_color,
                strokeWidth: elemento.configuracion.borde_ancho,
            });
        } else if (elemento.tipo_elemento === 'LINEA') {
            objeto = new Line([0, 0, elemento.ancho, 0], {
                stroke: elemento.configuracion.borde_color,
                strokeWidth: Math.max(1, elemento.configuracion.borde_ancho),
                strokeDashArray: patronTrazoLinea(elemento.configuracion.borde_estilo),
            });
        } else if (elemento.tipo_elemento === 'IMAGEN' && elemento.texto_fijo) {
            try {
                objeto = await FabricImage.fromURL(elemento.texto_fijo, { crossOrigin: 'anonymous' });
            } catch (error) {
                objeto = crearMarcadorGrafico('Imagen no disponible', elemento.ancho, elemento.alto);
            }
        } else if (elemento.tipo_elemento === 'QR') {
            if (String(elemento.texto_fijo || '').startsWith('data:image')) {
                try {
                    objeto = await FabricImage.fromURL(elemento.texto_fijo);
                } catch (error) {
                    objeto = crearMarcadorGrafico('QR', elemento.ancho, elemento.alto);
                }
            } else {
                objeto = crearMarcadorGrafico('QR', elemento.ancho, elemento.alto);
            }
        } else if (elemento.tipo_elemento === 'TABLA') {
            objeto = crearTablaFabric(elemento);
        } else {
            objeto = new Textbox(elemento.texto_fijo || textoInicial(elemento.tipo_elemento, elemento.codigo_elemento), {
                width: elemento.ancho,
                fontSize: elemento.tamano_letra,
                fontFamily: elemento.tipo_letra,
                fontWeight: elemento.negrita ? 'bold' : 'normal',
                fontStyle: elemento.cursiva ? 'italic' : 'normal',
                underline: elemento.subrayado,
                fill: elemento.color_texto,
                lineHeight: elemento.interlineado,
                textAlign: alineacionFabric(elemento.alineacion),
                editable: true,
                splitByGrapheme: false,
                objectCaching: false,
                selectionBackgroundColor: 'rgba(13, 148, 136, 0.12)',
                styles: clonarEstilosTexto(elemento.configuracion.estilos_texto),
            });
        }

        const anchoNatural = Math.max(1, objeto.width || elemento.ancho);
        const altoNatural = Math.max(1, objeto.height || elemento.alto);
        objeto.set({
            left: elemento.posicion_x,
            top: elemento.posicion_y,
            originX: 'left',
            originY: 'top',
            angle: elemento.configuracion.rotacion,
            opacity: elemento.configuracion.opacidad,
            scaleX: elemento.ancho / anchoNatural,
            scaleY: ['TEXTO', 'CAMPO', 'FIRMA', 'LINEA'].includes(elemento.tipo_elemento) || esTablaDatos(elemento)
                ? 1
                : elemento.alto / altoNatural,
            visible: elemento.configuracion.visible,
            selectable: true,
            evented: true,
            lockMovementX: elemento.configuracion.bloqueado,
            lockMovementY: elemento.configuracion.bloqueado,
            lockScalingX: elemento.configuracion.bloqueado,
            lockScalingY: elemento.configuracion.bloqueado,
            lockRotation: elemento.configuracion.bloqueado,
            hasControls: !elemento.configuracion.bloqueado,
            hoverCursor: elemento.configuracion.bloqueado ? 'not-allowed' : 'move',
        });
        objeto.plantillaIndice = indice;
        objeto.plantillaTipoElemento = elemento.tipo_elemento;
        objeto.plantillaCodigoElemento = elemento.codigo_elemento;
        objeto.plantillaTextoPlantilla = elemento.texto_fijo;
        if (objeto instanceof FabricImage) {
            // La imagen original se conserva; se evita crear una copia reducida para la vista previa.
            objeto.set({ objectCaching: false });
        }
        if (objeto instanceof Textbox) {
            // El texto puede ajustarse desde cualquier lado, igual que los demás elementos del lienzo.
            objeto.setControlsVisibility({
                mt: true,
                mb: true,
                tl: true,
                tr: true,
                bl: true,
                br: true,
                ml: true,
                mr: true,
                mtr: true,
            });
        }
        objeto.setCoords();
        return objeto;
    }

    function crearMarcadorGrafico(texto, ancho, alto) {
        const fondo = new Rect({
            left: 0,
            top: 0,
            width: ancho,
            height: alto,
            fill: '#f8fafc',
            stroke: '#94a3b8',
            strokeDashArray: [5, 4],
            rx: 6,
            ry: 6,
        });
        const etiqueta = new Textbox(texto, {
            left: 8,
            top: Math.max(6, alto / 2 - 8),
            width: Math.max(20, ancho - 16),
            fontSize: 12,
            fontFamily: 'Arial',
            fontWeight: 'bold',
            fill: '#475569',
            textAlign: 'center',
            selectable: false,
            evented: false,
        });
        return new Group([fondo, etiqueta], { width: ancho, height: alto });
    }

    function crearTablaFabric(elemento) {
        const columnas = elemento.columnas.length ? elemento.columnas : columnasPredeterminadas(elemento.codigo_elemento);
        const filas = filasTabla(elemento, columnas);
        const objetos = [];
        const mostrarEncabezado = elemento.configuracion.tabla.mostrar_encabezado;
        const anchosColumnas = columnas.map((columna) => (
            elemento.ancho * (numero(columna.ancho, 100 / Math.max(1, columnas.length)) / 100)
        ));
        const alturaMinimaFila = Math.max(28, Math.min(42, elemento.alto / Math.max(1, filas.length + (mostrarEncabezado ? 1 : 0))));
        const alturaEncabezado = mostrarEncabezado ? Math.max(28, alturaMinimaFila) : 0;
        const alturasFilas = filas.map((fila, filaIndice) => alturaFilaTabla(
            elemento,
            fila,
            filaIndice,
            columnas,
            anchosColumnas,
            alturaMinimaFila,
        ));
        let arriba = 0;

        if (mostrarEncabezado) {
            let izquierda = 0;
            columnas.forEach((columna, columnaIndice) => {
                objetos.push(celdaTabla(
                    izquierda,
                    arriba,
                    anchosColumnas[columnaIndice],
                    alturaEncabezado,
                    columna.titulo_columna || 'Columna',
                    true,
                    elemento,
                    -1,
                    false,
                    estiloCeldaTabla(elemento, -1, columnaIndice, true),
                ));
                izquierda += anchosColumnas[columnaIndice];
            });
            arriba += alturaEncabezado;
        }

        filas.forEach((fila, filaIndice) => {
            let izquierda = 0;
            columnas.forEach((columna, columnaIndice) => {
                const tieneCelda = Object.prototype.hasOwnProperty.call(fila, columnaIndice);
                const contenidoCelda = tieneCelda ? fila[columnaIndice] : '';
                objetos.push(celdaTabla(
                    izquierda,
                    arriba,
                    anchosColumnas[columnaIndice],
                    alturasFilas[filaIndice],
                    esTablaDatos(elemento) && !String(contenidoCelda ?? '').trim()
                        ? (columnaIndice % 2 === 0 ? 'Campo' : 'Valor')
                        : (tieneCelda ? contenidoCelda : marcador(columna.codigo_campo)),
                    false,
                    elemento,
                    filaIndice,
                    esTablaDatos(elemento) && columnaIndice % 2 === 0,
                    estiloCeldaTabla(elemento, filaIndice, columnaIndice),
                ));
                izquierda += anchosColumnas[columnaIndice];
            });
            arriba += alturasFilas[filaIndice];
        });

        if (esTablaDatos(elemento)) elemento.alto = Math.max(1, redondear(arriba));

        return new Group(objetos, { width: elemento.ancho, height: arriba });
    }

    function alturaFilaTabla(elemento, fila, filaIndice, columnas, anchosColumnas, alturaMinima) {
        if (!esTablaDatos(elemento)) return alturaMinima;

        const paddingX = numero(elemento.configuracion.tabla.padding_x, 6);
        const paddingY = numero(elemento.configuracion.tabla.padding_y, 4);

        return columnas.reduce((alturaMayor, columna, columnaIndice) => {
            const contenido = Object.prototype.hasOwnProperty.call(fila, columnaIndice)
                ? fila[columnaIndice]
                : marcador(columna.codigo_campo);
            const estiloCelda = estiloCeldaTabla(elemento, filaIndice, columnaIndice);
            const textoMedido = new Textbox(String(contenido ?? ''), {
                width: Math.max(10, anchosColumnas[columnaIndice] - paddingX * 2),
                fontSize: estiloCelda.tamano_letra,
                fontFamily: estiloCelda.tipo_letra,
                fontWeight: estiloCelda.negrita ? 'bold' : 'normal',
                fontStyle: estiloCelda.cursiva ? 'italic' : 'normal',
                underline: estiloCelda.subrayado,
                lineHeight: 1.05,
                splitByGrapheme: true,
            });
            return Math.max(alturaMayor, textoMedido.height + paddingY * 2);
        }, alturaMinima);
    }

    function celdaTabla(left, top, width, height, texto, encabezado, elemento, filaIndice = 0, etiqueta = false, estiloCelda = null) {
        const estilo = elemento.configuracion.tabla;
        const fondo = encabezado
            ? estilo.encabezado_fondo
            : (estilo.filas_alternas && filaIndice % 2 ? estilo.filas_alternas_color : estilo.celdas_fondo);
        const paddingX = numero(estilo.padding_x, 6);
        const paddingY = numero(estilo.padding_y, 4);
        const anchoTexto = Math.max(10, width - paddingX * 2);
        const altoTexto = Math.max(10, height - paddingY * 2);
        const formato = estiloCelda || estiloCeldaTabla(elemento, filaIndice, 0, encabezado);
        const textoCelda = textoAjustadoCelda(
            texto,
            anchoTexto,
            altoTexto,
            formato.tamano_letra,
            numero(estilo.tamano_minimo, 7),
            estilo.ajustar_texto !== false && !esTablaDatos(elemento),
            {
                left: paddingX,
                top: paddingY,
                fontFamily: formato.tipo_letra,
                fontWeight: formato.negrita ? 'bold' : 'normal',
                fontStyle: formato.cursiva ? 'italic' : 'normal',
                underline: formato.subrayado,
                fill: encabezado ? estilo.encabezado_texto : formato.color_texto,
                textAlign: alineacionFabric(formato.alineacion),
                selectable: false,
                evented: false,
            },
        );
        const altoRealTexto = Math.min(numero(textoCelda.height), altoTexto);
        textoCelda.top = paddingY + desplazamientoVerticalCelda(estilo.alineacion_vertical, altoTexto, altoRealTexto);

        return new Group([
            new Rect({
                left: 0,
                top: 0,
                width,
                height,
                fill: fondo,
                stroke: estilo.borde_color,
                strokeWidth: estilo.bordes === 'NINGUNO' ? 0 : estilo.borde_ancho,
            }),
            textoCelda,
        ], { left, top, width, height, selectable: false, evented: false });
    }

    function textoAjustadoCelda(texto, width, height, tamanoInicial, tamanoMinimo, ajustar, opciones = {}) {
        const contenidoOriginal = String(texto ?? '');
        const minimo = Math.min(tamanoInicial, Math.max(5, tamanoMinimo));
        const objeto = new Textbox(contenidoOriginal, {
            ...opciones,
            width,
            fontSize: tamanoInicial,
            lineHeight: 1.05,
            splitByGrapheme: true,
        });

        const redimensionar = (tamano, contenido = objeto.text) => {
            objeto.set({ fontSize: tamano, text: contenido });
            objeto.initDimensions();
        };

        if (ajustar) {
            let tamano = tamanoInicial;
            while (numero(objeto.height) > height && tamano > minimo) {
                tamano = Math.max(minimo, tamano - 0.5);
                redimensionar(tamano);
            }
        }

        // Última protección: si el contenido todavía no cabe, se usa elipsis.
        // El valor original no se modifica; solo se limita su representación visual.
        if (numero(objeto.height) > height && contenidoOriginal.length > 1) {
            let inicio = 0;
            let fin = contenidoOriginal.length;
            let contenidoVisible = '…';
            while (inicio <= fin) {
                const medio = Math.floor((inicio + fin) / 2);
                const candidato = `${contenidoOriginal.slice(0, medio).trimEnd()}…`;
                redimensionar(objeto.fontSize, candidato);
                if (numero(objeto.height) <= height) {
                    contenidoVisible = candidato;
                    inicio = medio + 1;
                } else {
                    fin = medio - 1;
                }
            }
            redimensionar(objeto.fontSize, contenidoVisible);
        }

        return objeto;
    }

    function desplazamientoVerticalCelda(alineacion, altoDisponible, altoTexto) {
        if (alineacion === 'INFERIOR') return Math.max(0, altoDisponible - altoTexto);
        if (alineacion === 'MEDIO') return Math.max(0, (altoDisponible - altoTexto) / 2);
        return 0;
    }

    function filasTabla(elemento, columnas = elemento.columnas) {
        try {
            const datos = JSON.parse(elemento.texto_fijo || '{}');
            if (Array.isArray(datos.filas) && datos.filas.length) return datos.filas;
        } catch (error) {
            // Una plantilla antigua puede tener texto simple; en ese caso se muestra una fila de ejemplo.
        }
        return [columnas.map((columna) => marcador(columna.codigo_campo))];
    }

    function alineacionFabric(alineacion) {
        return ({ IZQUIERDA: 'left', CENTRO: 'center', DERECHA: 'right', JUSTIFICADO: 'justify' })[alineacion] || 'left';
    }

    function objetoPorIndice(indice) {
        return lienzo.getObjects().find((objeto) => objeto.plantillaIndice === indice) || null;
    }

    function restaurarSeleccionEnLienzo() {
        const objetos = indicesSeleccionadosEstado
            .map(objetoPorIndice)
            .filter((objeto) => objeto?.visible);

        if (objetos.length === 1) lienzo.setActiveObject(objetos[0]);
        if (objetos.length > 1) {
            lienzo.setActiveObject(new ActiveSelection(objetos, { canvas: lienzo }));
        }
    }

    function sincronizarDesdeObjeto(objeto) {
        if (!objeto) return;

        if (objeto instanceof ActiveSelection) {
            const objetos = [...objeto.getObjects()];
            const indices = objetos
                .map((seleccionado) => seleccionado.plantillaIndice)
                .filter(Number.isInteger);

            // Al salir de ActiveSelection, Fabric aplica al objeto hijo el movimiento,
            // la escala y la rotación que el usuario hizo sobre toda la selección.
            renderizandoLienzo = true;
            lienzo.discardActiveObject();
            renderizandoLienzo = false;
            objetos.forEach(copiarTransformacionAlModelo);
            establecerSeleccion(indices);
            actualizarInput();
            renderizarCapas();
            renderizarPropiedades();
            restaurarSeleccionEnLienzo();
            lienzo.requestRenderAll();
            return;
        }

        if (!copiarTransformacionAlModelo(objeto)) return;
        actualizarInput();
        renderizarCapas();
        renderizarPropiedades();

        if (objeto instanceof Textbox) {
            // El texto ya está actualizado en Fabric; reconstruir el lienzo aquí podía ocultarlo al soltarlo.
            objeto.dirty = true;
            lienzo.requestRenderAll();
        } else {
            renderizarLienzo();
        }
    }

    function copiarTransformacionAlModelo(objeto) {
        const indice = objeto?.plantillaIndice;
        const elemento = Number.isInteger(indice) ? elementos[indice] : null;
        if (!elemento) return false;

        actualizandoDesdeLienzo = true;
        const escalaX = Math.max(0.01, Math.abs(numero(objeto.scaleX, 1)));
        const escalaY = Math.max(0.01, Math.abs(numero(objeto.scaleY, 1)));
        elemento.posicion_x = redondear(objeto.left);
        elemento.posicion_y = redondear(objeto.top);
        elemento.ancho = Math.max(1, redondear(objeto.getScaledWidth()));
        elemento.alto = Math.max(1, redondear(objeto.getScaledHeight()));
        elemento.configuracion.rotacion = redondear(objeto.angle);
        elemento.configuracion.opacidad = numero(objeto.opacity, 1);

        if (objeto instanceof Textbox) {
            // La escala vertical se convierte en tamaño de letra para evitar caracteres aplastados.
            elemento.texto_fijo = objeto.text;
            elemento.configuracion.estilos_texto = clonarEstilosTexto(objeto.styles);
            elemento.tamano_letra = limitar(Math.round(elemento.tamano_letra * escalaY), 6, 160);
            elemento.ancho = Math.max(20, redondear(objeto.width * escalaX));
            objeto.set({
                scaleX: 1,
                scaleY: 1,
                width: elemento.ancho,
                fontSize: elemento.tamano_letra,
            });
            objeto.initDimensions();
            elemento.alto = Math.max(20, redondear(objeto.height));
        } else if (objeto instanceof Line) {
            objeto.scaleX = 1;
            objeto.scaleY = 1;
        } else {
            objeto.scaleX = 1;
            objeto.scaleY = 1;
            objeto.set({ width: elemento.ancho, height: elemento.alto });
        }

        // Una capa soltada cerca del borde siempre queda dentro de la hoja y continúa visible.
        ajustarElementoDentroDelLienzo(elemento, medidasPapel());
        objeto.set({
            left: elemento.posicion_x,
            top: elemento.posicion_y,
            ...(objeto instanceof Line ? {} : {
                width: elemento.ancho,
                height: objeto instanceof Textbox ? objeto.height : elemento.alto,
            }),
        });
        if (objeto instanceof Textbox) objeto.initDimensions();
        objeto.setCoords();
        actualizandoDesdeLienzo = false;
        return true;
    }

    function ajustarElementoDentroDelLienzo(elemento, medidas) {
        elemento.ancho = Math.min(Math.max(1, numero(elemento.ancho, 1)), medidas.ancho);
        elemento.alto = Math.min(Math.max(1, numero(elemento.alto, 1)), medidas.alto);
        elemento.posicion_x = limitar(numero(elemento.posicion_x, 0), 0, Math.max(0, medidas.ancho - elemento.ancho));
        elemento.posicion_y = limitar(numero(elemento.posicion_y, 0), 0, Math.max(0, medidas.alto - elemento.alto));
    }

    function redondear(valor) {
        return Math.round(numero(valor, 0) * 100) / 100;
    }

    function configurarEventosLienzo() {
        lienzo.on('selection:created', seleccionarDesdeLienzo);
        lienzo.on('selection:updated', seleccionarDesdeLienzo);
        lienzo.on('selection:cleared', () => {
            if (renderizandoLienzo) return;
            indiceSeleccionado = null;
            indicesSeleccionadosEstado = [];
            renderizarCapas();
            renderizarPropiedades();
        });
        lienzo.on('before:transform', guardarHistorial);
        lienzo.on('object:modified', (evento) => {
            ocultarGuiasAlineacion();
            sincronizarDesdeObjeto(evento.target);
        });
        lienzo.on('text:editing:entered', guardarHistorial);
        lienzo.on('text:changed', (evento) => sincronizarTexto(evento.target));
        lienzo.on('object:moving', (evento) => {
            if (!evento.target) return;
            const omitirAjuste = Boolean(evento.e?.altKey);
            const aplicarIman = imanActivo && !omitirAjuste;
            const ajustarACuadricula = cuadriculaVisible && aplicarIman;
            const ajusteGuias = ajustarObjetoConGuias(evento.target, aplicarIman);

            if (ajustarACuadricula) {
                evento.target.set({
                    left: ajusteGuias.vertical
                        ? evento.target.left
                        : Math.round(evento.target.left / pasoCuadricula) * pasoCuadricula,
                    top: ajusteGuias.horizontal
                        ? evento.target.top
                        : Math.round(evento.target.top / pasoCuadricula) * pasoCuadricula,
                });
            }
            evento.target.setCoords();
            mantenerObjetoDentroDelLienzo(evento.target);
        });
        lienzo.on('mouse:up', ocultarGuiasAlineacion);

        controles.envolturaLienzo?.addEventListener('wheel', (evento) => {
            if (!evento.ctrlKey) return;
            evento.preventDefault();
            const caja = controles.envolturaLienzo.getBoundingClientRect();
            const puntoX = (controles.envolturaLienzo.scrollLeft + evento.clientX - caja.left) / zoom;
            const puntoY = (controles.envolturaLienzo.scrollTop + evento.clientY - caja.top) / zoom;
            zoomAjustadoAutomaticamente = false;
            zoom = limitar(zoom + (evento.deltaY < 0 ? 0.1 : -0.1), 0.3, 2);
            aplicarZoom();
            controles.envolturaLienzo.scrollLeft = puntoX * zoom - (evento.clientX - caja.left);
            controles.envolturaLienzo.scrollTop = puntoY * zoom - (evento.clientY - caja.top);
        }, { passive: false });

        configurarDesplazamientoLienzo();

        const aceptarCampoArrastrado = (evento) => {
            evento.preventDefault();
            evento.dataTransfer.dropEffect = 'copy';
            controles.escenario?.classList.add('is-drop-target');
        };
        const quitarIndicadorArrastre = (evento) => {
            if (evento.relatedTarget && controles.escenario?.contains(evento.relatedTarget)) return;
            controles.escenario?.classList.remove('is-drop-target');
        };
        const soltarCampoEnLienzo = (evento) => {
            evento.preventDefault();
            evento.stopPropagation();
            controles.escenario?.classList.remove('is-drop-target');
            const codigo = evento.dataTransfer.getData('application/x-plantilla-campo')
                || evento.dataTransfer.getData('text/plain')
                || codigoCampoArrastrado;
            if (!codigo) return;
            const caja = lienzo.upperCanvasEl.getBoundingClientRect();
            const medidas = medidasPapel();
            const posicionX = limitar((evento.clientX - caja.left) / zoom, 0, medidas.ancho - anchoInicial('CAMPO'));
            const posicionY = limitar((evento.clientY - caja.top) / zoom, 0, medidas.alto - altoInicial('CAMPO'));
            agregarElemento(codigo.endsWith('.tabla') ? 'TABLA' : 'CAMPO', codigo, {
                posicion_x: posicionX,
                posicion_y: posicionY,
            });
            codigoCampoArrastrado = '';
        };

        // Fabric usa un canvas superior para los eventos; por eso el arrastre se escucha allí y en el escenario.
        [controles.escenario, lienzo.upperCanvasEl].filter(Boolean).forEach((destino) => {
            destino.addEventListener('dragenter', aceptarCampoArrastrado);
            destino.addEventListener('dragover', aceptarCampoArrastrado);
            destino.addEventListener('dragleave', quitarIndicadorArrastre);
            destino.addEventListener('drop', soltarCampoEnLienzo);
        });
    }

    function configurarDesplazamientoLienzo() {
        if (!controles.envolturaLienzo || !lienzo.upperCanvasEl) return;
        let desplazando = false;
        let inicioX = 0;
        let inicioY = 0;
        let scrollX = 0;
        let scrollY = 0;

        lienzo.upperCanvasEl.addEventListener('mousedown', (evento) => {
            if (evento.button !== 1 && !(teclaEspacioActiva && evento.button === 0)) return;
            evento.preventDefault();
            evento.stopImmediatePropagation();
            desplazando = true;
            inicioX = evento.clientX;
            inicioY = evento.clientY;
            scrollX = controles.envolturaLienzo.scrollLeft;
            scrollY = controles.envolturaLienzo.scrollTop;
            lienzo.upperCanvasEl.style.cursor = 'grabbing';
        }, true);

        window.addEventListener('mousemove', (evento) => {
            if (!desplazando) return;
            controles.envolturaLienzo.scrollLeft = scrollX - (evento.clientX - inicioX);
            controles.envolturaLienzo.scrollTop = scrollY - (evento.clientY - inicioY);
        });
        window.addEventListener('mouseup', () => {
            if (!desplazando) return;
            desplazando = false;
            lienzo.upperCanvasEl.style.cursor = teclaEspacioActiva ? 'grab' : '';
        });
    }

    function seleccionarDesdeLienzo(evento) {
        const objetos = evento.target instanceof ActiveSelection
            ? evento.target.getObjects()
            : (evento.selected?.length ? evento.selected : [evento.target]);
        const indices = objetos
            .map((objeto) => objeto?.plantillaIndice)
            .filter(Number.isInteger);
        if (!indices.length) return;

        // Un clic siempre selecciona la capa alcanzada; agrupar no bloquea la edición individual.
        establecerSeleccion(indices);
        abrirPanelLateral('propiedades');
        renderizarCapas();
        renderizarPropiedades();
    }

    function sincronizarTexto(objeto) {
        if (!(objeto instanceof Textbox) || !Number.isInteger(objeto.plantillaIndice)) return;
        elementos[objeto.plantillaIndice].texto_fijo = objeto.text;
        elementos[objeto.plantillaIndice].configuracion.estilos_texto = clonarEstilosTexto(objeto.styles);
        elementos[objeto.plantillaIndice].alto = redondear(objeto.getScaledHeight());
        actualizarInput();
        renderizarCapas();
    }

    function configurarCamposDisponibles() {
        campos.forEach((campo) => {
            campo.boton.draggable = true;
            campo.boton.addEventListener('dragstart', (evento) => {
                campoSeleccionado = campo.codigo;
                codigoCampoArrastrado = campo.codigo;
                evento.dataTransfer.effectAllowed = 'copy';
                evento.dataTransfer.setData('application/x-plantilla-campo', campo.codigo);
                evento.dataTransfer.setData('text/plain', campo.codigo);
            });
            campo.boton.addEventListener('dragend', () => {
                codigoCampoArrastrado = '';
                controles.escenario?.classList.remove('is-drop-target');
            });
            campo.boton.addEventListener('click', () => {
                campoSeleccionado = campo.codigo;
                insertarCampoEnSeleccion(campo.codigo);
            });
        });

        controles.buscarCampo?.addEventListener('input', () => {
            const termino = controles.buscarCampo.value.trim().toLowerCase();
            campos.forEach((campo) => {
                campo.boton.hidden = !`${campo.nombre} ${campo.codigo}`.toLowerCase().includes(termino);
            });
        });
    }

    function configurarBarraHerramientas() {
        document.querySelectorAll('[data-plantilla-tool]').forEach((boton) => {
            boton.addEventListener('click', () => ejecutarHerramienta(boton.dataset.plantillaTool));
        });
        document.querySelector('[data-plantilla-tool="grid"]')?.classList.toggle('is-active', cuadriculaVisible);
        document.querySelector('[data-plantilla-tool="snap"]')?.classList.toggle('is-active', imanActivo);
        document.querySelector('[data-plantilla-alinear]')?.addEventListener('change', (evento) => {
            if (evento.target.value) alinearSeleccion(evento.target.value);
            evento.target.value = '';
        });
        controles.selectorSeleccion?.addEventListener('change', (evento) => {
            if (evento.target.value) ejecutarAccionSeleccion(evento.target.value);
            evento.target.value = '';
        });
        controles.selectorDistribucion?.addEventListener('change', (evento) => {
            if (evento.target.value) distribuirSeleccion(evento.target.value);
            evento.target.value = '';
        });
        controles.selectorExportacion?.addEventListener('change', (evento) => {
            if (evento.target.value) exportarVistaPrevia(evento.target.value);
            evento.target.value = '';
        });
        document.querySelectorAll('[data-plantilla-zoom]').forEach((boton) => {
            boton.addEventListener('click', () => cambiarZoom(boton.dataset.plantillaZoom));
        });
        controles.selectorTipoCuadricula?.addEventListener('change', (evento) => {
            tipoCuadricula = evento.target.value === 'PUNTOS' ? 'PUNTOS' : 'LINEAS';
            actualizarCuadricula();
        });
        controles.selectorTamanoCuadricula?.addEventListener('change', (evento) => {
            const pasoSolicitado = Number(evento.target.value);
            pasoCuadricula = PASOS_CUADRICULA_PERMITIDOS.includes(pasoSolicitado)
                ? pasoSolicitado
                : PASO_CUADRICULA_PREDETERMINADO;
            actualizarCuadricula();
        });
    }

    function ejecutarHerramienta(accion) {
        const acciones = {
            texto: () => agregarElemento('TEXTO'),
            tabla: crearTablaDatos,
            firma: () => agregarElemento('FIRMA', 'firma.director'),
            qr: () => agregarElemento('QR', 'qr.verificacion'),
            imagen: () => seleccionarImagen((imagen) => agregarElemento('IMAGEN', null, { texto_fijo: imagen })),
            forma: () => agregarElemento('FORMA'),
            linea: () => agregarElemento('LINEA'),
            deshacer,
            rehacer,
            duplicar: duplicarSeleccion,
            eliminar: eliminarSeleccion,
            grid: alternarCuadricula,
            snap: alternarIman,
            maximizar: alternarModoMaximo,
        };
        acciones[accion]?.();
    }

    function agregarElemento(tipo, codigo = null, datos = {}) {
        guardarHistorial();
        const nuevo = normalizarElemento({
            ...datos,
            tipo_elemento: tipo,
            codigo_elemento: codigo,
            texto_fijo: datos.texto_fijo ?? textoInicial(tipo, codigo),
            posicion_x: datos.posicion_x ?? 36 + (elementos.length % 3) * 36,
            posicion_y: datos.posicion_y ?? 48 + (elementos.length % 5) * 42,
            ancho: anchoInicial(tipo),
            alto: altoInicial(tipo),
        });
        elementos.push(nuevo);
        establecerSeleccion([elementos.length - 1]);
        abrirPanelLateral('propiedades');
        renderizarTodo();
    }

    function alternarModoMaximo() {
        if (!controles.disenador) return;
        const maximizado = controles.disenador.classList.toggle('is-maximized');
        document.body.classList.toggle('plantilla-editor-maximizado', maximizado);
        const boton = document.querySelector('[data-plantilla-tool="maximizar"]');

        boton?.classList.toggle('is-active', maximizado);
        boton?.querySelector('i')?.classList.toggle('fa-expand', !maximizado);
        boton?.querySelector('i')?.classList.toggle('fa-compress', maximizado);
        const etiqueta = maximizado ? 'Salir de pantalla completa' : 'Maximizar el área de diseño';
        boton?.setAttribute('title', etiqueta);
        boton?.setAttribute('aria-label', etiqueta);

        // Esperamos a que el navegador termine de ampliar el área antes de calcular el zoom.
        requestAnimationFrame(ajustarZoomAlArea);
    }

    function ejecutarAccionSeleccion(accion) {
        const acciones = {
            TODO: seleccionarTodo,
            AGRUPAR: agruparSeleccion,
            DESAGRUPAR: desagruparSeleccion,
        };
        acciones[accion]?.();
    }

    function seleccionarTodo() {
        const indices = elementos
            .map((elemento, indice) => elemento.configuracion.visible && !elemento.configuracion.bloqueado ? indice : null)
            .filter(Number.isInteger);
        seleccionarIndices(indices);
    }

    function agruparSeleccion() {
        const indices = indicesSeleccionados();
        if (indices.length < 2) return;
        guardarHistorial();
        const grupo = `grupo_${Date.now()}`;
        indices.forEach((indice) => {
            elementos[indice].configuracion.grupo = grupo;
        });
        establecerSeleccion(indices);
        actualizarInput();
        renderizarCapas();
        renderizarPropiedades();
    }

    function desagruparSeleccion() {
        const indicesIniciales = indicesSeleccionados();
        if (!indicesIniciales.length) return;
        const grupos = new Set(indicesIniciales.map((indice) => elementos[indice]?.configuracion.grupo).filter(Boolean));
        const indices = elementos
            .map((elemento, indice) => grupos.has(elemento.configuracion.grupo) || indicesIniciales.includes(indice) ? indice : null)
            .filter(Number.isInteger);
        guardarHistorial();
        indices.forEach((indice) => {
            elementos[indice].configuracion.grupo = '';
        });
        establecerSeleccion(indicesIniciales);
        actualizarInput();
        renderizarCapas();
        renderizarPropiedades();
    }

    function exportarVistaPrevia(formato) {
        const nombre = `plantilla-certificado.${formato === 'SVG' ? 'svg' : 'png'}`;
        const enlace = document.createElement('a');
        const seleccion = [...indicesSeleccionadosEstado];

        try {
            renderizandoLienzo = true;
            lienzo.discardActiveObject();
            renderizandoLienzo = false;
            lienzo.requestRenderAll();

            if (formato === 'SVG') {
                const archivo = new Blob([lienzo.toSVG()], { type: 'image/svg+xml;charset=utf-8' });
                enlace.href = URL.createObjectURL(archivo);
                setTimeout(() => URL.revokeObjectURL(enlace.href), 1000);
            } else {
                enlace.href = lienzo.toDataURL({
                    format: 'png',
                    multiplier: CALIDAD_EXPORTACION,
                    enableRetinaScaling: false,
                });
            }
            enlace.download = nombre;
            enlace.click();
        } catch (error) {
            window.alert('No se pudo exportar la vista previa. Revise que las imágenes usadas pertenezcan al sistema.');
        } finally {
            establecerSeleccion(seleccion);
            restaurarSeleccionEnLienzo();
            lienzo.requestRenderAll();
        }
    }

    function insertarCampoEnSeleccion(codigo) {
        const objeto = lienzo.getActiveObject();
        if (!(objeto instanceof Textbox) || !Number.isInteger(objeto.plantillaIndice)) {
            agregarElemento(codigo.endsWith('.tabla') ? 'TABLA' : 'CAMPO', codigo);
            return;
        }

        guardarHistorial();
        const posicion = objeto.isEditing ? objeto.selectionStart : objeto.text.length;
        const valor = marcador(codigo);
        objeto.text = `${objeto.text.slice(0, posicion)}${valor}${objeto.text.slice(posicion)}`;
        objeto.selectionStart = posicion + valor.length;
        objeto.selectionEnd = posicion + valor.length;
        objeto.initDimensions();
        sincronizarTexto(objeto);
        lienzo.requestRenderAll();
    }

    function seleccionarImagen(callback) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png,image/jpeg,image/webp';
        input.addEventListener('change', () => {
            const archivo = input.files?.[0];
            if (!archivo) return;
            const lector = new FileReader();
            lector.onload = () => callback(String(lector.result || ''));
            lector.readAsDataURL(archivo);
        });
        input.click();
    }

    function duplicarSeleccion() {
        const indices = indicesSeleccionados();
        if (!indices.length) return;
        guardarHistorial();
        const grupoNuevo = indices.length > 1 ? `grupo_${Date.now()}` : '';
        const nuevosIndices = [];
        indices.forEach((indice) => {
            const copia = normalizarElemento(JSON.parse(JSON.stringify(elementos[indice])));
            copia.posicion_x += 18;
            copia.posicion_y += 18;
            copia.configuracion.grupo = grupoNuevo;
            copia.configuracion.nombre_capa = copia.configuracion.nombre_capa
                ? `${copia.configuracion.nombre_capa} copia`
                : '';
            elementos.push(copia);
            nuevosIndices.push(elementos.length - 1);
        });
        establecerSeleccion(nuevosIndices);
        renderizarTodo();
    }

    function eliminarSeleccion() {
        const indices = indicesSeleccionados().sort((a, b) => b - a);
        if (!indices.length) return;
        guardarHistorial();
        indices.forEach((indice) => elementos.splice(indice, 1));
        establecerSeleccion(elementos.length ? [Math.min(indices.at(-1), elementos.length - 1)] : []);
        renderizarTodo();
    }

    function alternarBloqueo() {
        const indices = indicesSeleccionados();
        if (!indices.length) return;
        guardarHistorial();
        const bloquear = indices.some((indice) => !elementos[indice].configuracion.bloqueado);
        indices.forEach((indice) => {
            elementos[indice].configuracion.bloqueado = bloquear;
        });
        renderizarTodo();
    }

    function alternarCuadricula() {
        cuadriculaVisible = !cuadriculaVisible;
        actualizarCuadricula();
    }

    function alternarIman() {
        imanActivo = !imanActivo;
        document.querySelector('[data-plantilla-tool="snap"]')?.classList.toggle('is-active', imanActivo);
        if (!imanActivo) ocultarGuiasAlineacion();
        lienzo.requestRenderAll();
    }

    function actualizarCuadricula() {
        controles.escenario?.classList.toggle('is-grid', cuadriculaVisible);
        controles.escenario?.classList.toggle('is-grid-points', tipoCuadricula === 'PUNTOS');
        controles.escenario?.style.setProperty('--plantilla-grid-step', `${pasoCuadricula * zoom}px`);
        document.querySelector('[data-plantilla-tool="grid"]')?.classList.toggle('is-active', cuadriculaVisible);
        actualizarReglasLienzo(medidasPapel());
        lienzo.requestRenderAll();
    }

    function alinearSeleccion(tipo) {
        const seleccionados = indicesSeleccionados().map((indice) => elementos[indice]).filter(Boolean);
        if (!seleccionados.length) return;

        if (['MISMO_ANCHO', 'MISMO_ALTO', 'MISMO_TAMANO'].includes(tipo)) {
            igualarTamanoSeleccion(seleccionados, tipo);
            return;
        }

        guardarHistorial();
        const medidas = medidasPapel();
        const limites = limitesElementos(seleccionados);
        const esSeleccionMultiple = seleccionados.length > 1;

        if (esSeleccionMultiple) {
            const centroX = limites.izquierda + limites.ancho / 2;
            const centroY = limites.arriba + limites.alto / 2;
            seleccionados.forEach((elemento) => {
                if (tipo === 'IZQUIERDA') elemento.posicion_x = limites.izquierda;
                if (tipo === 'CENTRO_H') elemento.posicion_x = centroX - elemento.ancho / 2;
                if (tipo === 'DERECHA') elemento.posicion_x = limites.derecha - elemento.ancho;
                if (tipo === 'ARRIBA') elemento.posicion_y = limites.arriba;
                if (tipo === 'CENTRO_V') elemento.posicion_y = centroY - elemento.alto / 2;
                if (tipo === 'ABAJO') elemento.posicion_y = limites.abajo - elemento.alto;
            });
        } else {
            const elemento = seleccionados[0];
            if (tipo === 'IZQUIERDA') elemento.posicion_x = 0;
            if (tipo === 'CENTRO_H') elemento.posicion_x = (medidas.ancho - elemento.ancho) / 2;
            if (tipo === 'DERECHA') elemento.posicion_x = medidas.ancho - elemento.ancho;
            if (tipo === 'ARRIBA') elemento.posicion_y = 0;
            if (tipo === 'CENTRO_V') elemento.posicion_y = (medidas.alto - elemento.alto) / 2;
            if (tipo === 'ABAJO') elemento.posicion_y = medidas.alto - elemento.alto;
        }
        renderizarTodo();
    }

    function igualarTamanoSeleccion(seleccionados, tipo) {
        if (seleccionados.length < 2) return;
        guardarHistorial();
        const referencia = seleccionados[0];

        seleccionados.slice(1).forEach((elemento) => {
            if (tipo === 'MISMO_ANCHO' || tipo === 'MISMO_TAMANO') {
                elemento.ancho = referencia.ancho;
            }
            if (tipo === 'MISMO_ALTO' || tipo === 'MISMO_TAMANO') {
                elemento.alto = referencia.alto;
            }
        });

        renderizarTodo();
    }

    function limitesElementos(seleccionados) {
        const izquierda = Math.min(...seleccionados.map((elemento) => elemento.posicion_x));
        const arriba = Math.min(...seleccionados.map((elemento) => elemento.posicion_y));
        const derecha = Math.max(...seleccionados.map((elemento) => elemento.posicion_x + elemento.ancho));
        const abajo = Math.max(...seleccionados.map((elemento) => elemento.posicion_y + elemento.alto));
        return { izquierda, arriba, derecha, abajo, ancho: derecha - izquierda, alto: abajo - arriba };
    }

    function distribuirSeleccion(direccion) {
        const indices = indicesSeleccionados();
        if (indices.length < 3) return;
        guardarHistorial();
        const horizontal = direccion === 'HORIZONTAL';
        const seleccionados = indices
            .map((indice) => elementos[indice])
            .sort((a, b) => (horizontal ? a.posicion_x - b.posicion_x : a.posicion_y - b.posicion_y));
        const inicio = horizontal ? seleccionados[0].posicion_x : seleccionados[0].posicion_y;
        const final = horizontal
            ? seleccionados.at(-1).posicion_x + seleccionados.at(-1).ancho
            : seleccionados.at(-1).posicion_y + seleccionados.at(-1).alto;
        const ocupado = seleccionados.reduce((total, elemento) => total + (horizontal ? elemento.ancho : elemento.alto), 0);
        const espacio = Math.max(0, (final - inicio - ocupado) / (seleccionados.length - 1));
        let cursor = inicio;
        seleccionados.forEach((elemento) => {
            if (horizontal) elemento.posicion_x = cursor;
            else elemento.posicion_y = cursor;
            cursor += (horizontal ? elemento.ancho : elemento.alto) + espacio;
        });
        renderizarTodo();
    }

    function cambiarZoom(accion) {
        if (accion === 'ajustar') {
            ajustarZoomAlArea();
            return;
        }

        zoomAjustadoAutomaticamente = false;
        if (accion === 'restablecer') zoom = 1;
        if (accion === 'mas') zoom = limitar(zoom + 0.1, 0.3, 2);
        if (accion === 'menos') zoom = limitar(zoom - 0.1, 0.3, 2);
        aplicarZoom();
    }

    function ajustarZoomAlArea() {
        const medidas = medidasPapel();
        const anchoDisponible = Math.max(260, (controles.envolturaLienzo?.clientWidth || medidas.ancho) - 40);
        const parteSuperior = controles.envolturaLienzo?.getBoundingClientRect().top || 0;
        const altoDisponible = Math.max(360, window.innerHeight - parteSuperior - 36);
        zoom = limitar(Math.min(anchoDisponible / medidas.ancho, altoDisponible / medidas.alto, 1), 0.3, 1);
        zoomAjustadoAutomaticamente = true;
        aplicarZoom();
    }

    function aplicarZoom() {
        const medidas = medidasPapel();
        const contenedorFabric = lienzo.wrapperEl;
        contenedorFabric.style.transform = `scale(${zoom})`;
        contenedorFabric.style.transformOrigin = 'top left';
        if (controles.escenario) {
            controles.escenario.style.width = `${medidas.ancho * zoom}px`;
            controles.escenario.style.height = `${medidas.alto * zoom}px`;
            controles.escenario.style.setProperty('--plantilla-grid-step', `${pasoCuadricula * zoom}px`);
        }
        actualizarReglasLienzo(medidas);
        if (controles.zoom) controles.zoom.textContent = `${Math.round(zoom * 100)}%`;
    }

    function crearReglasLienzo() {
        if (!controles.escenario) return {};
        const horizontal = document.createElement('div');
        const vertical = document.createElement('div');
        horizontal.className = 'plantilla-ruler is-horizontal';
        vertical.className = 'plantilla-ruler is-vertical';
        horizontal.setAttribute('aria-hidden', 'true');
        vertical.setAttribute('aria-hidden', 'true');
        controles.escenario.append(horizontal, vertical);
        return { horizontal, vertical };
    }

    function actualizarReglasLienzo(medidas) {
        if (!reglas.horizontal || !reglas.vertical) return;
        const paso = Math.max(2, pasoCuadricula * zoom);
        const pasoMayor = paso * 5;
        reglas.horizontal.style.width = `${medidas.ancho * zoom}px`;
        reglas.vertical.style.height = `${medidas.alto * zoom}px`;
        [reglas.horizontal, reglas.vertical].forEach((regla) => {
            regla.style.setProperty('--ruler-step', `${paso}px`);
            regla.style.setProperty('--ruler-major-step', `${pasoMayor}px`);
        });

        const marcasHorizontales = [];
        const marcasVerticales = [];
        for (let posicion = 0; posicion <= medidas.ancho; posicion += 100) {
            marcasHorizontales.push(`<span style="left:${posicion * zoom}px">${posicion}</span>`);
        }
        for (let posicion = 0; posicion <= medidas.alto; posicion += 100) {
            marcasVerticales.push(`<span style="top:${posicion * zoom}px">${posicion}</span>`);
        }
        reglas.horizontal.innerHTML = marcasHorizontales.join('');
        reglas.vertical.innerHTML = marcasVerticales.join('');
    }

    function crearGuiasAlineacion() {
        if (!controles.escenario) return {};
        const vertical = document.createElement('span');
        const horizontal = document.createElement('span');
        vertical.className = 'plantilla-alignment-guide is-vertical';
        horizontal.className = 'plantilla-alignment-guide is-horizontal';
        controles.escenario.append(vertical, horizontal);
        return { vertical, horizontal };
    }

    function ajustarObjetoConGuias(objeto, aplicarAjuste) {
        const medidas = medidasPapel();
        objeto.setCoords();
        const caja = objeto.getBoundingRect();
        const centro = objeto.getCenterPoint();
        const tolerancia = TOLERANCIA_GUIA / zoom;
        const objetosDelMovimiento = new Set(
            objeto instanceof ActiveSelection ? objeto.getObjects() : [objeto],
        );
        const referenciasVerticales = [0, medidas.ancho / 2, medidas.ancho];
        const referenciasHorizontales = [0, medidas.alto / 2, medidas.alto];

        // Los bordes y centros de los demás campos funcionan como guías magnéticas.
        lienzo.getObjects()
            .filter((otro) => !objetosDelMovimiento.has(otro) && otro.visible !== false)
            .forEach((otro) => {
                otro.setCoords();
                const cajaOtro = otro.getBoundingRect();
                const centroOtro = otro.getCenterPoint();
                referenciasVerticales.push(cajaOtro.left, centroOtro.x, cajaOtro.left + cajaOtro.width);
                referenciasHorizontales.push(cajaOtro.top, centroOtro.y, cajaOtro.top + cajaOtro.height);
            });

        const vertical = guiaMasCercana(
            [caja.left, centro.x, caja.left + caja.width],
            referenciasVerticales,
            tolerancia,
        );
        const horizontal = guiaMasCercana(
            [caja.top, centro.y, caja.top + caja.height],
            referenciasHorizontales,
            tolerancia,
        );

        // Las guías se muestran siempre, pero no cambian la posición si el imán está apagado.
        if (aplicarAjuste && vertical) objeto.left += vertical.diferencia;
        if (aplicarAjuste && horizontal) objeto.top += horizontal.diferencia;
        mostrarGuia(guias.vertical, vertical?.posicion, 'vertical');
        mostrarGuia(guias.horizontal, horizontal?.posicion, 'horizontal');
        objeto.setCoords();

        return {
            vertical: Boolean(aplicarAjuste && vertical),
            horizontal: Boolean(aplicarAjuste && horizontal),
        };
    }

    function guiaMasCercana(puntosObjeto, referencias, tolerancia) {
        let mejor = null;

        puntosObjeto.forEach((actual) => {
            referencias.forEach((posicion) => {
                const diferencia = posicion - actual;
                const distancia = Math.abs(diferencia);

                if (distancia <= tolerancia && (!mejor || distancia < mejor.distancia)) {
                    mejor = { posicion, actual, diferencia, distancia };
                }
            });
        });

        return mejor;
    }

    function mantenerObjetoDentroDelLienzo(objeto) {
        const medidas = medidasPapel();
        objeto.setCoords();
        const caja = objeto.getBoundingRect();
        let desplazamientoX = 0;
        let desplazamientoY = 0;

        if (caja.width <= medidas.ancho) {
            if (caja.left < 0) desplazamientoX = -caja.left;
            if (caja.left + caja.width > medidas.ancho) desplazamientoX = medidas.ancho - caja.left - caja.width;
        }
        if (caja.height <= medidas.alto) {
            if (caja.top < 0) desplazamientoY = -caja.top;
            if (caja.top + caja.height > medidas.alto) desplazamientoY = medidas.alto - caja.top - caja.height;
        }

        if (desplazamientoX || desplazamientoY) {
            objeto.set({
                left: numero(objeto.left) + desplazamientoX,
                top: numero(objeto.top) + desplazamientoY,
            });
            objeto.setCoords();
        }
    }

    function mostrarGuia(guia, posicion, orientacion) {
        if (!guia || posicion === undefined) {
            guia?.classList.remove('is-visible');
            return;
        }
        const medidas = medidasPapel();
        guia.style[orientacion === 'vertical' ? 'left' : 'top'] = `${posicion * zoom}px`;
        guia.style[orientacion === 'vertical' ? 'height' : 'width'] = `${(orientacion === 'vertical' ? medidas.alto : medidas.ancho) * zoom}px`;
        guia.classList.add('is-visible');
    }

    function ocultarGuiasAlineacion() {
        guias.vertical?.classList.remove('is-visible');
        guias.horizontal?.classList.remove('is-visible');
    }

    function configurarPaneles() {
        controles.pestanasLaterales.forEach((pestana) => {
            pestana.addEventListener('click', () => abrirPanelLateral(pestana.dataset.plantillaSidebarTab));
        });

        controles.capas?.addEventListener('click', (evento) => {
            const seleccionar = evento.target.closest('[data-capa-index]');
            const visible = evento.target.closest('[data-capa-visible]');
            const bloquear = evento.target.closest('[data-capa-bloquear]');
            const subir = evento.target.closest('[data-capa-subir]');
            const bajar = evento.target.closest('[data-capa-bajar]');
            if (visible) {
                evento.stopPropagation();
                alternarVisibilidad(Number(visible.dataset.capaVisible));
                return;
            }
            if (bloquear) {
                evento.stopPropagation();
                seleccionarIndice(Number(bloquear.dataset.capaBloquear));
                alternarBloqueo();
                return;
            }
            if (subir) {
                evento.stopPropagation();
                moverCapaUnNivel(Number(subir.dataset.capaSubir), 1);
                return;
            }
            if (bajar) {
                evento.stopPropagation();
                moverCapaUnNivel(Number(bajar.dataset.capaBajar), -1);
                return;
            }
            if (seleccionar) seleccionarIndice(Number(seleccionar.dataset.capaIndex));
        });

        controles.propiedades?.addEventListener('change', actualizarPropiedad);
        controles.propiedades?.addEventListener('input', actualizarPropiedad);
        controles.propiedades?.addEventListener('focusin', (evento) => {
            if (!editandoPropiedad && evento.target.matches('input, select, textarea')) {
                guardarHistorial();
                editandoPropiedad = true;
            }
        });
        controles.propiedades?.addEventListener('focusout', () => {
            editandoPropiedad = false;
        });
        controles.propiedades?.addEventListener('click', (evento) => {
            const elementoTabla = elementoSeleccionado();
            const agregarDato = evento.target.closest('[data-tabla-agregar]');
            const quitarDato = evento.target.closest('[data-tabla-quitar]');
            const subirDato = evento.target.closest('[data-tabla-subir]');
            const bajarDato = evento.target.closest('[data-tabla-bajar]');
            if (elementoTabla && esTablaDatos(elementoTabla) && (agregarDato || quitarDato || subirDato || bajarDato)) {
                evento.preventDefault();
                guardarHistorial();
                const entradas = entradasTablaDatos(elementoTabla);
                if (agregarDato) {
                    entradas.push({ etiqueta: '', codigo: '' });
                } else if (quitarDato) {
                    entradas.splice(Number(quitarDato.dataset.tablaQuitar), 1);
                } else {
                    const botonMovimiento = subirDato || bajarDato;
                    const origen = Number(subirDato?.dataset.tablaSubir ?? bajarDato?.dataset.tablaBajar);
                    const destino = origen + (subirDato ? -1 : 1);
                    if (destino >= 0 && destino < entradas.length) {
                        [entradas[origen], entradas[destino]] = [entradas[destino], entradas[origen]];
                    }
                }
                reagruparTablaDatos(
                    elementoTabla,
                    entradas.length ? entradas : [{ etiqueta: '', codigo: '' }],
                    elementoTabla.configuracion.tabla.pares_por_fila,
                    false,
                );
                renderizarTodo();
                return;
            }
            if (evento.target.closest('[data-prop-eliminar]')) eliminarSeleccion();
            if (evento.target.closest('[data-prop-imagen]')) {
                seleccionarImagen((imagen) => {
                    const elemento = elementoSeleccionado();
                    if (!elemento) return;
                    guardarHistorial();
                    elemento.texto_fijo = imagen;
                    renderizarTodo();
                });
            }
            const formato = evento.target.closest('[data-prop-formato]');
            if (formato) {
                alternarFormatoTexto(formato.dataset.propFormato);
            }
            const capitalizacion = evento.target.closest('[data-prop-capitalizacion]');
            if (capitalizacion) {
                transformarCapitalizacionTexto(capitalizacion.dataset.propCapitalizacion);
            }
        });
        controles.propiedades?.addEventListener('mousedown', (evento) => {
            if (evento.target.closest('[data-prop-formato], [data-prop-capitalizacion]')) evento.preventDefault();
        });
    }

    function alternarFormatoTexto(formato) {
        const elemento = elementoSeleccionado();
        const objeto = objetoPorIndice(indiceSeleccionado);
        if (!elemento || !['negrita', 'cursiva', 'subrayado'].includes(formato)) return;

        const inicio = Number(objeto?.selectionStart);
        const fin = Number(objeto?.selectionEnd);
        const seleccionParcial = elemento.tipo_elemento === 'TEXTO'
            && objeto instanceof Textbox
            && Number.isInteger(inicio)
            && Number.isInteger(fin)
            && fin > inicio;

        guardarHistorial();
        if (!seleccionParcial) {
            elemento[formato] = !elemento[formato];
            renderizarTodo();
            return;
        }

        const propiedades = {
            negrita: ['fontWeight', 'bold', 'normal'],
            cursiva: ['fontStyle', 'italic', 'normal'],
            subrayado: ['underline', true, false],
        };
        const [propiedad, activo, inactivo] = propiedades[formato];
        const estilosSeleccionados = objeto.getSelectionStyles(inicio, fin, true);
        const valorBase = formato === 'negrita'
            ? (elemento.negrita ? 'bold' : 'normal')
            : (formato === 'cursiva' ? (elemento.cursiva ? 'italic' : 'normal') : Boolean(elemento.subrayado));
        const todosActivos = estilosSeleccionados.length > 0
            && estilosSeleccionados.every((estilo) => (estilo[propiedad] ?? valorBase) === activo);

        objeto.setSelectionStyles({ [propiedad]: todosActivos ? inactivo : activo }, inicio, fin);
        objeto.dirty = true;
        elemento.configuracion.estilos_texto = clonarEstilosTexto(objeto.styles);
        actualizarInput();
        lienzo.requestRenderAll();
    }

    function transformarFragmentoTexto(texto, modo) {
        if (modo === 'mayusculas') return texto.toLocaleUpperCase('es-BO');
        if (modo === 'minusculas') return texto.toLocaleLowerCase('es-BO');
        if (modo === 'oracion') {
            const minusculas = texto.toLocaleLowerCase('es-BO');
            return minusculas.replace(/(^|[.!?]\s+)(\p{L})/gu, (coincidencia, prefijo, letra) => (
                `${prefijo}${letra.toLocaleUpperCase('es-BO')}`
            ));
        }
        return texto;
    }

    function transformarSeleccionProtegiendoMarcadores(texto, inicio, fin, modo) {
        const caracteres = Array.from(String(texto || ''));
        const protegidos = new Set();
        const patron = /\{\{\s*[^}]+\s*\}\}/g;
        let coincidencia;

        while ((coincidencia = patron.exec(texto)) !== null) {
            const desde = Array.from(texto.slice(0, coincidencia.index)).length;
            const hasta = desde + Array.from(coincidencia[0]).length;
            for (let indice = desde; indice < hasta; indice += 1) protegidos.add(indice);
        }

        const resultado = [];
        let cursor = inicio;
        while (cursor < fin) {
            if (protegidos.has(cursor)) {
                resultado.push(caracteres[cursor]);
                cursor += 1;
                continue;
            }

            let finalFragmento = cursor;
            while (finalFragmento < fin && !protegidos.has(finalFragmento)) finalFragmento += 1;
            resultado.push(transformarFragmentoTexto(caracteres.slice(cursor, finalFragmento).join(''), modo));
            cursor = finalFragmento;
        }

        return resultado.join('');
    }

    function transformarCapitalizacionTexto(modo) {
        if (!['mayusculas', 'minusculas', 'oracion'].includes(modo)) return;

        const elemento = elementoSeleccionado();
        const objeto = objetoPorIndice(indiceSeleccionado);
        if (!elemento || !(objeto instanceof Textbox)) return;

        const cantidadCaracteres = objeto.graphemeSplit(objeto.text || '').length;
        let inicio = Number(objeto.selectionStart);
        let fin = Number(objeto.selectionEnd);
        if (!Number.isInteger(inicio) || !Number.isInteger(fin) || fin <= inicio) {
            inicio = 0;
            fin = cantidadCaracteres;
        }
        if (fin <= inicio) return;

        const nuevoTexto = transformarSeleccionProtegiendoMarcadores(objeto.text || '', inicio, fin, modo);
        const textoAnterior = objeto.graphemeSplit(objeto.text || '').slice(inicio, fin).join('');
        if (nuevoTexto === textoAnterior) return;

        guardarHistorial();
        const estilosAnteriores = objeto.getSelectionStyles(inicio, fin, false);
        const caracteresNuevos = objeto.graphemeSplit(nuevoTexto);
        const estilosNuevos = caracteresNuevos.map((_, indice) => ({
            ...(estilosAnteriores[Math.min(indice, Math.max(0, estilosAnteriores.length - 1))] || {}),
        }));

        objeto.insertChars(nuevoTexto, estilosNuevos, inicio, fin);
        objeto.selectionStart = inicio;
        objeto.selectionEnd = inicio + caracteresNuevos.length;
        if (objeto.hiddenTextarea) {
            objeto.hiddenTextarea.value = objeto.text;
            objeto._updateTextarea?.();
        }
        objeto.dirty = true;
        sincronizarTexto(objeto);
        lienzo.requestRenderAll();
        renderizarPropiedades();
    }

    function abrirPanelLateral(nombre) {
        if (!nombre) return;
        controles.pestanasLaterales.forEach((pestana) => {
            const activa = pestana.dataset.plantillaSidebarTab === nombre;
            pestana.classList.toggle('is-active', activa);
            pestana.setAttribute('aria-selected', activa ? 'true' : 'false');
        });
        controles.panelesLaterales.forEach((panel) => {
            const activo = panel.dataset.plantillaSidebarPane === nombre;
            panel.classList.toggle('is-active', activo);
            panel.hidden = !activo;
        });
    }

    function seleccionarIndice(indice) {
        if (!elementos[indice]) return;
        seleccionarIndices([indice]);
        abrirPanelLateral('propiedades');
        renderizarCapas();
        renderizarPropiedades();
        lienzo.requestRenderAll();
    }

    function seleccionarIndices(indices) {
        const validos = [...new Set(indices)]
            .filter((indice) => elementos[indice]?.configuracion.visible);
        renderizandoLienzo = true;
        lienzo.discardActiveObject();
        renderizandoLienzo = false;
        establecerSeleccion(validos);
        restaurarSeleccionEnLienzo();
        lienzo.requestRenderAll();
    }

    function limpiarSeleccion() {
        renderizandoLienzo = true;
        lienzo.discardActiveObject();
        renderizandoLienzo = false;
        establecerSeleccion([]);
        renderizarCapas();
        renderizarPropiedades();
        lienzo.requestRenderAll();
    }

    function establecerSeleccion(indices) {
        indicesSeleccionadosEstado = [...new Set(indices)].filter((indice) => elementos[indice]);
        indiceSeleccionado = indicesSeleccionadosEstado[0] ?? null;
    }

    function indicesSeleccionados() {
        const objetosActivos = lienzo.getActiveObjects()
            .map((objeto) => objeto.plantillaIndice)
            .filter(Number.isInteger);
        return objetosActivos.length ? [...new Set(objetosActivos)] : [...indicesSeleccionadosEstado];
    }

    function moverCapaUnNivel(indice, direccion) {
        const destino = indice + direccion;
        if (!elementos[indice] || destino < 0 || destino >= elementos.length) return;
        guardarHistorial();
        [elementos[indice], elementos[destino]] = [elementos[destino], elementos[indice]];
        establecerSeleccion([destino]);
        renderizarTodo();
    }

    function alternarVisibilidad(indice) {
        if (!elementos[indice]) return;
        guardarHistorial();
        elementos[indice].configuracion.visible = !elementos[indice].configuracion.visible;
        renderizarTodo();
    }

    function renderizarCapas() {
        if (!controles.capas) return;
        if (!elementos.length) {
            controles.capas.innerHTML = '<div class="plantilla-editor-ayuda">Todavía no hay capas en el certificado.</div>';
            return;
        }

        const indicesActivos = new Set(indicesSeleccionados());
        const iconosPorTipo = {
            TEXTO: 'fa-font',
            CAMPO: 'fa-code',
            FIRMA: 'fa-signature',
            IMAGEN: 'fa-image',
            QR: 'fa-qrcode',
            TABLA: 'fa-table-cells',
            FORMA: 'fa-shapes',
            LINEA: 'fa-minus',
        };

        controles.capas.innerHTML = elementos
            .map((elemento, indice) => ({ elemento, indice }))
            .reverse()
            .map(({ elemento, indice }) => `
                <div class="plantilla-layer-item ${indicesActivos.has(indice) ? 'is-active' : ''} ${elemento.configuracion.visible ? '' : 'is-hidden'}"
                    data-capa-index="${indice}" role="button" tabindex="0">
                    <span class="plantilla-layer-thumbnail" title="${escaparAtributo(elemento.tipo_elemento)}">
                        <i class="fa-solid ${iconosPorTipo[elemento.tipo_elemento] || 'fa-layer-group'}"></i>
                    </span>
                    <div class="plantilla-layer-data">
                        <strong>${escaparHtml(nombreElemento(elemento))}</strong>
                        <small>${escaparHtml(elemento.codigo_elemento || elemento.tipo_elemento)}${elemento.configuracion.grupo ? ' · Agrupada' : ''}</small>
                    </div>
                    <div class="plantilla-layer-actions">
                        <button type="button" class="plantilla-layer-control" data-capa-subir="${indice}"
                            title="Subir una capa" ${indice === elementos.length - 1 ? 'disabled' : ''}>
                            <i class="fa-solid fa-chevron-up"></i>
                        </button>
                        <button type="button" class="plantilla-layer-control" data-capa-bajar="${indice}"
                            title="Bajar una capa" ${indice === 0 ? 'disabled' : ''}>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>
                        <button type="button" class="plantilla-layer-control" data-capa-visible="${indice}"
                            title="${elemento.configuracion.visible ? 'Ocultar capa' : 'Mostrar capa'}">
                            <i class="fa-solid ${elemento.configuracion.visible ? 'fa-eye' : 'fa-eye-slash'}"></i>
                        </button>
                        <button type="button" class="plantilla-layer-control" data-capa-bloquear="${indice}"
                            title="${elemento.configuracion.bloqueado ? 'Desbloquear capa' : 'Bloquear capa'}">
                            <i class="fa-solid ${elemento.configuracion.bloqueado ? 'fa-lock' : 'fa-lock-open'}"></i>
                        </button>
                    </div>
                </div>
            `).join('');
    }

    function elementoSeleccionado() {
        return Number.isInteger(indiceSeleccionado) ? elementos[indiceSeleccionado] : null;
    }

    function renderizarPropiedades() {
        if (!controles.propiedades || actualizandoDesdeLienzo) return;
        const cantidadSeleccionada = indicesSeleccionados().length;
        if (cantidadSeleccionada > 1) {
            controles.propiedades.innerHTML = `
                <div class="plantilla-editor-ayuda">
                    <strong>${cantidadSeleccionada} capas seleccionadas</strong><br>
                    Puede moverlas y transformarlas juntas. Use las opciones de selección para agruparlas o separarlas.
                </div>
            `;
            return;
        }
        const elemento = elementoSeleccionado();
        if (!elemento) {
            controles.propiedades.innerHTML = '<div class="plantilla-editor-ayuda">Seleccione una capa para editar sus propiedades.</div>';
            return;
        }

        const admiteTexto = ['TEXTO', 'CAMPO', 'FIRMA'].includes(elemento.tipo_elemento);
        const admiteImagen = ['IMAGEN', 'QR'].includes(elemento.tipo_elemento);
        controles.propiedades.innerHTML = `
            <div class="plantilla-prop-grid">
                <div class="plantilla-prop-section-title">Capa</div>
                <label class="plantilla-prop-full">
                    <span class="plantilla-prop-label">Nombre de la capa</span>
                    <input class="plantilla-prop-input" data-prop-config="nombre_capa" value="${escaparAtributo(elemento.configuracion.nombre_capa)}"
                        placeholder="${escaparAtributo(nombreElemento({ ...elemento, configuracion: { ...elemento.configuracion, nombre_capa: '' } }))}">
                </label>
                <div class="plantilla-prop-section-title">Transformación</div>
                ${campoNumero('Posición X', 'posicion_x', elemento.posicion_x)}
                ${campoNumero('Posición Y', 'posicion_y', elemento.posicion_y)}
                ${campoNumero('Ancho', 'ancho', elemento.ancho, 1)}
                ${campoNumero('Alto', 'alto', elemento.alto, 1)}
                ${campoNumero('Rotación', 'rotacion', elemento.configuracion.rotacion, -360, 'configuracion')}
                ${campoNumero('Opacidad %', 'opacidad', Math.round(elemento.configuracion.opacidad * 100), 5, 'porcentaje')}
                ${admiteTexto ? propiedadesTexto(elemento) : ''}
                ${elemento.tipo_elemento === 'FORMA' ? propiedadesForma(elemento) : ''}
                ${elemento.tipo_elemento === 'LINEA' ? propiedadesLinea(elemento) : ''}
                ${elemento.tipo_elemento === 'TABLA' ? propiedadesTabla(elemento) : ''}
                ${admiteImagen ? `<button type="button" class="plantilla-action-btn plantilla-prop-full" data-prop-imagen>Cambiar imagen</button>` : ''}
                <div class="plantilla-prop-section-title">Estado de la capa</div>
                <label class="plantilla-check-row">
                    <input type="checkbox" data-prop-config="visible" ${elemento.configuracion.visible ? 'checked' : ''}> Visible
                </label>
                <label class="plantilla-check-row">
                    <input type="checkbox" data-prop-config="bloqueado" ${elemento.configuracion.bloqueado ? 'checked' : ''}> Bloqueada
                </label>
                <div class="plantilla-prop-full plantilla-format-row">
                    <button type="button" class="plantilla-action-btn is-danger" data-prop-eliminar>Quitar capa</button>
                </div>
            </div>
        `;
    }

    function campoNumero(etiqueta, propiedad, valor, minimo = null, grupo = 'elemento') {
        const atributoMinimo = minimo === null ? '' : `min="${minimo}"`;
        return `<label><span class="plantilla-prop-label">${etiqueta}</span>
            <input type="number" class="plantilla-prop-input" ${atributoMinimo} step="any" data-prop-grupo="${grupo}" data-propiedad="${propiedad}" value="${valor}">
        </label>`;
    }

    function propiedadesTexto(elemento) {
        return `
            <div class="plantilla-prop-section-title">Texto</div>
            ${campoNumero('Tamaño de letra', 'tamano_letra', elemento.tamano_letra, 6)}
            ${campoNumero('Interlineado', 'interlineado', elemento.interlineado, 0.8)}
            <label><span class="plantilla-prop-label">Color</span>
                <input type="color" class="plantilla-prop-input" data-prop-grupo="elemento" data-propiedad="color_texto" value="${elemento.color_texto}">
            </label>
            <label><span class="plantilla-prop-label">Fuente</span>
                <select class="plantilla-prop-select" data-prop-grupo="elemento" data-propiedad="tipo_letra">
                    ${FUENTES_CERTIFICADO.map((fuente) => `
                        <option value="${fuente}" ${fuente === elemento.tipo_letra ? 'selected' : ''}>
                            ${fuente}
                        </option>
                    `).join('')}
                </select>
            </label>
            <label class="plantilla-prop-full"><span class="plantilla-prop-label">Alineación</span>
                <select class="plantilla-prop-select" data-prop-grupo="elemento" data-propiedad="alineacion">
                    ${[['IZQUIERDA', 'Izquierda'], ['CENTRO', 'Centro'], ['DERECHA', 'Derecha'], ['JUSTIFICADO', 'Justificado']].map(([valor, texto]) => `<option value="${valor}" ${valor === elemento.alineacion ? 'selected' : ''}>${texto}</option>`).join('')}
                </select>
            </label>
            <div class="plantilla-prop-full plantilla-format-row">
                <button type="button" class="plantilla-format-btn ${elemento.negrita ? 'is-active' : ''}" data-prop-formato="negrita" title="Negrita para el texto o las palabras seleccionadas">B</button>
                <button type="button" class="plantilla-format-btn ${elemento.cursiva ? 'is-active' : ''}" data-prop-formato="cursiva" title="Cursiva para el texto o las palabras seleccionadas">I</button>
                <button type="button" class="plantilla-format-btn ${elemento.subrayado ? 'is-active' : ''}" data-prop-formato="subrayado" title="Subrayado para el texto o las palabras seleccionadas">U</button>
                <button type="button" class="plantilla-format-btn" data-prop-capitalizacion="mayusculas" title="Convertir el texto o las palabras seleccionadas a MAYÚSCULAS" aria-label="Convertir a mayúsculas">ABC</button>
                <button type="button" class="plantilla-format-btn" data-prop-capitalizacion="minusculas" title="Convertir el texto o las palabras seleccionadas a minúsculas" aria-label="Convertir a minúsculas">abc</button>
                <button type="button" class="plantilla-format-btn" data-prop-capitalizacion="oracion" title="Convertir el texto o las palabras seleccionadas a tipo oración" aria-label="Convertir a tipo oración">Abc</button>
            </div>
            ${elemento.tipo_elemento === 'TEXTO' ? '<div class="plantilla-prop-full plantilla-editor-ayuda">Edite el texto en el lienzo y seleccione una o varias palabras. Use B, I, U, ABC, abc o Abc. Sin selección, el cambio se aplica a toda la capa. Los códigos {{variable}} se conservan intactos.</div>' : ''}
            <label class="plantilla-prop-full"><span class="plantilla-prop-label">Contenido</span>
                <textarea class="plantilla-prop-textarea" data-prop-grupo="elemento" data-propiedad="texto_fijo">${escaparHtml(elemento.texto_fijo)}</textarea>
            </label>`;
    }

    function propiedadesForma(elemento) {
        return `
            <label><span class="plantilla-prop-label">Relleno</span>
                <input type="color" class="plantilla-prop-input" data-prop-grupo="configuracion" data-propiedad="fondo_color" value="${elemento.configuracion.fondo_color === 'transparent' ? '#ffffff' : elemento.configuracion.fondo_color}">
            </label>
            <label><span class="plantilla-prop-label">Borde</span>
                <input type="color" class="plantilla-prop-input" data-prop-grupo="configuracion" data-propiedad="borde_color" value="${elemento.configuracion.borde_color}">
            </label>
            ${campoNumero('Grosor del borde', 'borde_ancho', elemento.configuracion.borde_ancho, 0, 'configuracion')}
            <label><span class="plantilla-prop-label">Forma</span>
                <select class="plantilla-prop-select" data-prop-grupo="configuracion" data-propiedad="forma">
                    <option value="RECTANGULO" ${elemento.configuracion.forma === 'RECTANGULO' ? 'selected' : ''}>Rectángulo</option>
                    <option value="ELIPSE" ${elemento.configuracion.forma === 'ELIPSE' ? 'selected' : ''}>Elipse</option>
                </select>
            </label>`;
    }

    function propiedadesLinea(elemento) {
        return `
            <div class="plantilla-prop-section-title">Línea horizontal</div>
            <label><span class="plantilla-prop-label">Color</span>
                <input type="color" class="plantilla-prop-input" data-prop-grupo="configuracion" data-propiedad="borde_color" value="${elemento.configuracion.borde_color}">
            </label>
            ${campoNumero('Grosor', 'borde_ancho', elemento.configuracion.borde_ancho, 1, 'configuracion')}
            <label class="plantilla-prop-full"><span class="plantilla-prop-label">Estilo</span>
                <select class="plantilla-prop-select" data-prop-grupo="configuracion" data-propiedad="borde_estilo">
                    <option value="SOLIDO" ${elemento.configuracion.borde_estilo === 'SOLIDO' ? 'selected' : ''}>Sólida</option>
                    <option value="PUNTEADO" ${elemento.configuracion.borde_estilo === 'PUNTEADO' ? 'selected' : ''}>Punteada</option>
                    <option value="DISCONTINUO" ${elemento.configuracion.borde_estilo === 'DISCONTINUO' ? 'selected' : ''}>Discontinua</option>
                </select>
            </label>`;
    }

    function propiedadesTabla(elemento) {
        const tabla = elemento.configuracion.tabla;
        const tablaDatos = esTablaDatos(elemento);
        const entradas = tablaDatos ? entradasTablaDatos(elemento) : [];
        const camposSeleccionables = campos
            .filter((campo) => !campo.codigo.endsWith('.tabla') && campo.codigo !== 'qr.verificacion');
        const gruposCampos = camposSeleccionables.reduce((grupos, campo) => {
            if (!grupos.has(campo.grupo)) grupos.set(campo.grupo, []);
            grupos.get(campo.grupo).push(campo);
            return grupos;
        }, new Map());
        const opcionesCampo = (codigoSeleccionado) => `
            <option value="">Seleccione un dato...</option>
            ${[...gruposCampos.entries()].map(([grupo, camposGrupo]) => `
                <optgroup label="${escaparAtributo(grupo)}">
                    ${camposGrupo.map((campo) => `<option value="${escaparAtributo(campo.codigo)}" ${campo.codigo === codigoSeleccionado ? 'selected' : ''}>${escaparHtml(campo.nombre)}</option>`).join('')}
                </optgroup>`).join('')}`;
        const controlesFormatoCelda = (titulo, tipoCelda, indice, estilo) => `
            <details class="plantilla-tabla-formato">
                <summary>${titulo}</summary>
                <div class="plantilla-tabla-formato-grid">
                    <label><span class="plantilla-prop-label">Fuente</span>
                        <select class="plantilla-prop-select" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="tipo_letra" data-tabla-indice="${indice}">
                            ${FUENTES_CERTIFICADO.map((fuente) => `<option value="${fuente}" ${fuente === estilo.tipo_letra ? 'selected' : ''}>${fuente}</option>`).join('')}
                        </select>
                    </label>
                    <label><span class="plantilla-prop-label">Tamaño</span>
                        <input type="number" min="6" max="160" step="1" class="plantilla-prop-input" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="tamano_letra" data-tabla-indice="${indice}" value="${estilo.tamano_letra}">
                    </label>
                    <label><span class="plantilla-prop-label">Color</span>
                        <input type="color" class="plantilla-prop-input" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="color_texto" data-tabla-indice="${indice}" value="${estilo.color_texto}">
                    </label>
                    <label><span class="plantilla-prop-label">Alineación</span>
                        <select class="plantilla-prop-select" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="alineacion" data-tabla-indice="${indice}">
                            ${[['IZQUIERDA', 'Izquierda'], ['CENTRO', 'Centro'], ['DERECHA', 'Derecha'], ['JUSTIFICADO', 'Justificado']]
                                .map(([valor, texto]) => `<option value="${valor}" ${valor === estilo.alineacion ? 'selected' : ''}>${texto}</option>`).join('')}
                        </select>
                    </label>
                    <label class="plantilla-check-row"><input type="checkbox" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="negrita" data-tabla-indice="${indice}" ${estilo.negrita ? 'checked' : ''}> Negrita</label>
                    <label class="plantilla-check-row"><input type="checkbox" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="cursiva" data-tabla-indice="${indice}" ${estilo.cursiva ? 'checked' : ''}> Cursiva</label>
                    <label class="plantilla-check-row"><input type="checkbox" data-tabla-estilo="${tipoCelda}" data-tabla-estilo-prop="subrayado" data-tabla-indice="${indice}" ${estilo.subrayado ? 'checked' : ''}> Subrayado</label>
                </div>
            </details>`;

        const editorContenido = tablaDatos
            ? `<div class="plantilla-prop-full plantilla-editor-ayuda">Cada fila mantiene alineados el nombre del dato y su valor. Elija una variable; al emitir se reemplazará automáticamente.</div>
                <label class="plantilla-prop-full"><span class="plantilla-prop-label">Distribución</span>
                    <select class="plantilla-prop-select" data-tabla-pares>
                        <option value="1" ${tabla.pares_por_fila === 1 ? 'selected' : ''}>Un campo y valor por fila</option>
                        <option value="2" ${tabla.pares_por_fila === 2 ? 'selected' : ''}>Dos campos y valores por fila</option>
                    </select>
                </label>
                <div class="plantilla-prop-full plantilla-tabla-datos-lista">
                    ${entradas.map((entrada, indice) => `
                        <div class="plantilla-tabla-dato" data-tabla-entrada="${indice}">
                            <div class="plantilla-tabla-dato-encabezado">
                                <strong>Dato ${indice + 1}</strong>
                                <div>
                                    <button type="button" class="plantilla-tabla-dato-btn" data-tabla-subir="${indice}" title="Subir">↑</button>
                                    <button type="button" class="plantilla-tabla-dato-btn" data-tabla-bajar="${indice}" title="Bajar">↓</button>
                                    <button type="button" class="plantilla-tabla-dato-btn is-danger" data-tabla-quitar="${indice}" title="Quitar">×</button>
                                </div>
                            </div>
                            <label><span class="plantilla-prop-label">Nombre que se imprimirá</span>
                                <input class="plantilla-prop-input" data-tabla-etiqueta="${indice}" value="${escaparAtributo(entrada.etiqueta)}" placeholder="Ej.: Razón social">
                            </label>
                            <label><span class="plantilla-prop-label">Valor dinámico</span>
                                <select class="plantilla-prop-select" data-tabla-codigo="${indice}">${opcionesCampo(entrada.codigo)}</select>
                            </label>
                            ${controlesFormatoCelda('Formato del campo', 'etiqueta', indice, estiloCeldaTabla(elemento, Math.floor(indice / tabla.pares_por_fila), (indice % tabla.pares_por_fila) * 2))}
                            ${controlesFormatoCelda('Formato del valor', 'valor', indice, estiloCeldaTabla(elemento, Math.floor(indice / tabla.pares_por_fila), (indice % tabla.pares_por_fila) * 2 + 1))}
                        </div>`).join('')}
                </div>
                <button type="button" class="plantilla-action-btn plantilla-prop-full" data-tabla-agregar>+ Agregar dato</button>`
            : `<div class="plantilla-prop-full plantilla-prop-section-title">Columnas de la tabla</div>
                ${elemento.columnas.map((columna, indice) => `
                    <label class="plantilla-prop-full"><span class="plantilla-prop-label">Columna ${indice + 1}</span>
                        <input class="plantilla-prop-input" data-columna-titulo="${indice}" value="${escaparAtributo(columna.titulo_columna || '')}">
                    </label>`).join('')}`;

        return `<div class="plantilla-prop-full plantilla-prop-section-title">Contenido de la tabla</div>
            ${editorContenido}
            <div class="plantilla-prop-full plantilla-prop-section-title">Columnas y apariencia</div>
            ${elemento.columnas.map((columna, indice) => `
                <label><span class="plantilla-prop-label">Ancho columna ${indice + 1} (%)</span>
                    <input type="number" min="5" max="95" step="1" class="plantilla-prop-input" data-tabla-ancho="${indice}" value="${numero(columna.ancho, 25)}">
                </label>`).join('')}
            ${campoNumero('Tamaño de letra', 'tamano_letra', elemento.tamano_letra, 8)}
            <label><span class="plantilla-prop-label">Bordes</span>
                <select class="plantilla-prop-select" data-tabla-config="bordes">
                    ${[['NINGUNO', 'Sin bordes'], ['TODOS', 'Todos'], ['EXTERIOR', 'Solo exterior'], ['HORIZONTAL', 'Horizontales'], ['VERTICAL', 'Verticales'], ['ENCABEZADO', 'Solo encabezado']]
                        .map(([valor, texto]) => `<option value="${valor}" ${tabla.bordes === valor ? 'selected' : ''}>${texto}</option>`).join('')}
                </select>
            </label>
            ${campoNumero('Separación horizontal', 'padding_x', tabla.padding_x, 0, 'tabla')}
            ${campoNumero('Separación vertical', 'padding_y', tabla.padding_y, 0, 'tabla')}
            ${campoNumero('Tamaño mínimo del texto', 'tamano_minimo', tabla.tamano_minimo, 5, 'tabla')}
            <label class="plantilla-prop-full"><span class="plantilla-prop-label">Alineación vertical</span>
                <select class="plantilla-prop-select" data-tabla-config="alineacion_vertical">
                    ${[['SUPERIOR', 'Superior'], ['MEDIO', 'Centro'], ['INFERIOR', 'Inferior']]
                        .map(([valor, texto]) => `<option value="${valor}" ${tabla.alineacion_vertical === valor ? 'selected' : ''}>${texto}</option>`).join('')}
                </select>
            </label>
            <label class="plantilla-check-row">
                <input type="checkbox" data-tabla-config="mostrar_encabezado" ${tabla.mostrar_encabezado ? 'checked' : ''}> Mostrar encabezado
            </label>
            ${tablaDatos
                ? '<div class="plantilla-prop-full plantilla-editor-ayuda">Las filas de Campo/Valor se amplían automáticamente cuando un nombre o valor necesita más líneas. El texto conserva su tamaño y nunca invade otra celda.</div>'
                : `<label class="plantilla-check-row plantilla-prop-full">
                    <input type="checkbox" data-tabla-config="ajustar_texto" ${tabla.ajustar_texto ? 'checked' : ''}> Ajustar texto largo dentro de cada celda
                </label>
                <div class="plantilla-prop-full plantilla-editor-ayuda">El texto baja de línea y reduce su tamaño solo hasta el mínimo indicado. Si aun así no cabe, se muestra con puntos suspensivos para que nunca invada otra celda.</div>`}
            ${tablaDatos ? `<label class="plantilla-check-row">
                <input type="checkbox" data-tabla-config="etiquetas_negrita" ${tabla.etiquetas_negrita ? 'checked' : ''}> Nombres en negrita
            </label>` : ''}`;
    }

    function actualizarPropiedad(evento) {
        const elemento = elementoSeleccionado();
        const objetivo = evento.target;
        if (!elemento || !(objetivo instanceof HTMLInputElement || objetivo instanceof HTMLSelectElement || objetivo instanceof HTMLTextAreaElement)) return;

        if (objetivo.dataset.tablaEstilo !== undefined) {
            const indice = Number(objetivo.dataset.tablaIndice);
            const propiedad = objetivo.dataset.tablaEstiloProp;
            const entradas = entradasTablaDatos(elemento);
            const entrada = entradas[indice];
            if (!entrada || !['etiqueta', 'valor'].includes(objetivo.dataset.tablaEstilo || '') || !propiedad) return;

            const claveEstilo = objetivo.dataset.tablaEstilo === 'etiqueta' ? 'estiloEtiqueta' : 'estiloValor';
            const estilo = { ...(entrada[claveEstilo] || {}) };
            if (['negrita', 'cursiva', 'subrayado'].includes(propiedad)) {
                estilo[propiedad] = objetivo.checked;
            } else if (propiedad === 'tamano_letra') {
                estilo[propiedad] = limitar(numero(objetivo.value, elemento.tamano_letra), 6, 160);
            } else if (propiedad === 'tipo_letra' && FUENTES_CERTIFICADO.includes(objetivo.value)) {
                estilo[propiedad] = objetivo.value;
            } else if (propiedad === 'color_texto') {
                estilo[propiedad] = normalizarColorTexto(objetivo.value);
            } else if (propiedad === 'alineacion') {
                estilo[propiedad] = normalizarAlineacion(objetivo.value);
            } else {
                return;
            }
            entrada[claveEstilo] = estilo;
            reagruparTablaDatos(elemento, entradas, elemento.configuracion.tabla.pares_por_fila, false);
            actualizarInput();
            if (evento.type === 'change') renderizarTodo();
            return;
        }

        if (objetivo.dataset.tablaEtiqueta !== undefined || objetivo.dataset.tablaCodigo !== undefined) {
            const indice = Number(objetivo.dataset.tablaEtiqueta ?? objetivo.dataset.tablaCodigo);
            const entradas = entradasTablaDatos(elemento);
            entradas[indice] ??= { etiqueta: '', codigo: '' };
            if (objetivo.dataset.tablaEtiqueta !== undefined) entradas[indice].etiqueta = objetivo.value;
            else entradas[indice].codigo = objetivo.value;
            reagruparTablaDatos(elemento, entradas, elemento.configuracion.tabla.pares_por_fila, false);
            actualizarInput();
            if (evento.type === 'change') renderizarTodo();
            return;
        }

        if (objetivo.dataset.tablaPares !== undefined) {
            reagruparTablaDatos(elemento, entradasTablaDatos(elemento), Number(objetivo.value));
            renderizarTodo();
            return;
        }

        if (objetivo.dataset.tablaAncho !== undefined) {
            const columna = elemento.columnas[Number(objetivo.dataset.tablaAncho)];
            if (columna) columna.ancho = limitar(numero(objetivo.value, 25), 5, 95);
            actualizarInput();
            if (evento.type === 'change') renderizarTodo();
            return;
        }

        if (objetivo.dataset.tablaConfig !== undefined) {
            const propiedad = objetivo.dataset.tablaConfig;
            elemento.configuracion.tabla[propiedad] = objetivo.type === 'checkbox'
                ? objetivo.checked
                : objetivo.value;
            actualizarInput();
            if (evento.type === 'change') renderizarTodo();
            return;
        }

        if (objetivo.dataset.columnaTitulo !== undefined) {
            const columna = elemento.columnas[Number(objetivo.dataset.columnaTitulo)];
            if (columna) columna.titulo_columna = objetivo.value;
            actualizarInput();
            if (evento.type === 'change') renderizarTodo();
            return;
        }

        if (objetivo.dataset.propConfig) {
            const propiedad = objetivo.dataset.propConfig;
            elemento.configuracion[propiedad] = objetivo.type === 'checkbox' ? objetivo.checked : objetivo.value;
            if (evento.type === 'change') renderizarTodo();
            else actualizarInput();
            return;
        }

        const propiedad = objetivo.dataset.propiedad;
        const grupo = objetivo.dataset.propGrupo;
        if (!propiedad || !grupo) return;
        let valor = objetivo.type === 'number' ? numero(objetivo.value, 0) : objetivo.value;
        if (grupo === 'porcentaje') {
            elemento.configuracion[propiedad] = Math.max(0.05, Math.min(1, valor / 100));
        } else if (grupo === 'configuracion') {
            elemento.configuracion[propiedad] = valor;
        } else if (grupo === 'tabla') {
            elemento.configuracion.tabla[propiedad] = valor;
        } else {
            elemento[propiedad] = propiedad === 'alineacion' ? normalizarAlineacion(valor) : valor;
        }

        actualizarInput();
        if (evento.type === 'change') {
            renderizarTodo();
        } else if (propiedad === 'texto_fijo') {
            // Actualiza el objeto visible sin reconstruir el panel ni quitar el foco del textarea.
            const objeto = objetoPorIndice(indiceSeleccionado);
            if (objeto instanceof Textbox) {
                objeto.set({ text: String(valor) });
                objeto.initDimensions();
                objeto.setCoords();
                lienzo.requestRenderAll();
            }
        }
    }

    function escaparHtml(valor = '') {
        return String(valor)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escaparAtributo(valor = '') {
        return escaparHtml(valor).replace(/\n/g, '&#10;');
    }

    function configurarFondo() {
        controles.seleccionarFondo?.addEventListener('click', () => controles.inputFondo?.click());
        controles.verFondo?.addEventListener('click', () => {
            if (urlFondo) window.open(urlFondo, '_blank', 'noopener');
        });
        controles.quitarFondo?.addEventListener('click', () => {
            urlFondo = '';
            fondoFabric = null;
            urlFondoFabric = '';
            cicloCargaFondo += 1;
            if (controles.inputFondo) controles.inputFondo.value = '';
            if (controles.quitarFondoInput) controles.quitarFondoInput.value = '1';
            if (controles.nombreFondo) controles.nombreFondo.textContent = 'Sin archivo seleccionado';
            if (controles.verFondo) controles.verFondo.disabled = true;
            if (controles.quitarFondo) controles.quitarFondo.disabled = true;
            aplicarFondoVisual();
        });
        controles.inputFondo?.addEventListener('change', () => {
            const archivo = controles.inputFondo.files?.[0];
            if (!archivo) return;
            if (controles.quitarFondoInput) controles.quitarFondoInput.value = '0';
            mostrarFondo(archivo.name, URL.createObjectURL(archivo), archivo.type.startsWith('image/'));
        });
        ['change', 'input'].forEach((nombreEvento) => {
            controles.selectorAjusteFondo?.addEventListener(nombreEvento, aplicarFondoVisual);
            controles.selectorFondoTrabajo?.addEventListener(nombreEvento, aplicarFondoVisual);
        });
    }

    function mostrarFondo(nombre, url, esImagen) {
        urlFondo = url;
        if (controles.nombreFondo) controles.nombreFondo.textContent = nombre;
        if (controles.verFondo) controles.verFondo.disabled = false;
        if (controles.quitarFondo) controles.quitarFondo.disabled = false;
        if (!esImagen) {
            controles.escenario.dataset.fondoNoVisual = '1';
        } else {
            delete controles.escenario.dataset.fondoNoVisual;
        }
        aplicarFondoVisual();
    }

    async function aplicarFondoVisual() {
        const contenedor = lienzo.wrapperEl;
        const mostrarImagen = urlFondo
            && controles.escenario?.dataset.fondoNoVisual !== '1'
            && controles.selectorFondoTrabajo?.value !== 'BLANCO';
        const ajuste = controles.selectorAjusteFondo?.value || 'ESTIRAR';

        // El fondo pertenece al lienzo: Fabric lo escala sin afectar las capas editables.
        contenedor.style.backgroundColor = '#ffffff';
        contenedor.style.backgroundImage = 'none';
        lienzo.backgroundColor = '#ffffff';

        if (!mostrarImagen) {
            lienzo.backgroundImage = undefined;
            lienzo.requestRenderAll();
        } else {
            const cicloActual = ++cicloCargaFondo;
            const urlSolicitada = urlFondo;
            if (!fondoFabric || urlFondoFabric !== urlFondo) {
                try {
                    const fondoCargado = await FabricImage.fromURL(urlFondo, { crossOrigin: 'anonymous' });
                    if (cicloActual !== cicloCargaFondo || urlSolicitada !== urlFondo) return;
                    fondoCargado.set({ objectCaching: false });
                    fondoFabric = fondoCargado;
                    urlFondoFabric = urlSolicitada;
                } catch (error) {
                    lienzo.backgroundImage = undefined;
                    lienzo.requestRenderAll();
                    return;
                }
            }

            ajustarFondoAlLienzo(fondoFabric, ajuste);
            lienzo.backgroundImage = fondoFabric;
            lienzo.requestRenderAll();
        }

        controles.escenario?.classList.toggle('is-grid', cuadriculaVisible);
        controles.escenario?.classList.toggle('is-grid-points', tipoCuadricula === 'PUNTOS');
    }

    function ajustarFondoAlLienzo(imagen, ajuste) {
        if (!imagen) return;
        const medidas = medidasPapel();
        const anchoImagen = Math.max(1, imagen.width || medidas.ancho);
        const altoImagen = Math.max(1, imagen.height || medidas.alto);
        let escalaX = medidas.ancho / anchoImagen;
        let escalaY = medidas.alto / altoImagen;

        if (ajuste === 'CONTENER' || ajuste === 'CUBRIR') {
            const escalaUniforme = ajuste === 'CONTENER'
                ? Math.min(escalaX, escalaY)
                : Math.max(escalaX, escalaY);
            escalaX = escalaUniforme;
            escalaY = escalaUniforme;
        }

        imagen.set({
            left: (medidas.ancho - anchoImagen * escalaX) / 2,
            top: (medidas.alto - altoImagen * escalaY) / 2,
            originX: 'left',
            originY: 'top',
            scaleX: escalaX,
            scaleY: escalaY,
            selectable: false,
            evented: false,
        });
        imagen.setCoords();
    }

    function configurarFormulario() {
        controles.formulario?.addEventListener('submit', actualizarInput);
        controles.formulario?.addEventListener('keydown', (evento) => {
            if (evento.key !== 'Enter') return;
            if (evento.target instanceof HTMLTextAreaElement || evento.target?.isContentEditable) return;

            // Evita el submit implícito desde inputs y selects. La plantilla solo se guarda con el botón.
            evento.preventDefault();
        });
        ['change', 'input'].forEach((nombreEvento) => {
            controles.selectorTamano?.addEventListener(nombreEvento, actualizarPapel);
            controles.selectorOrientacion?.addEventListener(nombreEvento, actualizarPapel);
        });
        controles.selectorTipo?.addEventListener('change', actualizarTipoSeleccionado);
        window.addEventListener('resize', () => {
            if (zoomAjustadoAutomaticamente) ajustarZoomAlArea();
        });
        document.addEventListener('keydown', (evento) => {
            if (evento.key === 'Escape') {
                if (lienzo.getActiveObjects().length) {
                    limpiarSeleccion();
                    return;
                }
                if (controles.disenador?.classList.contains('is-maximized')) {
                    alternarModoMaximo();
                    return;
                }
            }
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(evento.target.tagName)) return;
            if (evento.code === 'Space') {
                evento.preventDefault();
                teclaEspacioActiva = true;
                if (lienzo.upperCanvasEl) lienzo.upperCanvasEl.style.cursor = 'grab';
                return;
            }
            if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'z' && !evento.shiftKey) {
                evento.preventDefault();
                deshacer();
            }
            if ((evento.ctrlKey || evento.metaKey) && (evento.key.toLowerCase() === 'y' || (evento.shiftKey && evento.key.toLowerCase() === 'z'))) {
                evento.preventDefault();
                rehacer();
            }
            if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'a') {
                evento.preventDefault();
                seleccionarTodo();
            }
            if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'c' && elementoSeleccionado()) {
                elementoCopiado = indicesSeleccionados().map((indice) => JSON.parse(JSON.stringify(elementos[indice])));
            }
            if ((evento.ctrlKey || evento.metaKey) && evento.key.toLowerCase() === 'v' && elementoCopiado?.length) {
                evento.preventDefault();
                guardarHistorial();
                const grupoNuevo = elementoCopiado.length > 1 ? `grupo_${Date.now()}` : '';
                const nuevosIndices = [];
                elementoCopiado.forEach((copiado) => {
                    const copia = normalizarElemento(copiado);
                    copia.posicion_x += 18;
                    copia.posicion_y += 18;
                    copia.configuracion.grupo = grupoNuevo;
                    elementos.push(copia);
                    nuevosIndices.push(elementos.length - 1);
                });
                establecerSeleccion(nuevosIndices);
                renderizarTodo();
            }
            if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(evento.key) && indicesSeleccionados().length) {
                evento.preventDefault();
                moverSeleccionConTeclado(evento.key, evento.shiftKey ? 10 : 1);
            }
            if (['Delete', 'Backspace'].includes(evento.key)) {
                evento.preventDefault();
                eliminarSeleccion();
            }
        });
        document.addEventListener('keyup', (evento) => {
            if (evento.code !== 'Space') return;
            teclaEspacioActiva = false;
            if (lienzo.upperCanvasEl) lienzo.upperCanvasEl.style.cursor = '';
        });
    }

    function moverSeleccionConTeclado(tecla, distancia) {
        guardarHistorial();
        const desplazamientos = {
            ArrowLeft: [-distancia, 0],
            ArrowRight: [distancia, 0],
            ArrowUp: [0, -distancia],
            ArrowDown: [0, distancia],
        };
        const [x, y] = desplazamientos[tecla];
        const medidas = medidasPapel();
        indicesSeleccionados().forEach((indice) => {
            const elemento = elementos[indice];
            elemento.posicion_x = limitar(elemento.posicion_x + x, 0, Math.max(0, medidas.ancho - elemento.ancho));
            elemento.posicion_y = limitar(elemento.posicion_y + y, 0, Math.max(0, medidas.alto - elemento.alto));
        });
        renderizarTodo();
    }

    function medidasPapel() {
        const oficio = controles.selectorTamano?.value === 'OFICIO';
        const horizontal = controles.selectorOrientacion?.value === 'HORIZONTAL';
        // Carta y oficio se trabajan a 96 px por pulgada, igual que la salida PDF.
        const medidas = oficio ? { ancho: 816, alto: 1344 } : { ancho: 816, alto: 1056 };
        return horizontal ? { ancho: medidas.alto, alto: medidas.ancho } : medidas;
    }

    async function actualizarPapel() {
        const medidas = medidasPapel();
        ocultarGuiasAlineacion();
        elementos.forEach((elemento) => ajustarElementoDentroDelLienzo(elemento, medidas));
        lienzo.setDimensions({ width: medidas.ancho, height: medidas.alto });
        if (zoomAjustadoAutomaticamente) ajustarZoomAlArea();
        else aplicarZoom();
        await aplicarFondoVisual();
        lienzo.requestRenderAll();
    }

    function actualizarTipoSeleccionado() {
        if (!controles.resumenTipo) return;
        const tipo = tiposCertificados.find((item) => String(item.id) === String(controles.selectorTipo?.value));
        controles.resumenTipo.textContent = tipo?.nombre || '';
    }

    function actualizarInput() {
        elementos.forEach((elemento, indice) => {
            elemento.z_index = indice + 3;
            const objeto = objetoPorIndice(indice);
            if (objeto) {
                objeto.plantillaIndice = indice;
                objeto.plantillaTipoElemento = elemento.tipo_elemento;
                objeto.plantillaCodigoElemento = elemento.codigo_elemento;
                objeto.plantillaTextoPlantilla = elemento.texto_fijo;
            }
        });
        if (controles.inputElementos) controles.inputElementos.value = JSON.stringify(elementos);
        if (controles.inputDisenoFabric) {
            const jsonLienzo = lienzo.toJSON(PROPIEDADES_FABRIC_PLANTILLA);
            controles.inputDisenoFabric.value = JSON.stringify({
                version: jsonLienzo.version,
                width: lienzo.width,
                height: lienzo.height,
                objects: jsonLienzo.objects || [],
            });
        }
        if (controles.contadorCapas) controles.contadorCapas.textContent = String(elementos.length);
    }

    return { iniciar };
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarEditorPlantilla);
} else {
    iniciarEditorPlantilla();
}
