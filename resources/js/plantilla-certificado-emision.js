import {
    Ellipse,
    FabricImage,
    Group,
    Line,
    Rect,
    StaticCanvas,
    Textbox,
    config,
} from 'fabric';

const SELECTOR = '[data-certificado-fabric-lienzo]';
const DPI_BASE_NAVEGADOR = 96;
// El respaldo PNG se guarda a 300 DPI. La vista y la impresión usan SVG,
// por lo que texto y líneas conservan nitidez sin generar imágenes excesivas.
const DPI_DOCUMENTO = 300;
const MAX_PIXELES_DOCUMENTO = 18_000_000;
const FUENTES = [
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

function numero(valor, predeterminado = 0) {
    const convertido = Number(valor);
    return Number.isFinite(convertido) ? convertido : predeterminado;
}

// Exporta el respaldo del certificado a 300 DPI sin superar un tamaño que pueda
// consumir demasiada memoria cuando la plantilla use medidas personalizadas.
function multiplicadorDocumento(ancho, alto) {
    const pixelesBase = Math.max(1, ancho * alto);
    const multiplicadorObjetivo = DPI_DOCUMENTO / DPI_BASE_NAVEGADOR;
    const multiplicadorSeguro = Math.sqrt(MAX_PIXELES_DOCUMENTO / pixelesBase);

    return Math.max(1, Math.min(multiplicadorObjetivo, multiplicadorSeguro));
}

function alineacion(valor) {
    return ({ IZQUIERDA: 'left', CENTRO: 'center', DERECHA: 'right', JUSTIFICADO: 'justify' })[String(valor || '').toUpperCase()] || 'left';
}

function patronTrazo(estilo) {
    if (estilo === 'PUNTEADO') return [2, 5];
    if (estilo === 'DISCONTINUO') return [12, 7];
    return null;
}

async function cargarFuentes() {
    if (!document.fonts?.load) return;
    await Promise.allSettled(FUENTES.map((fuente) => document.fonts.load(`16px "${fuente}"`)));
}

function estilosTexto(configuracion = {}) {
    const estilos = configuracion.estilos_texto;
    return estilos && typeof estilos === 'object' && !Array.isArray(estilos) ? estilos : {};
}

function estiloCaracter(estilos, linea, caracter) {
    const estilo = estilos?.[linea]?.[caracter];
    return estilo && typeof estilo === 'object' && !Array.isArray(estilo) ? { ...estilo } : {};
}

function caracteresConEstilo(texto, estilos = {}) {
    let linea = 0;
    let caracter = 0;

    return Array.from(String(texto || '').replace(/\r\n?/g, '\n')).map((valor) => {
        const token = {
            valor,
            estilo: valor === '\n' ? {} : estiloCaracter(estilos, linea, caracter),
        };

        if (valor === '\n') {
            linea += 1;
            caracter = 0;
        } else {
            caracter += 1;
        }

        return token;
    });
}

function estiloRepresentativo(tokens = []) {
    const frecuencias = new Map();

    tokens.forEach(({ estilo }) => {
        if (!estilo || Object.keys(estilo).length === 0) return;
        const clave = JSON.stringify(estilo);
        const registro = frecuencias.get(clave) || { cantidad: 0, estilo };
        registro.cantidad += 1;
        frecuencias.set(clave, registro);
    });

    return [...frecuencias.values()]
        .sort((primero, segundo) => segundo.cantidad - primero.cantidad)[0]?.estilo || {};
}

function estilosDesdeCaracteres(tokens = []) {
    const estilos = {};
    let linea = 0;
    let caracter = 0;

    tokens.forEach(({ valor, estilo }) => {
        if (valor === '\n') {
            linea += 1;
            caracter = 0;
            return;
        }

        if (estilo && Object.keys(estilo).length > 0) {
            estilos[linea] ||= {};
            estilos[linea][caracter] = { ...estilo };
        }
        caracter += 1;
    });

    return estilos;
}

/**
 * Sustituye variables y desplaza los estilos por carácter junto con el texto.
 * Fabric guarda esos estilos por índice; sin este ajuste una variable de distinta
 * longitud trasladaba la negrita, cursiva o subrayado a palabras posteriores.
 */
function resolverMarcadoresConEstilos(texto, estilos = {}, valores = {}) {
    const plantilla = String(texto || '').replace(/\r\n?/g, '\n');
    const originales = caracteresConEstilo(plantilla, estilos);
    const resultado = [];
    const patron = /\{\{\s*([^}]+)\s*\}\}/g;
    let cursorCaracter = 0;
    let coincidencia;

    while ((coincidencia = patron.exec(plantilla)) !== null) {
        const inicio = Array.from(plantilla.slice(0, coincidencia.index)).length;
        const longitud = Array.from(coincidencia[0]).length;
        const clave = String(coincidencia[1] || '').trim();

        resultado.push(...originales.slice(cursorCaracter, inicio));

        if (Object.prototype.hasOwnProperty.call(valores, clave)) {
            const estilo = estiloRepresentativo(originales.slice(inicio, inicio + longitud));
            Array.from(String(valores[clave] ?? '').replace(/\r\n?/g, '\n')).forEach((valor) => {
                resultado.push({ valor, estilo: valor === '\n' ? {} : { ...estilo } });
            });
        } else {
            resultado.push(...originales.slice(inicio, inicio + longitud));
        }

        cursorCaracter = inicio + longitud;
    }

    resultado.push(...originales.slice(cursorCaracter));

    return {
        texto: resultado.map(({ valor }) => valor).join(''),
        estilos: estilosDesdeCaracteres(resultado),
    };
}

