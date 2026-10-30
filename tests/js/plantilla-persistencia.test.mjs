import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const editor = readFileSync(new URL('../../resources/js/plantilla-certificado-editor.js', import.meta.url), 'utf8');
const emision = readFileSync(new URL('../../resources/js/plantilla-certificado-emision.js', import.meta.url), 'utf8');
const vistaEmision = readFileSync(new URL('../../resources/views/certificados/emitir_certificado/create.blade.php', import.meta.url), 'utf8');

test('la vista previa incorpora el mismo número al documento y al envío antes de preparar el QR', async () => {
    const campoNumero = { value: '' };
    const orden = [];
    let valores;
    const contexto = vm.createContext({
        formulario: {
            dataset: { prepararNumeroUrl: '/preparar-numero' },
            querySelector: selector => selector.includes('_token') ? { value: 'token' } : campoNumero,
            querySelectorAll: () => [],
        },
        fetch: async () => ({ ok: true, json: async () => ({ numero_certificado: 'CAP-10-2026-1' }) }),
        window: { actualizarDatosCertificadoEmision: async datos => { valores = datos; orden.push('numero'); } },
        prepararVistaQr: async () => { orden.push('qr'); },
    });
    const inicio = vistaEmision.indexOf('                let preparacionNumeroEnCurso =');
    const fin = vistaEmision.indexOf('                const prepararAutomaticamente', inicio);
    vm.runInContext(vistaEmision.slice(inicio, fin), contexto);
    await vm.runInContext('window.prepararVistaQrEmision()', contexto);
    assert.equal(campoNumero.value, 'CAP-10-2026-1');
    assert.equal(valores['emision.numero_certificado'], campoNumero.value);
    assert.equal(valores['emision_numero_certificado'], campoNumero.value);
    assert.deepEqual(orden, ['numero', 'qr']);
});

function funcion(fuente, nombre, siguiente) {
    const inicio = fuente.indexOf(`    function ${nombre}(`);
    return fuente.slice(inicio, fuente.indexOf(`    function ${siguiente}(`, inicio));
}

test('el QR se reemplaza también al reconstruir una plantilla sin JSON de Fabric', async () => {
    const elemento = {
        indice_plantilla: 4, tipo_elemento: 'QR', codigo_elemento: 'qr.verificacion',
        ancho: 90, alto: 90, posicion_x: 120, posicion_y: 300,
    };
    function imagen() {
        return { width: 90, height: 90, set(valores) { Object.assign(this, valores); }, setCoords() {} };
    }
    const objetos = [];
    let publicado = false;
    const contexto = vm.createContext({
        elemento, datos: { elementos: [elemento], valores: {} }, window: {},
        numero: (valor, predeterminado = 0) => Number.isFinite(Number(valor)) ? Number(valor) : predeterminado,
        crearMarcador: imagen, FabricImage: { fromURL: async () => imagen() },
        lienzo: {
            getObjects: () => objetos,
            remove(objeto) { objetos.splice(objetos.indexOf(objeto), 1); },
            insertAt(posicion, objeto) { objetos.splice(posicion, 0, objeto); },
        },
        publicarSalida() { publicado = true; },
    });
    const inicio = emision.indexOf('async function crearObjeto(');
    const fin = emision.indexOf('async function agregarFondo(', inicio);
    vm.runInContext(emision.slice(inicio, fin), contexto);
    objetos.push(await vm.runInContext('crearObjeto(elemento)', contexto));
    assert.equal(objetos[0].plantillaIndice, 4);
    const desdeQr = emision.indexOf('    window.prepararQrCertificadoEmision =');
    const hastaQr = emision.indexOf('\n    publicarSalida();\n    document.dispatchEvent', desdeQr);
    vm.runInContext(emision.slice(desdeQr, hastaQr), contexto);
    await vm.runInContext("window.prepararQrCertificadoEmision('data:image/svg+xml;base64,real')", contexto);
    assert.equal(objetos.length, 1);
    assert.equal(objetos[0].plantillaIndice, 4);
    assert.equal(objetos[0].left, 120);
    assert.equal(objetos[0].top, 300);
    assert.equal(publicado, true);
});

test('el fondo visual permanece fuera de los píxeles que Fabric borra durante el arrastre', () => {
    const estilo = {};
    const lienzo = { lowerCanvasEl: { style: estilo } };
    const contexto = vm.createContext({ lienzo, urlFondo: '/plantillas/42/archivo' });
    const inicio = editor.indexOf('    function aplicarFondoFijoEditor(');
    const fin = editor.indexOf('    async function aplicarFondoVisual()', inicio);
    vm.runInContext(editor.slice(inicio, fin), contexto);
    vm.runInContext("aplicarFondoFijoEditor(true, 'CONTENER')", contexto);
    assert.equal(estilo.backgroundImage, 'url("/plantillas/42/archivo")');
    assert.equal(estilo.backgroundSize, 'contain');
    assert.equal(lienzo.backgroundColor, 'transparent');
    lienzo.backgroundImage = undefined;
    assert.equal(estilo.backgroundImage, 'url("/plantillas/42/archivo")');
    vm.runInContext("aplicarFondoFijoEditor(true, 'CUBRIR')", contexto);
    assert.equal(estilo.backgroundSize, 'cover');
    vm.runInContext("aplicarFondoFijoEditor(false, 'ESTIRAR')", contexto);
    assert.equal(estilo.backgroundImage, 'none');
    assert.equal(lienzo.backgroundColor, '#ffffff');
});

