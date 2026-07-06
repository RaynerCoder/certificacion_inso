 {{-- TOM SELECT - BUSCADOR DENTRO DEL MODAL --}}
 <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
 <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>

 {{-- ESTILOS PARA TOM SELECT - BUSCADOR DENTRO DEL MODAL --}}
 <style>
     .ts-wrapper.single .ts-control {
         min-height: 42px !important;
         height: 42px !important;

         display: flex !important;
         align-items: center !important;

         border-radius: 0.5rem !important;
         border: 1px solid #d1d5db !important;

         background: #fff !important;

         padding: 0 0.75rem !important;

         box-shadow: none !important;
     }

     .ts-wrapper.single .ts-control input {
         margin: 0 !important;
         padding: 0 !important;

         height: auto !important;
         line-height: 1.25rem !important;

         font-size: 0.875rem !important;
         color: #374151 !important;
     }

     .ts-wrapper.single .ts-control .item {
         margin: 0 !important;
         padding: 0 !important;

         line-height: 1.25rem !important;

         font-size: 0.875rem !important;
         color: #374151 !important;

         display: flex !important;
         align-items: center !important;
     }

     .ts-wrapper.focus .ts-control {
         border-color: #9ca3af !important;
         box-shadow: 0 0 0 3px rgba(156, 163, 175, 0.15) !important;
     }

     .ts-dropdown {
         border-radius: 0.65rem !important;
         border: 1px solid #e5e7eb !important;

         overflow: hidden !important;

         box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08) !important;
     }

     .ts-dropdown .option {
         padding: 0.7rem 0.85rem !important;
         font-size: 0.875rem !important;
     }

     .ts-dropdown .active {
         background: #f3f4f6 !important;
         color: #111827 !important;
     }
 </style>



 <!-- SCRIPT PARA TELEFONO -->
 <script>
     let indiceTelefono = 0;

     function agregarTelefonoPersona() {
         const numeroInput = document.getElementById('numeroTelefono');
         const tipoInput = document.getElementById('tipoTelefono');
         const lista = document.getElementById('listaTelefonosPersona');

         const numero = numeroInput.value.trim();
         const tipo = tipoInput.value;

         if (numero === '') {
             alert('Ingrese el número de teléfono.');
             return;
         }

         document.getElementById('mensajeSinTelefonos')?.remove();

         const item = document.createElement('div');

         item.className =
             'telefono-agregado inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-sm';

         item.innerHTML = `
            <span class="font-medium text-gray-700">
                ${numero}
            </span>

            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                ${tipo}
            </span>

            <button type="button"
                onclick="quitarTelefonoPersona(this)"
                class="text-red-500 hover:text-red-700 text-base font-bold leading-none">
                ×
            </button>

            <input type="hidden"
                name="telefonos[${indiceTelefono}][numero]"
                value="${numero}">

            <input type="hidden"
                name="telefonos[${indiceTelefono}][tipo]"
                value="${tipo}">
        `;

         lista.appendChild(item);

         numeroInput.value = '';
         tipoInput.value = 'CELULAR';

         indiceTelefono++;
     }

     function quitarTelefonoPersona(boton) {
         boton.closest('.telefono-agregado').remove();

         const lista = document.getElementById('listaTelefonosPersona');

         if (lista.children.length === 0) {
             lista.innerHTML = `
                <span id="mensajeSinTelefonos" class="text-sm text-gray-500">
                    Todavía no se agregaron teléfonos.
                </span>
            `;
         }
     }
 </script>


 <!-- SCRIPT PARA RUBRO -->
 <script>
     let indiceRubro = 0;

     function agregarRubroPersona() {
         const nombreInput = document.getElementById('nombreRubro');
         const estadoInput = document.getElementById('estadoRubro');
         const lista = document.getElementById('listaRubrosPersona');
         const nombre = nombreInput.value.trim();
         const estado = estadoInput.value;
         const textoEstado = estado === 'ACTIVO' ? 'Activo' : 'Inactivo';

         if (nombre === '') {
             alert('Ingrese el nombre del rubro.');
             return;
         }

         document.getElementById('mensajeSinRubros')?.remove();

         const item = document.createElement('div');

         // El chip del rubro permite varias lineas para no cortar nombres largos.
         item.className =
             'rubro-agregado inline-flex max-w-full flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm';

         item.innerHTML = `
            <span class="min-w-0 max-w-full whitespace-normal break-words font-medium leading-snug text-gray-700">
                ${nombre}
            </span>

            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                ${estado === 'ACTIVO'
                    ? 'bg-green-100 text-green-700'
                    : 'bg-red-100 text-red-700'}">
                ${textoEstado}
            </span>

            <button type="button"
                onclick="quitarRubroPersona(this)"
                class="text-red-500 hover:text-red-700 text-base font-bold leading-none">
                ×
            </button>

            <input type="hidden"
                name="rubros[${indiceRubro}][nombre]"
                value="${nombre}">

            <input type="hidden"
                name="rubros[${indiceRubro}][estado]"
                value="${estado}">
        `;

         lista.appendChild(item);

         nombreInput.value = '';
         estadoInput.value = 'ACTIVO';

         indiceRubro++;
     }

     function quitarRubroPersona(boton) {
         boton.closest('.rubro-agregado').remove();

         const lista = document.getElementById('listaRubrosPersona');

         if (lista.children.length === 0) {
             lista.innerHTML = `
                <span id="mensajeSinRubros" class="text-sm text-gray-500">
                    Todavía no se agregaron rubros.
                </span>
            `;
         }
     }
 </script>


 <!-- SCRIPT MAPA -->
 <script>
     let mapa = L.map('map').setView([-16.5000, -68.1500], 13);

     L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
         attribution: 'OpenStreetMap'
     }).addTo(mapa);

     let marcador = L.marker([-16.5000, -68.1500], {
         draggable: true
     }).addTo(mapa);

     marcador.on('dragend', function(event) {

         const posicion = event.target.getLatLng();

         document.getElementById('form_latitud').value = posicion.lat.toFixed(6);

         document.getElementById('form_longitud').value = posicion.lng.toFixed(6);
     });
 </script>

 <!-- SCRIPT TIPO REGISTRO -->
 <script>
     const tipoRegistro = document.getElementById('tipo_registro');
     const seccionNatural = document.getElementById('seccion_natural');
     const seccionEmpresa = document.getElementById('seccion_empresa');
     const seccionRubros = document.getElementById('seccion_rubros');
     const seccionResponsables = document.getElementById('seccion_responsables');
     const accionResponsablesWizard = document.getElementById('accion_responsables_wizard');
     const bloqueRubrosWizard = document.getElementById('bloque_rubros_wizard');
     const bloqueResponsablesWizard = document.getElementById('bloque_responsables_wizard');
     let tipoRegistroAnterior = tipoRegistro?.value || '';

     // Limpia el mensaje visual de error cuando el usuario corrige un campo.
     function limpiarErrorVisualCampo(campo) {
         if (!campo) return;

         campo.removeAttribute('aria-invalid');
         campo.classList.remove('border-red-500', 'border-red-600', 'ring-red-500');

         const contenedor = campo.closest('label, div') || campo.parentElement;
         const posiblesErrores = [
             campo.nextElementSibling,
             contenedor?.querySelector('[role="alert"]'),
             contenedor?.querySelector('.text-red-500'),
             contenedor?.querySelector('.text-red-600'),
             contenedor?.querySelector('.text-negative-500'),
             contenedor?.querySelector('.text-negative-600'),
         ].filter(Boolean);

         posiblesErrores.forEach(error => {
             const texto = error.textContent?.trim() || '';

             if (texto !== '') {
                 error.remove();
             }
         });
     }

     // Limpia un campo y dispara eventos para que WireUI/JS actualicen su estado.
     function limpiarCampoPersonaWizard(name) {
         const campo = document.querySelector(`[name="${name}"]`);

         if (!campo) return;

         campo.value = '';
         campo.setAttribute('value', '');
         campo.dispatchEvent(new Event('input', { bubbles: true }));
         campo.dispatchEvent(new Event('change', { bubbles: true }));
         limpiarErrorVisualCampo(campo);
     }

     // Restaura una lista dinamica a su mensaje inicial y elimina inputs hidden viejos.
     function limpiarListaDinamicaPersonaWizard(selectorLista, selectorItems, mensajeId, mensajeTexto) {
         document.querySelectorAll(selectorItems).forEach(item => item.remove());

         const lista = document.querySelector(selectorLista);

         if (lista) {
             lista.innerHTML = `
                <span id="${mensajeId}" class="text-sm text-gray-500">
                    ${mensajeTexto}
                </span>
            `;
         }
     }

     // Limpia los datos generales porque al cambiar el tipo se inicia un registro distinto.
     function limpiarDatosGeneralesPersonaWizard() {
         [
             'form_domicilio',
             'form_nit',
             'form_correo',
             'form_id_territorio'
         ].forEach(name => limpiarCampoPersonaWizard(name));

         const estadoPersona = document.querySelector('[name="form_estado"]');
         if (estadoPersona) {
             estadoPersona.value = 'ACTIVO';
             estadoPersona.dispatchEvent(new Event('change', { bubbles: true }));
             limpiarErrorVisualCampo(estadoPersona);
         }
     }

     // Limpia telefonos porque son contactos del registro anterior.
     function limpiarTelefonosPersonaWizard() {
         limpiarCampoPersonaWizard('numeroTelefono');

         const tipoTelefono = document.getElementById('tipoTelefono');
         if (tipoTelefono) {
             tipoTelefono.value = 'CELULAR';
         }

         limpiarListaDinamicaPersonaWizard(
             '#listaTelefonosPersona',
             '.telefono-agregado',
             'mensajeSinTelefonos',
             'Todavía no se agregaron teléfonos.'
         );

         if (typeof indiceTelefono !== 'undefined') {
             indiceTelefono = 0;
         }
     }

     // Limpia todo lo que pertenece al tipo anterior, dejando solamente el nuevo tipo seleccionado.
     function limpiarDatosPorCambioTipoPersonaWizard() {
         limpiarDatosGeneralesPersonaWizard();
         limpiarTelefonosPersonaWizard();
         limpiarSeccionNatural();
         limpiarSeccionEmpresa();
     }

     // Quita el error de un campo solo cuando ya tiene un valor corregido.
     function limpiarErrorSiCampoCorregido(campo) {
         if (!campo || !campo.name || campo.name === '_token') return;
         if (campo.type === 'file') return;

         const tieneValor = campo.type === 'checkbox' || campo.type === 'radio'
             ? campo.checked
             : String(campo.value || '').trim() !== '';

         if (tieneValor) {
             limpiarErrorVisualCampo(campo);
         }
     }

     function limpiarSeccionNatural() {

         const campos = [
             'form_nombres',
             'form_apellido_paterno',
             'form_apellido_materno',
             'form_apellido_casado',
             'form_ci',
             'form_complemento',
             'form_expedido',
             'form_fecha_nacimiento',
             'form_genero',
             'form_ocupacion'
         ];

         campos.forEach(name => {
             limpiarCampoPersonaWizard(name);
         });

         limpiarListaDinamicaPersonaWizard(
             '#listaRubrosPersona',
             '.rubro-agregado',
             'mensajeSinRubros',
             'Todavía no se agregaron rubros.'
         );

         if (typeof indiceRubro !== 'undefined') {
             indiceRubro = 0;
         }
     }

     function limpiarSeccionEmpresa() {

         const campos = [
             'form_id_tipo_empresa',
             'form_razon_social',
             'form_matricula',
             'form_latitud',
             'form_longitud',
             'form_estado_empresa'
         ];

         campos.forEach(name => {
             limpiarCampoPersonaWizard(name);
         });

         // Deja el estado inicial de empresa con el valor usado por la base de datos.
         const estadoEmpresa = document.querySelector('[name="form_estado_empresa"]');
         if (estadoEmpresa) {
             estadoEmpresa.value = 'ACTIVO';
         }

         mapa.setView([-16.5000, -68.1500], 13);

         marcador.setLatLng([-16.5000, -68.1500]);

         limpiarListaDinamicaPersonaWizard(
             '#listaResponsablesEmpresa',
             '.responsable-agregado',
             'mensajeSinResponsables',
             'Todavía no se agregaron responsables.'
         );

         if (typeof indiceResponsable !== 'undefined') {
             indiceResponsable = 0;
         }
     }

     function deshabilitarCampos(selector) {
         document.querySelectorAll(`${selector} input, ${selector} select, ${selector} textarea`)
             .forEach(campo => {
                 campo.disabled = true;
             });
     }

     function habilitarCampos(selector) {
         document.querySelectorAll(`${selector} input, ${selector} select, ${selector} textarea`)
             .forEach(campo => {
                 campo.disabled = false;
             });
     }


     function cambiarTipoRegistro(limpiarDatosIncompatibles = false) {
         seccionNatural.classList.add('hidden');
         seccionEmpresa.classList.add('hidden');
         seccionRubros.classList.add('hidden');
         seccionResponsables.classList.add('hidden');
         accionResponsablesWizard?.classList.add('hidden');
         accionResponsablesWizard?.classList.remove('is-visible');
         bloqueRubrosWizard?.classList.add('hidden');
         bloqueResponsablesWizard?.classList.add('hidden');

         deshabilitarCampos('#seccion_natural');
         deshabilitarCampos('#seccion_empresa');
         deshabilitarCampos('#seccion_rubros');
         deshabilitarCampos('#seccion_responsables');

         if (tipoRegistro.value === 'NATURAL') {
             if (limpiarDatosIncompatibles) {
                 limpiarDatosPorCambioTipoPersonaWizard();
             }

             seccionNatural.classList.remove('hidden');
             seccionRubros.classList.remove('hidden');
             bloqueRubrosWizard?.classList.remove('hidden');

             habilitarCampos('#seccion_natural');
             habilitarCampos('#seccion_rubros');
         }

         if (tipoRegistro.value === 'EMPRESA') {
             if (limpiarDatosIncompatibles) {
                 limpiarDatosPorCambioTipoPersonaWizard();
             }

             seccionEmpresa.classList.remove('hidden');
             seccionResponsables.classList.remove('hidden');
             bloqueResponsablesWizard?.classList.remove('hidden');
             accionResponsablesWizard?.classList.remove('hidden');
             accionResponsablesWizard?.classList.add('is-visible');

             habilitarCampos('#seccion_empresa');
             habilitarCampos('#seccion_responsables');

             setTimeout(() => {
                 mapa.invalidateSize();
             }, 200);
         }
     }

     tipoRegistro.addEventListener('change', () => {
         const tipoNuevo = tipoRegistro.value || '';
         const cambioReal = Boolean(tipoRegistroAnterior && tipoRegistroAnterior !== tipoNuevo);

         cambiarTipoRegistro(cambioReal);
         tipoRegistroAnterior = tipoNuevo;
     });

     cambiarTipoRegistro();
 </script>


 <!-- SCRIPT FORMULARIO POR PASOS
      Controla las burbujas, el avance/retroceso y el borrador local.
      No cambia rutas ni guarda en base de datos hasta presionar "Guardar registro". -->
 <script>
     // Indice del paso visible actualmente.
     let pasoPersonaActual = 0;
     let pasoPersonaRestaurado = 0;

     // Clave usada para guardar el borrador en el navegador.
     const claveBorradorPersona = 'certificador.personas.create.borrador';

     // Elementos principales del wizard.
     const pasosPersona = Array.from(document.querySelectorAll('.wizard-persona-step'));
     const burbujasPersona = Array.from(document.querySelectorAll('.paso-burbuja'));
     const btnPasoAnterior = document.getElementById('btnPasoAnterior');
     const btnPasoSiguiente = document.getElementById('btnPasoSiguiente');
     const btnGuardarBorrador = document.getElementById('btnGuardarBorrador');
     const btnGuardarRegistro = document.getElementById('btnGuardarRegistro');
     const ayudaPersonaWizard = document.getElementById('ayudaPersonaWizard');
     const estadoBorrador = document.getElementById('estadoBorrador');
     const resumenPersonaWizard = document.getElementById('resumenPersonaWizard');
     const formPersonaWizard = document.getElementById('formPersonaWizard');
     const botonesTipoRapido = Array.from(document.querySelectorAll('.tipo-rapido'));
     const tituloPasoWizard = document.getElementById('tituloPasoWizard');
     const subtituloPasoWizard = document.getElementById('subtituloPasoWizard');
     const etiquetaTipoWizard = document.getElementById('etiquetaTipoWizard');

     // Items del panel lateral de progreso.
     const progresoPersonaWizard = {
         tipo: document.getElementById('progresoTipo'),
         generales: document.getElementById('progresoGenerales'),
         especificos: document.getElementById('progresoEspecificos'),
         telefonos: document.getElementById('progresoTelefonos'),
         complementos: document.getElementById('progresoComplementos'),
     };

     // Mensajes de ayuda que aparecen debajo del formulario.
     const ayudasPorPasoPersona = [
         'Seleccione si registrara una persona natural o una empresa.',
         'Complete los datos generales comunes de la persona.',
         'Complete los datos específicos según el tipo registro seleccionado.',
         'Agregue teléfonos y complementos: rubros para natural o responsables para empresa.',
         'Revise los datos principales antes de guardar el registro.'
     ];

     // Titulos del panel principal: cambian con cada burbuja del wizard.
     const titulosPorPasoPersona = [
         {
             titulo: 'Tipo de registro',
             subtitulo: 'Elija si registrara una persona natural o una empresa.'
         },
         {
             titulo: 'Datos generales',
             subtitulo: 'Datos comunes para identificar a la persona.'
         },
         {
             titulo: 'Datos específicos',
             subtitulo: 'Formulario según el tipo seleccionado.'
         },
         {
             titulo: 'Complementos',
             subtitulo: 'Telefonos, rubros o responsables relacionados.'
         },
         {
             titulo: 'Revisión del registro',
             subtitulo: 'Confirme la información antes de guardar.'
         }
     ];

     // Obtiene el valor de un campo por selector CSS.
     function valorPersonaWizard(selector) {
         const campo = document.querySelector(selector);
         return campo ? String(campo.value || '').trim() : '';
     }

     // Obtiene el texto visible de un select.
     function textoSelectPersonaWizard(selector) {
         const campo = document.querySelector(selector);
         if (!campo || campo.selectedIndex < 0) return '';

         return campo.options[campo.selectedIndex]?.text?.trim() || '';
     }

     // Escapa valores antes de ponerlos dentro del resumen HTML.
     function escaparHtmlPersonaWizard(valor) {
         return String(valor ?? '')
             .replaceAll('&', '&amp;')
             .replaceAll('<', '&lt;')
             .replaceAll('>', '&gt;')
             .replaceAll('"', '&quot;')
             .replaceAll("'", '&#039;');
     }

     // Crea una tarjeta pequeña para el resumen final.
     function itemResumenPersonaWizard(titulo, valor) {
         const texto = valor && String(valor).trim() !== '' ? valor : 'No registrado';

         return `
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                    ${escaparHtmlPersonaWizard(titulo)}
                </p>
                <p class="mt-1 text-sm font-semibold text-gray-700">
                    ${escaparHtmlPersonaWizard(texto)}
                </p>
            </div>
         `;
     }

     // Devuelve el tipo actual: NATURAL, EMPRESA o vacio.
     function tipoPersonaWizard() {
         return document.getElementById('tipo_registro')?.value || '';
     }

     // Construye el nombre completo para mostrarlo en revision.
     function nombreNaturalPersonaWizard() {
         return [
             valorPersonaWizard('[name="form_nombres"]'),
             valorPersonaWizard('[name="form_apellido_paterno"]'),
             valorPersonaWizard('[name="form_apellido_materno"]')
         ].filter(Boolean).join(' ');
     }

     // Pinta los botones grandes Persona Natural / Empresa segun el select real.
     function actualizarTipoRapidoPersonaWizard() {
         const tipo = tipoPersonaWizard();

         botonesTipoRapido.forEach(boton => {
             const activo = boton.dataset.tipoRapido === tipo;

             boton.classList.toggle('is-active', activo);
             boton.classList.toggle('border-teal-500', activo);
             boton.classList.toggle('bg-white', activo);
             boton.classList.toggle('text-teal-700', activo);
             boton.classList.toggle('shadow-sm', activo);
             boton.classList.toggle('border-transparent', !activo);
             boton.classList.toggle('bg-transparent', !activo);
             boton.classList.toggle('text-slate-600', !activo);
         });

         if (etiquetaTipoWizard) {
             etiquetaTipoWizard.textContent =
                 tipo === 'EMPRESA' ? 'Empresa' : tipo === 'NATURAL' ? 'Persona Natural' : 'Sin tipo seleccionado';
             etiquetaTipoWizard.className = tipo ?
                 'persona-type-pill bg-teal-50 text-teal-700' :
                 'persona-type-pill';
         }
     }

     // Cambia una fila del panel de progreso entre pendiente y listo.
     function marcarProgresoPersonaWizard(elemento, listo) {
         if (!elemento) return;

         const estado = elemento.querySelector('.progreso-estado');
         const punto = elemento.querySelector('.progreso-punto');
         const panel = elemento.querySelector('.progreso-item-box');

         elemento.classList.toggle('is-complete', listo);

         if (punto) {
             punto.dataset.numero = punto.dataset.numero || punto.textContent;
             punto.textContent = listo ? '✓' : punto.dataset.numero;
         }

         if (panel) {
             panel.classList.toggle('border-emerald-200', listo);
             panel.classList.toggle('bg-emerald-50', listo);
             panel.classList.toggle('border-slate-200', !listo);
             panel.classList.toggle('bg-white', !listo);
         }

         if (!estado) return;

         estado.textContent = listo ? 'Completo' : 'Pendiente';
         estado.className = 'progreso-estado';
     }

     // Actualiza el panel lateral de progreso segun los datos actuales.
     function actualizarProgresoPersonaWizard() {
         const tipo = tipoPersonaWizard();
         const tieneGenerales = Boolean(
             valorPersonaWizard('[name="form_correo"]') &&
             valorPersonaWizard('[name="form_id_territorio"]')
         );

         const tieneNatural = Boolean(
             valorPersonaWizard('[name="form_ci"]') &&
             valorPersonaWizard('[name="form_nombres"]') &&
             valorPersonaWizard('[name="form_apellido_paterno"]')
         );

         const tieneEmpresa = Boolean(
             valorPersonaWizard('[name="form_id_tipo_empresa"]') &&
             valorPersonaWizard('[name="form_razon_social"]') &&
             valorPersonaWizard('[name="form_matricula"]')
         );

         const cantidadTelefonos = document.querySelectorAll('.telefono-agregado').length;
         const cantidadRubros = document.querySelectorAll('.rubro-agregado').length;
         const cantidadResponsables = document.querySelectorAll('.responsable-agregado').length;

         marcarProgresoPersonaWizard(progresoPersonaWizard.tipo, Boolean(tipo));
         marcarProgresoPersonaWizard(progresoPersonaWizard.generales, tieneGenerales);
         marcarProgresoPersonaWizard(
             progresoPersonaWizard.especificos,
             tipo === 'EMPRESA' ? tieneEmpresa : tipo === 'NATURAL' ? tieneNatural : false
         );
         marcarProgresoPersonaWizard(progresoPersonaWizard.telefonos, cantidadTelefonos > 0);
         marcarProgresoPersonaWizard(
             progresoPersonaWizard.complementos,
             tipo === 'EMPRESA' ? cantidadResponsables > 0 : tipo === 'NATURAL' ? cantidadRubros > 0 : false
         );
     }

     // Actualiza las tarjetas del paso de revision.
     function actualizarResumenPersonaWizard() {
         if (!resumenPersonaWizard) return;

         const tipo = tipoPersonaWizard();
         const cantidadTelefonos = document.querySelectorAll('.telefono-agregado').length;
         const cantidadRubros = document.querySelectorAll('.rubro-agregado').length;
         const cantidadResponsables = document.querySelectorAll('.responsable-agregado').length;

         let items = [
             itemResumenPersonaWizard('Tipo de registro', tipo === 'EMPRESA' ? 'Empresa' : 'Persona natural'),
             itemResumenPersonaWizard('Domicilio', valorPersonaWizard('[name="form_domicilio"]')),
             itemResumenPersonaWizard('NIT', valorPersonaWizard('[name="form_nit"]')),
             itemResumenPersonaWizard('Correo', valorPersonaWizard('[name="form_correo"]')),
             itemResumenPersonaWizard('Ubicacion', textoSelectPersonaWizard('[name="form_id_territorio"]')),
             itemResumenPersonaWizard('Estado', textoSelectPersonaWizard('[name="form_estado"]')),
             itemResumenPersonaWizard('Telefonos agregados', cantidadTelefonos)
         ];

         if (tipo === 'NATURAL') {
             items = items.concat([
                 itemResumenPersonaWizard('Nombre completo', nombreNaturalPersonaWizard()),
                 itemResumenPersonaWizard('CI', valorPersonaWizard('[name="form_ci"]')),
                 itemResumenPersonaWizard('Genero', textoSelectPersonaWizard('[name="form_genero"]')),
                 itemResumenPersonaWizard('Ocupacion', valorPersonaWizard('[name="form_ocupacion"]')),
                 itemResumenPersonaWizard('Rubros agregados', cantidadRubros)
             ]);
         }

         if (tipo === 'EMPRESA') {
             items = items.concat([
                 itemResumenPersonaWizard('Tipo de empresa', textoSelectPersonaWizard('[name="form_id_tipo_empresa"]')),
                 itemResumenPersonaWizard('Razon social', valorPersonaWizard('[name="form_razon_social"]')),
                 itemResumenPersonaWizard('Matricula', valorPersonaWizard('[name="form_matricula"]')),
                 itemResumenPersonaWizard('Latitud', valorPersonaWizard('[name="form_latitud"]')),
                 itemResumenPersonaWizard('Longitud', valorPersonaWizard('[name="form_longitud"]')),
                 itemResumenPersonaWizard('Responsables agregados', cantidadResponsables)
             ]);
         }

         resumenPersonaWizard.innerHTML = items.join('');
     }

     // Guarda los campos normales del formulario en localStorage.
     function guardarBorradorPersonaWizard(mostrarMensaje = true) {
         if (!formPersonaWizard) return;

         const datos = {
             paso: pasoPersonaActual,
             campos: {}
         };

         formPersonaWizard.querySelectorAll('input[name], select[name], textarea[name]').forEach(campo => {
             if (campo.type === 'file' || campo.name === '_token') return;

             if (campo.type === 'checkbox' || campo.type === 'radio') {
                 datos.campos[campo.name] = campo.checked;
                 return;
             }

             datos.campos[campo.name] = campo.value;
         });

         localStorage.setItem(claveBorradorPersona, JSON.stringify(datos));

         if (estadoBorrador) {
             const hora = new Date().toLocaleTimeString([], {
                 hour: '2-digit',
                 minute: '2-digit'
             });

             estadoBorrador.textContent = `Guardado ${hora}`;
             estadoBorrador.className =
                 'persona-save-pill bg-emerald-50 text-emerald-700';
         }

         if (mostrarMensaje) {
             console.info('Borrador de persona guardado en este navegador.');
         }
     }

     // Restaura el borrador guardado localmente cuando se vuelve a abrir el formulario.
     function restaurarBorradorPersonaWizard() {
         const borrador = localStorage.getItem(claveBorradorPersona);
         if (!borrador || !formPersonaWizard) return;

         try {
             const datos = JSON.parse(borrador);

             pasoPersonaRestaurado = Number(datos.paso || 0);

             Object.entries(datos.campos || {}).forEach(([name, value]) => {
                 const campo = formPersonaWizard.elements[name];
                 if (!campo || campo.type === 'file') return;

                 if (campo.type === 'checkbox' || campo.type === 'radio') {
                     campo.checked = Boolean(value);
                     return;
                 }

             campo.value = value ?? '';
             });

             if (estadoBorrador) {
                 estadoBorrador.textContent = 'Borrador recuperado';
                 estadoBorrador.className =
                     'persona-save-pill bg-blue-50 text-blue-700';
             }

             if (typeof cambiarTipoRegistro === 'function') {
                 cambiarTipoRegistro();
             }
         } catch (error) {
             console.warn('No se pudo restaurar el borrador de persona.', error);
         }
     }

     // Pinta burbujas activas, completadas y pendientes.
     function actualizarBurbujasPersonaWizard() {
         burbujasPersona.forEach((burbuja, indice) => {
             const circulo = burbuja.querySelector('.paso-circulo');
             const activo = indice === pasoPersonaActual;
             const completado = indice < pasoPersonaActual;

             burbuja.classList.toggle('is-active', activo);
             burbuja.classList.toggle('is-completed', completado);
             burbuja.classList.toggle('text-teal-700', activo);
             burbuja.classList.toggle('text-emerald-700', completado);
             burbuja.classList.toggle('text-gray-500', !activo && !completado);

             if (!circulo) return;

             circulo.textContent = completado ? '✓' : String(indice + 1);
             circulo.classList.toggle('border-teal-600', activo);
             circulo.classList.toggle('bg-teal-600', activo);
             circulo.classList.toggle('border-emerald-600', completado);
             circulo.classList.toggle('bg-emerald-600', completado);
             circulo.classList.toggle('border-slate-200', !activo && !completado);
             circulo.classList.toggle('bg-gray-100', !activo && !completado);
             circulo.classList.toggle('text-white', activo || completado);
             circulo.classList.toggle('text-gray-500', !activo && !completado);
         });
     }

     // Valida lo minimo antes de permitir avanzar al siguiente paso.
     function puedeAvanzarPersonaWizard() {
         if (pasoPersonaActual === 0 && tipoPersonaWizard() === '') {
             alert('Seleccione el tipo de registro para continuar.');
             return false;
         }

         return true;
     }

     // Cambia el encabezado del panel para que el usuario sepa exactamente donde esta.
     function actualizarEncabezadoPasoPersonaWizard() {
         const tipo = tipoPersonaWizard();
         const base = titulosPorPasoPersona[pasoPersonaActual] || titulosPorPasoPersona[0];

         let titulo = base.titulo;
         let subtitulo = base.subtitulo;

         if (pasoPersonaActual === 2) {
             titulo = tipo === 'EMPRESA' ? 'Datos especificos - Empresa' : 'Datos especificos - Persona Natural';
             subtitulo = tipo === 'EMPRESA' ?
                 'Complete tipo de empresa, razon social, matricula y ubicacion.' :
                 'Complete CI, nombres, apellidos, nacimiento, genero y ocupacion.';
         }

         if (pasoPersonaActual === 3) {
             titulo = tipo === 'EMPRESA' ? 'Telefonos y Responsables' : 'Telefonos y Rubros';
             subtitulo = tipo === 'EMPRESA' ?
                 'Agregue telefonos de contacto y responsables de la empresa.' :
                 'Agregue telefonos de contacto y rubros de la persona natural.';
         }

         if (tituloPasoWizard) tituloPasoWizard.textContent = titulo;
         if (subtituloPasoWizard) subtituloPasoWizard.textContent = subtitulo;
     }

     // Muestra un paso y oculta los demas.
     function mostrarPasoPersonaWizard(indice) {
         if (indice < 0 || indice >= pasosPersona.length) return;

         pasoPersonaActual = indice;

         pasosPersona.forEach((paso, posicion) => {
             paso.classList.toggle('hidden', posicion !== pasoPersonaActual);
         });

         if (ayudaPersonaWizard) {
             ayudaPersonaWizard.textContent = ayudasPorPasoPersona[pasoPersonaActual] || '';
         }

         btnPasoAnterior?.classList.toggle('hidden', pasoPersonaActual === 0);
         btnPasoSiguiente?.classList.toggle('hidden', pasoPersonaActual === pasosPersona.length - 1);
         btnGuardarRegistro?.classList.toggle('hidden', pasoPersonaActual !== pasosPersona.length - 1);

         actualizarBurbujasPersonaWizard();
         actualizarTipoRapidoPersonaWizard();
         actualizarEncabezadoPasoPersonaWizard();
         actualizarProgresoPersonaWizard();

         if (pasoPersonaActual === pasosPersona.length - 1) {
             actualizarResumenPersonaWizard();
         }

         if (tipoPersonaWizard() === 'EMPRESA' && pasoPersonaActual === 2 && typeof mapa !== 'undefined') {
             setTimeout(() => {
                 mapa.invalidateSize();
             }, 200);
         }

         guardarBorradorPersonaWizard(false);
     }

     // Boton para volver al paso anterior sin borrar lo escrito.
     btnPasoAnterior?.addEventListener('click', () => {
         mostrarPasoPersonaWizard(pasoPersonaActual - 1);
     });

     // Boton para avanzar al siguiente paso y guardar borrador local.
     btnPasoSiguiente?.addEventListener('click', () => {
         if (!puedeAvanzarPersonaWizard()) return;

         guardarBorradorPersonaWizard(false);
         mostrarPasoPersonaWizard(pasoPersonaActual + 1);
     });

     // Boton visible para forzar guardado de borrador local.
     btnGuardarBorrador?.addEventListener('click', () => {
         guardarBorradorPersonaWizard(true);
         actualizarProgresoPersonaWizard();
     });

     // Botones tipo tarjeta: actualizan el select real que ya usa el controlador.
     botonesTipoRapido.forEach(boton => {
         boton.addEventListener('click', () => {
             const tipo = boton.dataset.tipoRapido || '';

             if (!tipoRegistro) return;

             tipoRegistro.value = tipo;
             tipoRegistro.dispatchEvent(new Event('change', {
                 bubbles: true
             }));
         });
     });

     // Permite ir a una burbuja anterior o futura si ya se eligio tipo.
     burbujasPersona.forEach(burbuja => {
         burbuja.addEventListener('click', () => {
             const destino = Number(burbuja.dataset.wizardIr);

             if (destino > 0 && tipoPersonaWizard() === '') {
                 alert('Seleccione el tipo de registro para continuar.');
                 return;
             }

             mostrarPasoPersonaWizard(destino);
         });
     });

     // Si cambia el tipo, se reutiliza tu logica actual y se vuelve al primer paso.
     tipoRegistro?.addEventListener('change', () => {
         if (typeof cambiarTipoRegistro === 'function') {
             cambiarTipoRegistro();
         }

         actualizarTipoRapidoPersonaWizard();
         actualizarProgresoPersonaWizard();
         mostrarPasoPersonaWizard(0);
     });

     // Guarda automaticamente los campos simples mientras se escribe.
     formPersonaWizard?.addEventListener('input', (evento) => {
         limpiarErrorSiCampoCorregido(evento.target);
         guardarBorradorPersonaWizard(false);
         actualizarProgresoPersonaWizard();
     });
     formPersonaWizard?.addEventListener('change', (evento) => {
         limpiarErrorSiCampoCorregido(evento.target);
         guardarBorradorPersonaWizard(false);
         actualizarTipoRapidoPersonaWizard();
         actualizarProgresoPersonaWizard();
     });

     // Refresca el progreso despues de botones que agregan o quitan listas dinamicas.
     document.addEventListener('click', (evento) => {
         const accionDinamica = evento.target.closest(
             '[onclick*="agregarTelefonoPersona"], [onclick*="quitarTelefonoPersona"], [onclick*="agregarRubroPersona"], [onclick*="quitarRubroPersona"], [onclick*="agregarNuevoResponsableTemporal"], [onclick*="quitarResponsableEmpresa"]'
         );

         if (!accionDinamica) return;

         setTimeout(() => {
             actualizarProgresoPersonaWizard();
             guardarBorradorPersonaWizard(false);
         }, 0);
     });

     // Inicializa el wizard respetando el formulario existente.
     restaurarBorradorPersonaWizard();
     tipoRegistroAnterior = tipoPersonaWizard();
     mostrarPasoPersonaWizard(tipoPersonaWizard() ? pasoPersonaRestaurado : 0);
 </script>

 <!-- SCRIPT RESPONSABLES -->
 <script>
     let indiceResponsable = 0;
     let telefonosResponsableTemporal = [];
     let rubrosResponsableTemporal = [];
     let pdfTemporalResponsableModal = null;

     function abrirModalResponsable() {
         document.getElementById('modalNuevoResponsable').classList.remove('hidden');
         prepararModoPersonaNueva();
     }

     function cerrarModalResponsable() {
         document.getElementById('modalNuevoResponsable').classList.add('hidden');
     }

     function prepararModoPersonaNueva() {
         desbloquearDatosPersonaResponsable();

         document.getElementById('nuevo_id_persona_existente').value = '';

         document.getElementById('formTelefonosResponsable').classList.remove('hidden');
         document.getElementById('formRubrosResponsable').classList.remove('hidden');

         document.getElementById('textoModoTelefonosResponsable').innerText =
             'Persona nueva: puede agregar uno o varios teléfonos.';

         document.getElementById('textoModoRubrosResponsable').innerText =
             'Persona nueva: puede agregar uno o varios rubros.';
     }

     function prepararModoPersonaExistente() {
         bloquearDatosPersonaResponsable();

         document.getElementById('formTelefonosResponsable').classList.add('hidden');
         document.getElementById('formRubrosResponsable').classList.add('hidden');

         document.getElementById('textoModoTelefonosResponsable').innerText =
             'Persona existente: los teléfonos solo se muestran como información.';

         document.getElementById('textoModoRubrosResponsable').innerText =
             'Persona existente: los rubros solo se muestran como información.';
     }

     function bloquearDatosPersonaResponsable() {
         const ids = [
             'nuevo_domicilio',
             'nuevo_nit',
             'nuevo_correo',
             'nuevo_id_territorio',
             'nuevo_nombres',
             'nuevo_apellido_paterno',
             'nuevo_apellido_materno',
             'nuevo_apellido_casado',
             'nuevo_ci',
             'nuevo_complemento',
             'nuevo_expedido',
             'nuevo_genero',
             'nuevo_ocupacion',
         ];

         ids.forEach(id => {
             const campo = document.getElementById(id);
             if (campo) campo.disabled = true;
         });

         const fecha = document.querySelector('[name="nuevo_fecha_nacimiento"]');
         if (fecha) fecha.disabled = true;
     }

     function desbloquearDatosPersonaResponsable() {
         const ids = [
             'nuevo_domicilio',
             'nuevo_nit',
             'nuevo_correo',
             'nuevo_id_territorio',
             'nuevo_nombres',
             'nuevo_apellido_paterno',
             'nuevo_apellido_materno',
             'nuevo_apellido_casado',
             'nuevo_ci',
             'nuevo_complemento',
             'nuevo_expedido',
             'nuevo_genero',
             'nuevo_ocupacion',
         ];

         ids.forEach(id => {
             const campo = document.getElementById(id);
             if (campo) campo.disabled = false;
         });

         const fecha = document.querySelector('[name="nuevo_fecha_nacimiento"]');
         if (fecha) fecha.disabled = false;
     }

     function cargarPersonaResponsable() {
         const select = document.getElementById('modal_id_persona_responsable');
         const valorSeleccionado = select.value;
         const opcion = select.querySelector(`option[value="${valorSeleccionado}"]`);

         limpiarDatosPersonaResponsable();
         limpiarListasResponsableModal();

         if (!opcion || !opcion.value) {
             prepararModoPersonaNueva();
             return;
         }

         prepararModoPersonaExistente();

         document.getElementById('nuevo_id_persona_existente').value = opcion.value;

         document.getElementById('nuevo_domicilio').value = opcion.dataset.domicilio || '';
         document.getElementById('nuevo_nit').value = opcion.dataset.nit || '';
         document.getElementById('nuevo_correo').value = opcion.dataset.correo || '';
         document.getElementById('nuevo_id_territorio').value = opcion.dataset.territorio || '';

         document.getElementById('nuevo_nombres').value = opcion.dataset.nombres || '';
         document.getElementById('nuevo_apellido_paterno').value = opcion.dataset.paterno || '';
         document.getElementById('nuevo_apellido_materno').value = opcion.dataset.materno || '';
         document.getElementById('nuevo_apellido_casado').value = opcion.dataset.casado || '';
         document.getElementById('nuevo_ci').value = opcion.dataset.ci || '';
         document.getElementById('nuevo_complemento').value = opcion.dataset.complemento || '';
         document.getElementById('nuevo_expedido').value = opcion.dataset.expedido || '';
         document.getElementById('nuevo_genero').value = String(opcion.dataset.genero ?? '');
         document.getElementById('nuevo_ocupacion').value = opcion.dataset.ocupacion || '';

         const fecha = opcion.dataset.fecha ?
             opcion.dataset.fecha.substring(0, 10) :
             '';

         const fechaNacimiento = document.querySelector('[name="nuevo_fecha_nacimiento"]');

         if (fechaNacimiento) {

             fechaNacimiento.value = fecha;

             fechaNacimiento.setAttribute('value', fecha);

             fechaNacimiento.dispatchEvent(new InputEvent('input', {
                 bubbles: true
             }));

             fechaNacimiento.dispatchEvent(new Event('change', {
                 bubbles: true
             }));

             fechaNacimiento.dispatchEvent(new Event('blur', {
                 bubbles: true
             }));
         }

         mostrarTelefonosExistentes(opcion.dataset.telefonos);
         mostrarRubrosExistentes(opcion.dataset.rubros);
     }

     function agregarTelefonoResponsableModal() {
         const numeroInput = document.getElementById('nuevo_telefono');
         const tipoInput = document.getElementById('nuevo_tipo_telefono');

         const numero = numeroInput.value.trim();
         const tipo = tipoInput.value;

         if (numero === '') {
             alert('Ingrese el número de teléfono.');
             return;
         }

         telefonosResponsableTemporal.push({
             numero: numero,
             tipo: tipo
         });

         renderTelefonosResponsable(false);

         numeroInput.value = '';
         tipoInput.value = 'CELULAR';
     }

     function renderTelefonosResponsable(soloLectura = false) {
         const lista = document.getElementById('listaTelefonosResponsableModal');
         lista.innerHTML = '';

         if (telefonosResponsableTemporal.length === 0) {
             lista.innerHTML = `
                <span id="mensajeSinTelefonosResponsableModal" class="text-sm text-gray-500">
                    No tiene teléfonos registrados.
                </span>
            `;
             return;
         }

         telefonosResponsableTemporal.forEach((telefono, index) => {
             const item = document.createElement('div');

             item.className =
                 'inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-sm';

             item.innerHTML = `
                <span class="font-medium text-gray-700">${telefono.numero}</span>

                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                    ${telefono.tipo}
                </span>

                ${soloLectura ? '' : `
                                          <button type="button"
                                              onclick="quitarTelefonoResponsableModal(${index})"
                                              class="text-red-500 hover:text-red-700 text-base font-bold leading-none">
                                              ×
                                          </button>
                                      `}
            `;

             lista.appendChild(item);
         });
     }

     function quitarTelefonoResponsableModal(index) {
         telefonosResponsableTemporal.splice(index, 1);
         renderTelefonosResponsable(false);
     }

     function mostrarTelefonosExistentes(telefonosJson) {
         let telefonos = [];

         try {
             telefonos = JSON.parse(telefonosJson || '[]');
         } catch (e) {
             telefonos = [];
         }

         telefonosResponsableTemporal = telefonos.map(telefono => ({
             numero: telefono.numero ?? '',
             tipo: telefono.estado ?? telefono.tipo ?? 'CELULAR',
         }));

         renderTelefonosResponsable(true);
     }

     function agregarRubroResponsableModal() {
         const nombreInput = document.getElementById('nuevo_rubro');
         const estadoInput = document.getElementById('nuevo_estado_rubro');

         const nombre = nombreInput.value.trim();
         const estado = estadoInput.value;

         if (nombre === '') {
             alert('Ingrese el nombre del rubro.');
             return;
         }

         rubrosResponsableTemporal.push({
             nombre: nombre,
             estado: estado
         });

         renderRubrosResponsable(false);

         nombreInput.value = '';
         estadoInput.value = 'ACTIVO';
     }

     function renderRubrosResponsable(soloLectura = false) {
         const lista = document.getElementById('listaRubrosResponsableModal');
         lista.innerHTML = '';

         if (rubrosResponsableTemporal.length === 0) {
             lista.innerHTML = `
                <span id="mensajeSinRubrosResponsableModal" class="text-sm text-gray-500">
                    No tiene rubros registrados.
                </span>
            `;
             return;
         }

         rubrosResponsableTemporal.forEach((rubro, index) => {
             const textoEstado = rubro.estado === 'ACTIVO' ? 'Activo' : 'Inactivo';

             const item = document.createElement('div');

             // El chip del rubro del responsable tambien permite varias lineas.
             item.className =
                 'inline-flex max-w-full flex-wrap items-center gap-2 rounded-2xl border border-gray-200 bg-white px-3 py-2 text-sm shadow-sm';

             item.innerHTML = `
                <span class="min-w-0 max-w-full whitespace-normal break-words font-medium leading-snug text-gray-700">
                    ${rubro.nombre}
                </span>

                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                    ${rubro.estado === 'ACTIVO'
                        ? 'bg-green-100 text-green-700'
                        : 'bg-red-100 text-red-700'}">
                    ${textoEstado}
                </span>

                ${soloLectura ? '' : `
                                          <button type="button"
                                              onclick="quitarRubroResponsableModal(${index})"
                                              class="text-red-500 hover:text-red-700 text-base font-bold leading-none">
                                              ×
                                          </button>
                                      `}
            `;

             lista.appendChild(item);
         });
     }

     function quitarRubroResponsableModal(index) {
         rubrosResponsableTemporal.splice(index, 1);
         renderRubrosResponsable(false);
     }

     function mostrarRubrosExistentes(rubrosJson) {
         let rubros = [];

         try {
             rubros = JSON.parse(rubrosJson || '[]');
         } catch (e) {
             rubros = [];
         }

         rubrosResponsableTemporal = rubros.map(rubro => ({
             nombre: rubro.nombre ?? '',
             // Mantiene compatibilidad con rubros viejos que pudieron llegar como 0/1.
             estado: rubro.estado === '0' || rubro.estado === 'INACTIVO' ? 'INACTIVO' : 'ACTIVO',
         }));

         renderRubrosResponsable(true);
     }

     function agregarNuevoResponsableTemporal() {
         const idPersonaExistente = document.getElementById('nuevo_id_persona_existente').value;

         const domicilio = document.getElementById('nuevo_domicilio').value.trim();
         const nit = document.getElementById('nuevo_nit').value.trim();
         const correo = document.getElementById('nuevo_correo').value.trim();
         const idTerritorio = document.getElementById('nuevo_id_territorio').value;

         const nombres = document.getElementById('nuevo_nombres').value.trim();
         const paterno = document.getElementById('nuevo_apellido_paterno').value.trim();
         const materno = document.getElementById('nuevo_apellido_materno').value.trim();
         const casado = document.getElementById('nuevo_apellido_casado').value.trim();
         const ci = document.getElementById('nuevo_ci').value.trim();
         const complemento = document.getElementById('nuevo_complemento').value.trim();
         const expedido = document.getElementById('nuevo_expedido').value.trim();
         const fechaNacimiento = document.querySelector('[name="nuevo_fecha_nacimiento"]')?.value ?? '';
         const genero = document.getElementById('nuevo_genero').value;
         const ocupacion = document.getElementById('nuevo_ocupacion').value.trim();

         const idRol = document.getElementById('nuevo_id_rol').value;
         const rolNombre = textoSelectPorValorPersonaWizard('#nuevo_id_rol', idRol);
         const respaldoInput = document.getElementById('nuevo_url_respaldo');
         const archivoRespaldo = respaldoInput?.files?.[0] ?? null;
         const fechaRegistro = document.querySelector('[name="nuevo_fecha_registro"]')?.value ?? '';
         const fechaBaja = document.querySelector('[name="nuevo_fecha_baja"]')?.value ?? '';
         const estadoResponsable = document.getElementById('nuevo_estado_responsable').value;

         if (idPersonaExistente === '') {
             if (nombres === '' || paterno === '' || ci === '') {
                 alert('Para persona nueva debe ingresar nombres, apellido paterno y CI.');
                 return;
             }

             if (correo === '') {
                 alert('Ingrese el correo del responsable.');
                 return;
             }

             if (idTerritorio === '') {
                 alert('Seleccione el territorio del responsable.');
                 return;
             }
         }

         if (idRol === '') {
             alert('Seleccione el rol o cargo del responsable.');
             return;
         }

         const respaldoEsPdf = !archivoRespaldo ||
             archivoRespaldo.type === 'application/pdf' ||
             archivoRespaldo.name.toLowerCase().endsWith('.pdf');

         if (!respaldoEsPdf) {
             mostrarErrorRespaldoResponsableModal('El respaldo del responsable debe ser un PDF.');
             return;
         } else {
             limpiarErrorRespaldoResponsableModal();
         }

         const lista = document.getElementById('listaResponsablesEmpresa');
         document.getElementById('mensajeSinResponsables')?.remove();

         const nombreCompleto = `${nombres} ${paterno} ${materno}`.trim();
         const tipoResponsable = idPersonaExistente ? 'EXISTENTE' : 'NUEVO';

         const item = document.createElement('div');

         item.className =
             'responsable-agregado inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white border border-cyan-200 shadow-sm text-sm';

         let hiddenTelefonos = '';
         let hiddenRubros = '';

         telefonosResponsableTemporal.forEach((telefono, index) => {
             hiddenTelefonos += `
                <input type="hidden" name="responsables[${indiceResponsable}][telefonos][${index}][numero]" value="${telefono.numero}">
                <input type="hidden" name="responsables[${indiceResponsable}][telefonos][${index}][tipo]" value="${telefono.tipo}">
            `;
         });

         rubrosResponsableTemporal.forEach((rubro, index) => {
             hiddenRubros += `
                <input type="hidden" name="responsables[${indiceResponsable}][rubros][${index}][nombre]" value="${rubro.nombre}">
                <input type="hidden" name="responsables[${indiceResponsable}][rubros][${index}][estado]" value="${rubro.estado}">
            `;
         });

         item.innerHTML = `
            <span class="font-medium text-gray-700">${nombreCompleto}</span>

            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                ${tipoResponsable === 'EXISTENTE'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-green-100 text-green-700'}">
                ${tipoResponsable}
            </span>

             <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">
                 ${rolNombre}
             </span>

            ${archivoRespaldo ? `
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                    PDF
                </span>
            ` : ''}

            <button type="button"
                onclick="quitarResponsableEmpresa(this)"
                class="text-red-500 hover:text-red-700 text-base font-bold leading-none">
                ×
            </button>

            <input type="hidden" name="responsables[${indiceResponsable}][tipo]" value="${tipoResponsable}">
            <input type="hidden" name="responsables[${indiceResponsable}][id_persona]" value="${idPersonaExistente}">

            <input type="hidden" name="responsables[${indiceResponsable}][domicilio]" value="${domicilio}">
            <input type="hidden" name="responsables[${indiceResponsable}][nit]" value="${nit}">
            <input type="hidden" name="responsables[${indiceResponsable}][correo]" value="${correo}">
            <input type="hidden" name="responsables[${indiceResponsable}][id_territorio]" value="${idTerritorio}">

            <input type="hidden" name="responsables[${indiceResponsable}][nombres]" value="${nombres}">
            <input type="hidden" name="responsables[${indiceResponsable}][apellido_paterno]" value="${paterno}">
            <input type="hidden" name="responsables[${indiceResponsable}][apellido_materno]" value="${materno}">
            <input type="hidden" name="responsables[${indiceResponsable}][apellido_casado]" value="${casado}">
            <input type="hidden" name="responsables[${indiceResponsable}][ci]" value="${ci}">
            <input type="hidden" name="responsables[${indiceResponsable}][complemento]" value="${complemento}">
            <input type="hidden" name="responsables[${indiceResponsable}][expedido]" value="${expedido}">
            <input type="hidden" name="responsables[${indiceResponsable}][fecha_nacimiento]" value="${fechaNacimiento}">
            <input type="hidden" name="responsables[${indiceResponsable}][genero]" value="${genero}">
            <input type="hidden" name="responsables[${indiceResponsable}][ocupacion]" value="${ocupacion}">

            <input type="hidden" name="responsables[${indiceResponsable}][id_rol]" value="${idRol}">
            <input type="hidden" name="responsables[${indiceResponsable}][fecha_registro]" value="${fechaRegistro}">
            <input type="hidden" name="responsables[${indiceResponsable}][fecha_baja]" value="${fechaBaja}">
            <input type="hidden" name="responsables[${indiceResponsable}][estado]" value="${estadoResponsable}">

            ${hiddenTelefonos}
            ${hiddenRubros}
        `;

         // Adjunta el PDF al responsable agregado para enviarlo junto al formulario final.
         if (archivoRespaldo) {
             if (typeof DataTransfer === 'undefined') {
                 alert('Su navegador no pudo adjuntar el PDF. Intente nuevamente.');
                 return;
             }

             const archivoTemporal = new DataTransfer();
             archivoTemporal.items.add(archivoRespaldo);

             const inputArchivo = document.createElement('input');
             inputArchivo.type = 'file';
             inputArchivo.name = `responsables[${indiceResponsable}][archivo_respaldo]`;
             inputArchivo.className = 'hidden';
             inputArchivo.files = archivoTemporal.files;

             item.appendChild(inputArchivo);
         }

         lista.appendChild(item);

         indiceResponsable++;

         cerrarModalResponsable();
         limpiarModalResponsable();
     }

     function quitarResponsableEmpresa(boton) {
         boton.closest('.responsable-agregado').remove();

         const lista = document.getElementById('listaResponsablesEmpresa');

         if (lista.children.length === 0) {
             lista.innerHTML = `
                <span id="mensajeSinResponsables" class="text-sm text-gray-500">
                    Todavía no se agregaron responsables.
                </span>
            `;
         }
     }

     function limpiarFechaWire(nombre) {
         const campo = document.querySelector(`[name="${nombre}"]`);

         if (!campo) return;

         campo.value = '';
         campo.setAttribute('value', '');

         campo.dispatchEvent(new InputEvent('input', {
             bubbles: true
         }));
         campo.dispatchEvent(new Event('change', {
             bubbles: true
         }));
         campo.dispatchEvent(new Event('blur', {
             bubbles: true
         }));
     }

     function limpiarDatosPersonaResponsable() {
         const ids = [
             'nuevo_id_persona_existente',
             'nuevo_domicilio',
             'nuevo_nit',
             'nuevo_correo',
             'nuevo_id_territorio',
             'nuevo_nombres',
             'nuevo_apellido_paterno',
             'nuevo_apellido_materno',
             'nuevo_apellido_casado',
             'nuevo_ci',
             'nuevo_complemento',
             'nuevo_expedido',
             'nuevo_genero',
             'nuevo_ocupacion',
         ];

         ids.forEach(id => {
             const campo = document.getElementById(id);
             if (campo) campo.value = '';
         });

         const fecha = document.querySelector('[name="nuevo_fecha_nacimiento"]');
         if (fecha) fecha.value = '';
     }

     function limpiarListasResponsableModal() {
         telefonosResponsableTemporal = [];
         rubrosResponsableTemporal = [];

         renderTelefonosResponsable(false);
         renderRubrosResponsable(false);
     }

     function limpiarErrorRespaldoResponsableModal() {
         document.querySelector('[data-error-responsable="nuevo_url_respaldo"]')?.remove();
     }

     function mostrarErrorRespaldoResponsableModal(mensaje) {
         const wrapper = document.querySelector('[data-error-wrapper="nuevo_url_respaldo"]');
         const error = document.createElement('p');

         limpiarErrorRespaldoResponsableModal();

         error.dataset.errorResponsable = 'nuevo_url_respaldo';
         error.className = 'mt-2 text-sm text-red-600';
         error.textContent = mensaje;

         wrapper?.insertAdjacentElement('afterend', error);
     }

     function nombreArchivoResponsableDesdeUrl(url) {
         const texto = String(url || '').split('?')[0].split('#')[0];
         const nombre = texto.split('/').pop();

         return nombre ? decodeURIComponent(nombre) : 'PDF guardado';
     }

     function actualizarVistaRespaldoResponsableModal(nombre, estado, url = '', permiteQuitar = false) {
         const nombrePdf = document.getElementById('responsableModalPdfNombre');
         const estadoPdf = document.getElementById('responsableModalPdfEstado');
         const botonVer = document.getElementById('btnVerRespaldoResponsableModal');
         const botonQuitar = document.getElementById('btnQuitarRespaldoResponsableModal');

         if (nombrePdf) nombrePdf.textContent = nombre || 'Sin PDF seleccionado';
         if (estadoPdf) estadoPdf.textContent = estado || 'Seleccione un respaldo PDF si corresponde.';

         if (botonVer) {
             botonVer.dataset.pdfUrl = url || '';
             botonVer.disabled = !url;
         }

         if (botonQuitar) {
             botonQuitar.disabled = !permiteQuitar;
         }
     }

     function liberarPdfTemporalResponsableModal() {
         if (pdfTemporalResponsableModal) {
             URL.revokeObjectURL(pdfTemporalResponsableModal);
             pdfTemporalResponsableModal = null;
         }
     }

     function limpiarRespaldoResponsableModal(mensaje = 'Seleccione un respaldo PDF si corresponde.') {
         const inputPdf = document.getElementById('nuevo_url_respaldo');

         if (inputPdf) inputPdf.value = '';

         liberarPdfTemporalResponsableModal();
         actualizarVistaRespaldoResponsableModal('Sin PDF seleccionado', mensaje, '', false);
         limpiarErrorRespaldoResponsableModal();
     }

     function limpiarModalResponsable() {
         limpiarDatosPersonaResponsable();
         limpiarListasResponsableModal();

         const ids = [
             'nuevo_telefono',
             'nuevo_tipo_telefono',
             'nuevo_rubro',
             'nuevo_estado_rubro',
             'nuevo_id_rol',
             'nuevo_url_respaldo',
             'nuevo_estado_responsable',
         ];

         ids.forEach(id => {
             const campo = document.getElementById(id);
             if (campo) campo.value = '';
         });

         limpiarFechaWire('nuevo_fecha_registro');
         limpiarFechaWire('nuevo_fecha_baja');

         const tipoTelefono = document.getElementById('nuevo_tipo_telefono');
         if (tipoTelefono) tipoTelefono.value = 'CELULAR';

         const estadoRubro = document.getElementById('nuevo_estado_rubro');
         if (estadoRubro) estadoRubro.value = 'ACTIVO';

         const estadoResponsable = document.getElementById('nuevo_estado_responsable');
         if (estadoResponsable) estadoResponsable.value = 'ACTIVO';

         // Limpia el control compacto del PDF cuando se cierra o se agrega el responsable.
         limpiarRespaldoResponsableModal();

         const select = document.getElementById('modal_id_persona_responsable');

         if (select?.tomselect) {
             select.tomselect.clear();
         } else if (select) {
             select.value = '';
         }

         prepararModoPersonaNueva();
     }
 </script>





 <!-- TomSelect PARA BUSCAR PERSONAS DENTRO DEL MODAL -->
 <script>
     new TomSelect("#modal_id_persona_responsable", {
         create: false,
         placeholder: "Buscar por CI o nombre completo",
         allowEmptyOption: true,
         maxOptions: 500,

         onChange: function() {
             cargarPersonaResponsable();
         }
     });
 </script>




<!-- Control PDF compacto del responsable -->
 <script>
     document.getElementById('nuevo_url_respaldo').addEventListener('change', function(e) {

         const file = e.target.files[0];

         if (!file) return;

         // Valida PDF antes de habilitar la accion Ver.
         const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
         if (!esPdf) {
             mostrarErrorRespaldoResponsableModal('Solo se permiten archivos PDF.');
             e.target.value = '';
             return;
         }

         limpiarErrorRespaldoResponsableModal();
         liberarPdfTemporalResponsableModal();

         pdfTemporalResponsableModal = URL.createObjectURL(file);

         actualizarVistaRespaldoResponsableModal(
             file.name,
             'PDF seleccionado para guardar como respaldo.',
             pdfTemporalResponsableModal,
             true
         );
     });

     document.getElementById('btnVerRespaldoResponsableModal')?.addEventListener('click', function() {
         const url = this.dataset.pdfUrl;

         if (url) {
             window.open(url, '_blank');
         }
     });

     document.getElementById('btnQuitarRespaldoResponsableModal')?.addEventListener('click', function() {
         limpiarRespaldoResponsableModal('El respaldo fue quitado. Guarde el responsable para confirmar.');
     });
 </script>