function aplicarDatosDinamicos(objeto, valores = {}) {
    if (!(objeto instanceof Textbox)) return;

    const tipo = String(objeto.plantillaTipoElemento || '').toUpperCase();
    const codigo = objeto.plantillaCodigoElemento;
    const textoGuardado = objeto.plantillaTextoPlantilla;
    const plantilla = textoGuardado === null || textoGuardado === undefined || textoGuardado === ''
        ? (objeto.text ?? '')
        : textoGuardado;
    const tieneMarcador = /\{\{\s*[^}]+\s*\}\}/.test(String(plantilla));

    if (!['TEXTO', 'CAMPO', 'FIRMA'].includes(tipo) && !tieneMarcador) return;

    let resuelto = resolverMarcadoresConEstilos(plantilla, objeto.styles || {}, valores);
    if (tipo === 'CAMPO' && codigo && Object.prototype.hasOwnProperty.call(valores, codigo)) {
        const valorCampo = String(valores[codigo] ?? '');
        if (resuelto.texto !== valorCampo) {
            const estilo = estiloRepresentativo(caracteresConEstilo(plantilla, objeto.styles || {}));
            const tokens = Array.from(valorCampo).map((valor) => ({ valor, estilo: { ...estilo } }));
            resuelto = { texto: valorCampo, estilos: estilosDesdeCaracteres(tokens) };
        }
    }

    const geometria = {
        left: objeto.left,
        top: objeto.top,
        width: objeto.width,
        height: objeto.height,
        scaleX: objeto.scaleX,
        scaleY: objeto.scaleY,
        angle: objeto.angle,
    };
    objeto.set({ text: resuelto.texto, styles: resuelto.estilos });
    objeto.initDimensions();
    objeto.set(geometria);
    objeto.setCoords();
}

function crearMarcador(texto, ancho, alto) {
    return new Group([
        new Rect({
            left: 0,
            top: 0,
            originX: 'left',
            originY: 'top',
            width: ancho,
            height: alto,
            fill: '#f8fafc',
            stroke: '#94a3b8',
            strokeDashArray: [5, 4],
            rx: 6,
            ry: 6,
        }),
        new Textbox(texto, {
            left: 8,
            top: Math.max(6, alto / 2 - 8),
            width: Math.max(20, ancho - 16),
            fontSize: 12,
            fontFamily: 'Arial',
            fontWeight: 'bold',
            fill: '#475569',
            textAlign: 'center',
        }),
    ], { width: ancho, height: alto });
}