test('el fondo fijo sobrevive a los redibujos sin convertirse en una capa seleccionable', () => {
    const fondo = { set(valores) { Object.assign(this, valores); } };
    const lienzo = {};
    const controles = { escenario: { dataset: {} }, selectorFondoTrabajo: { value: 'PLANTILLA' } };
    const contexto = vm.createContext({ fondoFabric: fondo, urlFondoFabric: '/fondo', urlFondo: '/fondo', lienzo, controles });
    const inicio = editor.indexOf('    function restaurarFondoFijo()');
    const fin = editor.indexOf('    async function aplicarFondoVisual()', inicio);
    vm.runInContext(editor.slice(inicio, fin), contexto);
    for (let movimiento = 0; movimiento < 5; movimiento++) {
        lienzo.backgroundImage = undefined;
        vm.runInContext('restaurarFondoFijo()', contexto);
        assert.equal(lienzo.backgroundImage, fondo);
        assert.equal(fondo.canvas, lienzo);
        assert.equal(fondo.selectable, false);
        assert.equal(fondo.evented, false);
    }
    controles.selectorFondoTrabajo.value = 'BLANCO';
    lienzo.backgroundImage = undefined;
    vm.runInContext('restaurarFondoFijo()', contexto);
    assert.equal(lienzo.backgroundImage, undefined);
    controles.selectorFondoTrabajo.value = 'PLANTILLA';
    contexto.urlFondo = '';
    vm.runInContext('restaurarFondoFijo()', contexto);
    assert.equal(lienzo.backgroundImage, undefined);
});

test('conserva negrita parcial después del JSON de PHP y de reconstruir las capas', () => {
    const contexto = vm.createContext({});
    vm.runInContext(funcion(editor, 'normalizarEstilosTexto', 'patronTrazoLinea'), contexto);
    // PHP convierte el mapa de una única línea en un array JSON, también si
    // el formato comienza por el primer carácter del texto.
    for (const guardado of [
        [{ 4: { fontWeight: 'bold' }, 5: { underline: true } }],
        [[{ fontWeight: 'bold' }, { fontWeight: 'bold' }]],
    ]) {
        contexto.guardado = guardado;
        const normalizado = vm.runInContext('clonarEstilosTexto(guardado)', contexto);
        for (const [caracter, estilo] of Object.entries(guardado[0])) {
            assert.deepEqual(JSON.parse(JSON.stringify(normalizado[0][caracter])), estilo);
        }
        assert.equal(normalizado[0][20], undefined);
        contexto.guardado = JSON.parse(JSON.stringify(normalizado));
        assert.deepEqual(
            JSON.parse(JSON.stringify(vm.runInContext('clonarEstilosTexto(guardado)', contexto))),
            JSON.parse(JSON.stringify(normalizado)),
        );
    }
    contexto.guardado = [{ 0: { fontWeight: 'bold', propiedadNoPermitida: 'valor' } }];
    assert.equal(vm.runInContext('clonarEstilosTexto(guardado)[0][0].propiedadNoPermitida', contexto), undefined);
});

test('la emisión conserva los estilos de texto recibidos como arrays desde PHP', () => {
    const inicio = emision.indexOf('function estilosTexto(');
    const fin = emision.indexOf('function caracteresConEstilo(', inicio);
    const contexto = vm.createContext({});
    vm.runInContext(emision.slice(inicio, fin), contexto);
    assert.equal(vm.runInContext("estiloCaracter(estilosTexto({ estilos_texto: [[{ fontWeight: 'bold' }]] }), 0, 0).fontWeight", contexto), 'bold');
});

test('una imagen puede reservar el lugar del QR y conservar su referencia y geometría', () => {
    class Input {}
    const elemento = {
        tipo_elemento: 'IMAGEN', codigo_elemento: null,
        texto_fijo: 'data:image/png;base64,referencia',
        posicion_x: 100, posicion_y: 200, ancho: 90, alto: 90,
    };
    let redibujos = 0;
    const contexto = vm.createContext({
        HTMLInputElement: Input,
        elementoSeleccionado: () => elemento,
        renderizarTodo() { redibujos++; },
    });
    const inicio = editor.indexOf('    function actualizarPropiedad(');
    const fin = editor.indexOf('    let revisionVistaPropiedad', inicio);
    vm.runInContext(editor.slice(inicio, fin), contexto);
    const input = new Input();
    Object.assign(input, { dataset: { propImagenQr: '' }, checked: true });
    contexto.evento = { target: input, type: 'change' };
    vm.runInContext('actualizarPropiedad(evento)', contexto);
    assert.equal(elemento.tipo_elemento, 'QR');
    assert.equal(elemento.codigo_elemento, 'qr.verificacion');
    assert.equal(elemento.texto_fijo, 'data:image/png;base64,referencia');
    assert.deepEqual([elemento.posicion_x, elemento.posicion_y, elemento.ancho, elemento.alto], [100, 200, 90, 90]);
    input.checked = false;
    vm.runInContext('actualizarPropiedad(evento)', contexto);
    assert.equal(elemento.tipo_elemento, 'IMAGEN');
    assert.equal(elemento.codigo_elemento, null);
    assert.equal(redibujos, 2);
});

