import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import vm from 'node:vm';

const editor = readFileSync(new URL('../../resources/js/plantilla-certificado-editor.js', import.meta.url), 'utf8');
const emision = readFileSync(new URL('../../resources/js/plantilla-certificado-emision.js', import.meta.url), 'utf8');

function funcion(fuente, nombre, siguiente) {
    const inicio = fuente.indexOf(`    function ${nombre}(`);
    return fuente.slice(inicio, fuente.indexOf(`    function ${siguiente}(`, inicio));
}

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