function colorTabla(valor, predeterminado) {
    return /^#[0-9a-f]{6}$/i.test(String(valor || '')) ? String(valor) : predeterminado;
}

function estiloCeldaTabla(elemento, filaIndice, columnaIndice, encabezado = false) {
    const tabla = elemento.configuracion?.tabla || {};
    const esEtiqueta = tabla.tipo === 'CAMPO_VALOR' && columnaIndice % 2 === 0;
    const estiloGuardado = elemento.estilos_celdas?.[filaIndice]?.[columnaIndice] || {};
    const tipoLetra = FUENTES.includes(estiloGuardado.tipo_letra)
        ? estiloGuardado.tipo_letra
        : (elemento.tipo_letra || 'Arial');
    const alineacionCelda = String(estiloGuardado.alineacion || elemento.alineacion || 'IZQUIERDA').toUpperCase();

    return {
        tipo_letra: tipoLetra,
        tamano_letra: Math.max(6, Math.min(160, numero(estiloGuardado.tamano_letra, elemento.tamano_letra || 11))),
        negrita: encabezado
            ? true
            : (typeof estiloGuardado.negrita === 'boolean'
                ? estiloGuardado.negrita
                : Boolean(elemento.negrita || (esEtiqueta && tabla.etiquetas_negrita !== false))),
        cursiva: typeof estiloGuardado.cursiva === 'boolean' ? estiloGuardado.cursiva : Boolean(elemento.cursiva),
        subrayado: typeof estiloGuardado.subrayado === 'boolean' ? estiloGuardado.subrayado : Boolean(elemento.subrayado),
        color_texto: colorTabla(estiloGuardado.color_texto, elemento.color_texto || '#0f172a'),
        alineacion: ['IZQUIERDA', 'CENTRO', 'DERECHA', 'JUSTIFICADO'].includes(alineacionCelda)
            ? alineacionCelda
            : 'IZQUIERDA',
    };
}