test('el evento input cambia el color del texto sin perder el foco ni reconstruir el editor', () => {
    class Input {
        constructor() {
            this.dataset = { propGrupo: 'elemento', propiedad: 'color_texto' };
            this.type = 'color';
            this.value = '#ff0000';
        }
    }
    class Textbox {
        set(valores) { Object.assign(this, valores); }
        initDimensions() {}
        setCoords() {}
    }
    const objeto = new Textbox();
    const elemento = { tipo_elemento: 'TEXTO', texto_fijo: 'Texto', configuracion: {} };
    let dibujos = 0;
    let guardados = 0;
    const contexto = vm.createContext({
        HTMLInputElement: Input, HTMLSelectElement: class {}, HTMLTextAreaElement: class {},
        Textbox, indiceSeleccionado: 0,
        elementoSeleccionado: () => elemento, objetoPorIndice: () => objeto,
        alineacionFabric: () => 'left', clonarEstilosTexto: () => ({}),
        lienzo: { requestRenderAll() { dibujos++; } },
        actualizarInput() { guardados++; },
        renderizarCapas() {},
        numero: (valor) => Number(valor),
    });
    const inicio = editor.indexOf('    function actualizarPropiedad(');
    const fin = editor.indexOf('    function escaparHtml(', inicio);
    vm.runInContext(editor.slice(inicio, fin), contexto);
    contexto.evento = { type: 'input', target: new Input() };
    vm.runInContext('actualizarPropiedad(evento)', contexto);
    assert.equal(elemento.color_texto, '#ff0000');
    assert.equal(objeto.fill, '#ff0000');
    assert.equal(dibujos, 1);
    assert.equal(guardados, 1);
    contexto.evento.target.value = '#0000ff';
    vm.runInContext('actualizarPropiedad(evento)', contexto);
    assert.equal(objeto.fill, '#0000ff');
    assert.equal(dibujos, 2);
    for (const [grupo, propiedad, valor, propiedadFabric, esperado] of [
        ['elemento', 'tamano_letra', 32, 'fontSize', 32],
        ['elemento', 'posicion_x', 125, 'left', 125],
        ['elemento', 'posicion_y', 85, 'top', 85],
        ['elemento', 'ancho', 240, 'width', 240],
        ['elemento', 'texto_fijo', 'Nuevo contenido', 'text', 'Nuevo contenido'],
        ['configuracion', 'rotacion', 45, 'angle', 45],
        ['porcentaje', 'opacidad', 50, 'opacity', 0.5],
    ]) {
        Object.assign(contexto.evento.target, {
            dataset: { propGrupo: grupo, propiedad },
            type: typeof valor === 'number' ? 'number' : 'text', value: valor,
        });
        vm.runInContext('actualizarPropiedad(evento)', contexto);
        assert.equal(objeto[propiedadFabric], esperado, propiedad);
    }
    for (const propiedad of ['visible', 'bloqueado']) {
        Object.assign(contexto.evento.target, {
            dataset: { propConfig: propiedad }, type: 'checkbox', checked: true,
        });
        vm.runInContext('actualizarPropiedad(evento)', contexto);
        assert.equal(objeto[propiedad === 'visible' ? 'visible' : 'lockMovementX'], true);
    }
});

test('reabrir una imagen guardada mediante una ruta sin extensión mantiene el fondo visual', async () => {
    let imagenDetectada;
    const controles = { nombreFondo: { textContent: 'certificado-guardado.png' } };
    const contexto = vm.createContext({
        controles,
        urlFondo: '/certificados/plantillas/42/archivo',
        window: {},
        requestAnimationFrame() {},
        aplicarZoom() {},
        async cargarFuentesDelSistema() {},
        configurarEventosLienzo() {}, configurarCamposDisponibles() {},
        configurarBarraHerramientas() {}, configurarPaneles() {},
        configurarFondo() {}, configurarFormulario() {}, actualizarTipoSeleccionado() {},
        actualizarPapel() {}, actualizarCuadricula() {},
        mostrarFondo(nombre, url, esImagen) { imagenDetectada = esImagen; },
        async cargarDisenoFabricGuardado() { return false; },
        async renderizarTodo() {},
    });
    const inicio = editor.indexOf('    async function iniciar()');
    const fin = editor.indexOf('    async function cargarFuentesDelSistema()', inicio);
    vm.runInContext(editor.slice(inicio, fin), contexto);
    await vm.runInContext('iniciar()', contexto);
    assert.equal(imagenDetectada, true);
    controles.nombreFondo.textContent = 'certificado.pdf';
    await vm.runInContext('iniciar()', contexto);
    assert.equal(imagenDetectada, false);
});