function crearCelda(elemento, izquierda, arriba, ancho, alto, texto, encabezado, filaIndice = 0, etiqueta = false, formatoCelda = null) {
    const estilo = elemento.configuracion?.tabla || {};
    const tablaCampoValor = estilo.tipo === 'CAMPO_VALOR';
    const fondo = encabezado
        ? (estilo.encabezado_fondo || '#f1f5f9')
        : (tablaCampoValor
            ? 'transparent'
            : estilo.filas_alternas && filaIndice % 2
            ? (estilo.filas_alternas_color || '#f8fafc')
            : (estilo.celdas_fondo || '#ffffff'));
    const paddingX = numero(estilo.padding_x, 6);
    const paddingY = numero(estilo.padding_y, 4);
    const anchoTexto = Math.max(10, ancho - paddingX * 2);
    const altoTexto = Math.max(10, alto - paddingY * 2);
    const formato = formatoCelda || estiloCeldaTabla(elemento, filaIndice, 0, encabezado);
    const textoCelda = textoAjustadoCelda(
        texto,
        anchoTexto,
        altoTexto,
        formato.tamano_letra,
        numero(estilo.tamano_minimo, 7),
        estilo.ajustar_texto !== false && estilo.tipo !== 'CAMPO_VALOR',
        {
            left: izquierda + paddingX,
            top: arriba + paddingY,
            originX: 'left',
            originY: 'top',
            fontFamily: formato.tipo_letra,
            fontWeight: formato.negrita ? 'bold' : 'normal',
            fontStyle: formato.cursiva ? 'italic' : 'normal',
            underline: formato.subrayado,
            fill: encabezado ? (estilo.encabezado_texto || '#0f172a') : formato.color_texto,
            textAlign: alineacion(formato.alineacion),
        },
    );
    const altoRealTexto = Math.min(numero(textoCelda.height), altoTexto);
    // Campo/Valor crece por fila cuando el contenido salta de línea. Ambas
    // celdas deben empezar arriba; si se centran, la etiqueta corta queda en
    // medio del valor largo y aparenta estar desalineada.
    const alineacionVertical = tablaCampoValor ? 'SUPERIOR' : estilo.alineacion_vertical;
    textoCelda.top = arriba + paddingY + desplazamientoVerticalCelda(alineacionVertical, altoTexto, altoRealTexto);

    return [
        new Rect({
        left: izquierda,
        top: arriba,
        originX: 'left',
        originY: 'top',
        width: ancho,
        height: alto,
            fill: fondo,
            stroke: estilo.borde_color || '#0f172a',
            strokeWidth: estilo.bordes === 'NINGUNO' ? 0 : numero(estilo.borde_ancho, 1),
        }),
        textoCelda,
    ];
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

function desplazamientoVerticalCelda(alineacionVertical, altoDisponible, altoTexto) {
    if (alineacionVertical === 'INFERIOR') return Math.max(0, altoDisponible - altoTexto);
    if (alineacionVertical === 'MEDIO') return Math.max(0, (altoDisponible - altoTexto) / 2);
    return 0;
}

function crearTabla(elemento) {
    const columnas = Array.isArray(elemento.columnas) ? elemento.columnas : [];
    const filas = Array.isArray(elemento.filas) && elemento.filas.length ? elemento.filas : [[]];
    const objetos = [];
    const estilo = elemento.configuracion?.tabla || {};
    const mostrarEncabezado = estilo.mostrar_encabezado !== false;
    const tablaCampoValor = estilo.tipo === 'CAMPO_VALOR';
    const anchoTabla = numero(elemento.ancho, 520);
    const anchosColumnas = columnas.map((columna) => (
        anchoTabla * (numero(columna.ancho, 100 / Math.max(1, columnas.length)) / 100)
    ));
    const alturaMinimaFila = Math.max(28, Math.min(42, numero(elemento.alto, 118) / Math.max(1, filas.length + (mostrarEncabezado ? 1 : 0))));
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
            objetos.push(...crearCelda(
                elemento,
                izquierda,
                arriba,
                anchosColumnas[columnaIndice],
                alturaEncabezado,
                columna.titulo_columna || 'Columna',
                true,
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
            objetos.push(...crearCelda(
                elemento,
                izquierda,
                arriba,
                anchosColumnas[columnaIndice],
                alturasFilas[filaIndice],
                fila[columnaIndice] ?? '',
                false,
                filaIndice,
                tablaCampoValor && columnaIndice % 2 === 0,
                estiloCeldaTabla(elemento, filaIndice, columnaIndice),
            ));
            izquierda += anchosColumnas[columnaIndice];
        });
        arriba += alturasFilas[filaIndice];
    });

    return new Group(objetos, {
        originX: 'left',
        originY: 'top',
    });
}

function alturaFilaTabla(elemento, fila, filaIndice, columnas, anchosColumnas, alturaMinima) {
    if (elemento.configuracion?.tabla?.tipo !== 'CAMPO_VALOR') return alturaMinima;

    const tabla = elemento.configuracion?.tabla || {};
    const paddingX = numero(tabla.padding_x, 6);
    const paddingY = numero(tabla.padding_y, 4);

    return columnas.reduce((alturaMayor, columna, columnaIndice) => {
        const formato = estiloCeldaTabla(elemento, filaIndice, columnaIndice);
        const texto = new Textbox(String(fila[columnaIndice] ?? ''), {
            width: Math.max(10, anchosColumnas[columnaIndice] - paddingX * 2),
            fontSize: formato.tamano_letra,
            fontFamily: formato.tipo_letra,
            fontWeight: formato.negrita ? 'bold' : 'normal',
            fontStyle: formato.cursiva ? 'italic' : 'normal',
            underline: formato.subrayado,
            lineHeight: 1.05,
            splitByGrapheme: true,
        });
        return Math.max(alturaMayor, texto.height + paddingY * 2);
    }, alturaMinima);
}

async function crearObjeto(elemento, valores = {}) {
    const tipo = String(elemento.tipo_elemento || 'CAMPO').toUpperCase();
    const configuracion = elemento.configuracion || {};
    const ancho = Math.max(1, numero(elemento.ancho, 1));
    let alto = Math.max(1, numero(elemento.alto, 1));
    let objeto;

    if (tipo === 'FORMA') {
        objeto = configuracion.forma === 'ELIPSE'
            ? new Ellipse({ rx: ancho / 2, ry: alto / 2 })
            : new Rect({ width: ancho, height: alto, rx: numero(configuracion.radio_borde), ry: numero(configuracion.radio_borde) });
        objeto.set({
            fill: configuracion.fondo_color || '#d1fae5',
            stroke: configuracion.borde_color || '#0f766e',
            strokeWidth: numero(configuracion.borde_ancho, 2),
        });
    } else if (tipo === 'LINEA') {
        objeto = new Line([0, 0, ancho, 0], {
            stroke: configuracion.borde_color || '#0f766e',
            strokeWidth: Math.max(1, numero(configuracion.borde_ancho, 1)),
            strokeDashArray: patronTrazo(configuracion.borde_estilo),
        });
    } else if (tipo === 'IMAGEN' && elemento.valor) {
        try {
            objeto = await FabricImage.fromURL(elemento.valor, { crossOrigin: 'anonymous' });
        } catch (error) {
            objeto = crearMarcador('Imagen no disponible', ancho, alto);
        }
    } else if (tipo === 'QR') {
        if (String(elemento.valor || '').startsWith('data:image')) {
            try {
                objeto = await FabricImage.fromURL(elemento.valor);
            } catch (error) {
                objeto = crearMarcador('QR', ancho, alto);
            }
        } else {
            objeto = crearMarcador('QR', ancho, alto);
        }
    } else if (tipo === 'TABLA') {
        objeto = crearTabla(elemento);
    } else {
        const plantilla = elemento.texto_plantilla ?? elemento.valor ?? '';
        let resuelto = resolverMarcadoresConEstilos(plantilla, estilosTexto(configuracion), valores);
        if (tipo === 'CAMPO' && elemento.codigo_elemento
            && Object.prototype.hasOwnProperty.call(valores, elemento.codigo_elemento)) {
            const valorCampo = String(valores[elemento.codigo_elemento] ?? '');
            if (resuelto.texto !== valorCampo) {
                const estilo = estiloRepresentativo(caracteresConEstilo(plantilla, estilosTexto(configuracion)));
                const tokens = Array.from(valorCampo).map((valor) => ({ valor, estilo: { ...estilo } }));
                resuelto = { texto: valorCampo, estilos: estilosDesdeCaracteres(tokens) };
            }
        }

        objeto = new Textbox(resuelto.texto, {
            width: ancho,
            fontSize: numero(elemento.tamano_letra, 11),
            fontFamily: elemento.tipo_letra || 'Arial',
            fontWeight: elemento.negrita ? 'bold' : 'normal',
            fontStyle: elemento.cursiva ? 'italic' : 'normal',
            underline: Boolean(elemento.subrayado),
            fill: elemento.color_texto || '#0f172a',
            lineHeight: numero(elemento.interlineado, 1.25),
            textAlign: alineacion(elemento.alineacion),
            splitByGrapheme: false,
            objectCaching: false,
            styles: resuelto.estilos,
        });
    }

    const anchoNatural = Math.max(1, objeto.width || ancho);
    const altoNatural = Math.max(1, objeto.height || alto);
    const tablaDatos = tipo === 'TABLA' && configuracion.tabla?.tipo === 'CAMPO_VALOR';
    if (tablaDatos) alto = altoNatural;
    objeto.set({
        left: numero(elemento.posicion_x),
        top: numero(elemento.posicion_y),
        originX: 'left',
        originY: 'top',
        angle: numero(configuracion.rotacion),
        opacity: numero(configuracion.opacidad, 1),
        // La tabla se construye con celdas directas. Esta escala solo iguala
        // el ancho lógico guardado y no modifica la distribución Campo/Valor.
        scaleX: ancho / anchoNatural,
        scaleY: ['TEXTO', 'CAMPO', 'FIRMA', 'LINEA'].includes(tipo) || tablaDatos ? 1 : alto / altoNatural,
        visible: configuracion.visible !== false,
        selectable: false,
        evented: false,
    });
    objeto.setCoords();
    return objeto;
}

async function agregarFondo(lienzo, datos) {
    if (!datos.fondo) return;

    try {
        const imagen = await FabricImage.fromURL(datos.fondo, { crossOrigin: 'anonymous' });
        const anchoImagen = Math.max(1, imagen.width || datos.ancho);
        const altoImagen = Math.max(1, imagen.height || datos.alto);
        let escalaX = datos.ancho / anchoImagen;
        let escalaY = datos.alto / altoImagen;
        const ajuste = String(datos.ajuste_fondo || 'ESTIRAR').toUpperCase();

        if (ajuste === 'CONTENER' || ajuste === 'CUBRIR') {
            const uniforme = ajuste === 'CONTENER' ? Math.min(escalaX, escalaY) : Math.max(escalaX, escalaY);
            escalaX = uniforme;
            escalaY = uniforme;
        }

        imagen.set({
            left: (datos.ancho - anchoImagen * escalaX) / 2,
            top: (datos.alto - altoImagen * escalaY) / 2,
            originX: 'left',
            originY: 'top',
            scaleX: escalaX,
            scaleY: escalaY,
            selectable: false,
            evented: false,
        });
        lienzo.insertAt(0, imagen);
    } catch (error) {
        // Si el fondo no puede cargarse, se mantiene el lienzo blanco y el fallback HTML.
    }
}

async function iniciarPrevisualizacion() {
    const canvas = document.querySelector(SELECTOR);
    const datos = window.plantillaCertificadoEmision;

    if (!canvas || !datos || canvas.dataset.fabricInicializado === '1') return;
    canvas.dataset.fabricInicializado = '1';

    await cargarFuentes();
    config.configure({ devicePixelRatio: Math.max(window.devicePixelRatio || 1, 2) });

    const lienzo = new StaticCanvas(canvas, {
        width: numero(datos.ancho, 1056),
        height: numero(datos.alto, 816),
        enableRetinaScaling: true,
        imageSmoothingEnabled: true,
        renderOnAddRemove: false,
    });
    lienzo.backgroundColor = '#ffffff';
    const disenoFabric = datos.diseno_fabric;
    if (Array.isArray(disenoFabric?.objects) && disenoFabric.objects.length) {
        await lienzo.loadFromJSON({
            version: disenoFabric.version,
            objects: disenoFabric.objects,
        });
        const objetosGuardados = [...lienzo.getObjects()];
        const elementosPorIndice = new Map((datos.elementos || [])
            .filter((elemento) => Number.isInteger(Number(elemento.indice_plantilla)))
            .map((elemento) => [Number(elemento.indice_plantilla), elemento]));
        const objetosFinales = await Promise.all(objetosGuardados.map(async (objeto, posicion) => {
            const indice = Number(objeto.plantillaIndice);
            const elemento = Number.isInteger(indice)
                ? elementosPorIndice.get(indice)
                : datos.elementos?.[posicion];

            // Una tabla se reconstruye con los valores reales: así sus filas crecen
            // antes de generar el certificado y ningún nombre largo queda recortado.
            if (String(elemento?.tipo_elemento || '').toUpperCase() === 'TABLA') {
                const tabla = await crearObjeto(elemento, datos.valores || {});
                tabla.plantillaIndice = Number.isInteger(indice) ? indice : posicion;
                tabla.plantillaTipoElemento = 'TABLA';
                tabla.plantillaCodigoElemento = elemento.codigo_elemento || null;
                tabla.plantillaTextoPlantilla = elemento.texto_plantilla || '';
                return tabla;
            }

            // El QR se vuelve a construir desde su capa, no desde el marcador
            // guardado por Fabric. Así puede reemplazarse por el QR temporal
            // justo antes de capturar el PNG oficial.
            if (String(elemento?.tipo_elemento || '').toUpperCase() === 'QR') {
                const qr = await crearObjeto(elemento, datos.valores || {});
                qr.plantillaIndice = Number.isInteger(indice) ? indice : posicion;
                qr.plantillaTipoElemento = 'QR';
                qr.plantillaCodigoElemento = elemento.codigo_elemento || null;
                qr.plantillaTextoPlantilla = elemento.texto_plantilla || '';
                return qr;
            }

            aplicarDatosDinamicos(objeto, datos.valores || {});
            objeto.set({ selectable: false, evented: false });
            return objeto;
        }));
        lienzo.remove(...objetosGuardados);
        lienzo.add(...objetosFinales);
        await agregarFondo(lienzo, datos);
    } else {
        await agregarFondo(lienzo, datos);
        const objetos = await Promise.all(
            (datos.elementos || []).map((elemento) => crearObjeto(elemento, datos.valores || {})),
        );
        lienzo.add(...objetos);
    }
    const publicarSalida = () => {
        lienzo.renderAll();

        const salidaSvg = document.querySelector('[data-certificado-fabric-svg]');
        const fallback = document.querySelector('[data-certificado-html-fallback]');
        const campoDocumento = document.querySelector('[data-documento-renderizado]');
        const resultado = lienzo.toDataURL({
            format: 'png',
            multiplier: multiplicadorDocumento(lienzo.getWidth(), lienzo.getHeight()),
        });

        // El SVG mantiene textos, líneas y formas como vectores en la vista y al imprimir.
        // El PNG de 300 DPI se conserva como respaldo para generar el PDF almacenado.
        if (salidaSvg) {
            salidaSvg.innerHTML = lienzo.toSVG();
            salidaSvg.hidden = false;
        }
        if (fallback) fallback.hidden = true;
        if (campoDocumento) campoDocumento.value = resultado;
        document.querySelectorAll('[data-requiere-render-certificado]').forEach((boton) => {
            boton.disabled = false;
        });
    };

    // Antes de guardar permite incorporar al diseño las fechas escritas en el modal.
    window.actualizarDatosCertificadoEmision = async (valores = {}) => {
        datos.valores = { ...(datos.valores || {}), ...valores };
        lienzo.getObjects().forEach((objeto) => aplicarDatosDinamicos(objeto, datos.valores));
        publicarSalida();
    };

    // La API devuelve una imagen SVG temporal. Se incorpora únicamente al
    // lienzo que será capturado para esta emisión; no modifica la plantilla.
    window.prepararQrCertificadoEmision = async (imagenQr) => {
        if (!String(imagenQr || '').startsWith('data:image')) {
            throw new Error('La imagen del código QR no es válida.');
        }

        datos.valores = { ...(datos.valores || {}), 'qr.verificacion': imagenQr };
        const objetos = [...lienzo.getObjects()];
        const elementosPorIndice = new Map((datos.elementos || [])
            .filter((elemento) => Number.isInteger(Number(elemento.indice_plantilla)))
            .map((elemento) => [Number(elemento.indice_plantilla), elemento]));
        let reemplazados = 0;

        for (const [posicion, objeto] of objetos.entries()) {
            const indice = Number(objeto.plantillaIndice);
            const elemento = Number.isInteger(indice)
                ? elementosPorIndice.get(indice)
                : null;

            if (String(elemento?.tipo_elemento || '').toUpperCase() !== 'QR'
                || String(elemento?.codigo_elemento || '').toLowerCase() !== 'qr.verificacion') {
                continue;
            }

            const qr = await crearObjeto({ ...elemento, valor: imagenQr }, datos.valores);
            qr.plantillaIndice = indice;
            qr.plantillaTipoElemento = 'QR';
            qr.plantillaCodigoElemento = elemento.codigo_elemento || null;
            qr.plantillaTextoPlantilla = elemento.texto_plantilla || '';
            lienzo.remove(objeto);
            lienzo.insertAt(posicion, qr);
            reemplazados += 1;
        }

        if (reemplazados === 0) {
            throw new Error('La plantilla ya no tiene una ubicación válida para el código QR. Revise la plantilla activa.');
        }

        publicarSalida();
    };

    publicarSalida();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarPrevisualizacion);
} else {
    iniciarPrevisualizacion();
}
