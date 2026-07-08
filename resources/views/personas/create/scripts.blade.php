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

     function buscarCampoPersonaWizard(idCampo) {
         return document.getElementById(idCampo) || document.querySelector(`[name="${idCampo}"]`);
     }

     // Quita el mensaje bajo el campo, igual que al limpiar un error de validacion Laravel.
     function limpiarErrorCampoResponsable(idCampo) {
         const campo = buscarCampoPersonaWizard(idCampo);
         const error = document.querySelector(`[data-error-responsable="${idCampo}"]`);

         error?.remove();
         campo?.removeAttribute('aria-invalid');
     }

     // Busca el contenedor real del campo para no meter el mensaje dentro del input.
     // WireUI renderiza cada campo con form-wrapper="id_del_campo"; ahi es donde Laravel/WireUI
     // muestra sus errores, debajo del control y sin deformar la caja del input.
     function ubicarReferenciaErrorCampoResponsable(idCampo, campo) {
         const wrapperManual = document.querySelector(`[data-error-wrapper="${idCampo}"]`);

         if (wrapperManual) {
             return wrapperManual;
         }

         const wrapperWireUi = Array.from(document.querySelectorAll('[form-wrapper]'))
             .find(wrapper => wrapper.getAttribute('form-wrapper') === idCampo);

         if (wrapperWireUi) {
             return wrapperWireUi.querySelector('[name="form.wrapper.container"]') || wrapperWireUi;
         }

         if (campo.tomselect?.wrapper) {
             return campo.tomselect.wrapper;
         }

         return campo;
     }

     // Muestra el error debajo del input/select usando el mismo criterio visual de Laravel.
     function mostrarErrorCampoResponsable(idCampo, mensaje) {
         const campo = buscarCampoPersonaWizard(idCampo);

         if (!campo) return null;

         limpiarErrorCampoResponsable(idCampo);

         const referencia = ubicarReferenciaErrorCampoResponsable(idCampo, campo);
         const error = document.createElement('p');

         campo.setAttribute('aria-invalid', 'true');
         error.dataset.errorResponsable = idCampo;
         error.className = 'mt-2 text-sm text-red-600';
         error.textContent = mensaje;

         referencia.insertAdjacentElement('afterend', error);

         return campo;
     }

     // Valida un campo requerido del modal y devuelve el primer campo con error.
     function validarCampoResponsable(idCampo, valor, mensaje, primerCampoError = null) {
         limpiarErrorCampoResponsable(idCampo);

         if (String(valor || '').trim() !== '') {
             return primerCampoError;
         }

         return primerCampoError || mostrarErrorCampoResponsable(idCampo, mensaje);
     }

     // Limpia todos los errores inline del modal de responsable.
     function limpiarErroresResponsableModal() {
         document.querySelectorAll('[data-error-responsable]').forEach(error => {
             limpiarErrorCampoResponsable(error.dataset.errorResponsable);
         });
     }

     // Reutiliza el mismo estilo de error bajo el campo para listas dinamicas del formulario.
     function mostrarErrorCampoPersonaWizard(idCampo, mensaje) {
         return mostrarErrorCampoResponsable(idCampo, mensaje);
     }

     function limpiarErrorCampoPersonaWizard(idCampo) {
         limpiarErrorCampoResponsable(idCampo);
     }

     // El tipo de registro se elige con tarjetas visibles; el select real esta oculto.
     // Por eso el mensaje se coloca debajo de las tarjetas, alineado al mismo ancho visual.
     function mostrarErrorTipoRegistroPersonaWizard(mensaje) {
         limpiarErrorCampoPersonaWizard('tipo_registro');

         const contenedorTipos = document.querySelector('.persona-type-tabs');
         const error = document.createElement('p');

         document.getElementById('tipo_registro')?.setAttribute('aria-invalid', 'true');

         error.dataset.errorResponsable = 'tipo_registro';
         error.className = 'persona-type-error';
         error.textContent = mensaje;

         contenedorTipos?.insertAdjacentElement('afterend', error);

         return document.getElementById('tipo_registro');
     }

     function agregarTelefonoPersona() {
         const numeroInput = document.getElementById('numeroTelefono');
         const tipoInput = document.getElementById('tipoTelefono');
         const lista = document.getElementById('listaTelefonosPersona');

         const numero = numeroInput.value.trim();
         const tipo = tipoInput.value;

         if (numero === '') {
             mostrarErrorCampoPersonaWizard('numeroTelefono', 'Ingrese el número de teléfono.');
             return;
         }

         limpiarErrorCampoPersonaWizard('numeroTelefono');

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
         refrescarResumenSiEstaEnRevisionPersonaWizard();
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

         refrescarResumenSiEstaEnRevisionPersonaWizard();
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
             'form_id_territorio',
             'form_usuario_name',
             'form_usuario_email',
             'form_usuario_password'
         ].forEach(name => limpiarCampoPersonaWizard(name));

         // Al iniciar otro registro, los datos de cuenta vuelven a poder autocompletarse.
         ['form_usuario_name', 'form_usuario_email'].forEach(nombreCampo => {
             const campoCuenta = document.querySelector(`[name="${nombreCampo}"]`);
             if (campoCuenta) {
                 campoCuenta.dataset.manual = '0';
             }
         });

         sincronizarCuentaUsuarioPersona(false);

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
             limpiarErrorCampoPersonaWizard(campo.id || campo.name);
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
             'form_id_ocupacion'
         ];

         campos.forEach(name => {
             limpiarCampoPersonaWizard(name);
         });

         const rubrosPersona = document.getElementById('rubrosPersona');
         if (rubrosPersona) {
             Array.from(rubrosPersona.options).forEach(option => option.selected = false);
             rubrosPersona.dispatchEvent(new Event('change', { bubbles: true }));
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
            seccionRubros.classList.remove('hidden');
            seccionResponsables.classList.remove('hidden');
            bloqueRubrosWizard?.classList.remove('hidden');
            bloqueResponsablesWizard?.classList.remove('hidden');
            accionResponsablesWizard?.classList.remove('hidden');
            accionResponsablesWizard?.classList.add('is-visible');

            habilitarCampos('#seccion_empresa');
            habilitarCampos('#seccion_rubros');
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
     No cambia rutas; el guardado real en base de datos ocurre solo al presionar "Guardar registro" al final. -->
 <script>
     // Indice del paso visible actualmente.
     let pasoPersonaActual = 0;
     let pasoPersonaRestaurado = 0;

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
     const estadoEnvioPersona = document.getElementById('estadoEnvioPersona');
     const erroresPersonaWizard = @json($errors->getBag('default')->keys());
     const telefonosOldPersonaWizard = @json(old('telefonos', []));
     const responsablesOldPersonaWizard = @json(old('responsables', []));
     let avisoTipoRegistroPersonaMostrado = false;

     // Modo del formulario: create guarda borrador local, edit trabaja con datos ya registrados.
     const esModoEdicionPersona = formPersonaWizard?.dataset.modoFormulario === 'edit';
     const tieneErroresServidorPersona = formPersonaWizard?.dataset.tieneErrores === '1';
     const claveBorradorPersona = esModoEdicionPersona
         ? `certificador.personas.edit.${formPersonaWizard?.dataset.personaId || 'registro'}`
         : 'certificador.personas.create.borrador';
     const baseAssetPersonaWizard = @json(asset(''));

     // Items del panel lateral de progreso.
     const progresoPersonaWizard = {
         tipo: document.getElementById('progresoTipo'),
         generales: document.getElementById('progresoGenerales'),
         especificos: document.getElementById('progresoEspecificos'),
         telefonos: document.getElementById('progresoTelefonos'),
         complementos: document.getElementById('progresoComplementos'),
         cuenta: document.getElementById('progresoCuentaUsuario'),
     };

     // Mensajes de ayuda que aparecen debajo del formulario.
     const ayudasPorPasoPersona = [
         'Seleccione si registrara una persona natural o una empresa.',
         'Complete los datos generales comunes de la persona.',
         'Complete los datos específicos según el tipo registro seleccionado.',
         'Agregue teléfonos y complementos: rubros para natural o responsables para empresa.',
        'Revise el usuario, correo y rol. La contrasena puede escribirse o generarse al guardar.',
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
             titulo: 'Cuenta de usuario',
             subtitulo: 'Credenciales de acceso para iniciar sesion y crear tramites.'
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
     function itemResumenPersonaWizard(titulo, valor, anchoCompleto = false) {
         const texto = valor && String(valor).trim() !== '' ? valor : 'No registrado';
         const textoSeguro = escaparHtmlPersonaWizard(texto).replaceAll('\n', '<br>');

         return `
            <div class="persona-review-row ${anchoCompleto ? 'is-wide' : ''}">
                <dt>
                    ${escaparHtmlPersonaWizard(titulo)}
                </dt>
                <dd>
                    ${textoSeguro}
                </dd>
            </div>
         `;
     }

     // Agrupa varias filas en una seccion de revision para que el usuario lea por tema.
     function grupoResumenPersonaWizard(titulo, descripcion, items) {
         return `
            <section class="persona-review-section">
                <div class="persona-review-section-head">
                    <span class="persona-review-section-dot"></span>
                    <div>
                        <h4>${escaparHtmlPersonaWizard(titulo)}</h4>
                        <p>${escaparHtmlPersonaWizard(descripcion)}</p>
                    </div>
                </div>
                <div class="persona-review-list">
                    ${items.join('')}
                </div>
            </section>
         `;
     }

     // Construye una tabla para listas que pueden tener varios registros.
     function tablaResumenPersonaWizard(titulo, columnas, filas, mensajeVacio) {
         const encabezados = columnas
             .map(columna => `<th>${escaparHtmlPersonaWizard(columna)}</th>`)
             .join('');

         const filasHtml = filas.length
             ? filas.map(fila => `
                <tr>
                    ${fila.map(valor => `<td>${escaparHtmlPersonaWizard(valor || 'Sin dato').replaceAll('\n', '<br>')}</td>`).join('')}
                </tr>
             `).join('')
             : `
                <tr>
                    <td colspan="${columnas.length}">${escaparHtmlPersonaWizard(mensajeVacio)}</td>
                </tr>
             `;

         return `
            <div class="persona-review-table-block">
                <h5>${escaparHtmlPersonaWizard(titulo)}</h5>
                <div class="persona-review-table-wrap">
                    <table class="persona-review-table">
                        <thead>
                            <tr>${encabezados}</tr>
                        </thead>
                        <tbody>${filasHtml}</tbody>
                    </table>
                </div>
            </div>
         `;
     }

     // Lee un input oculto dentro de una lista dinamica, por ejemplo telefonos[0][numero].
     function valorOcultoResumenPersonaWizard(contenedor, campo) {
         const input = Array.from(contenedor.querySelectorAll('input[name]'))
             .find(elemento => elemento.name.endsWith(`[${campo}]`));

         return String(input?.value || '').trim();
     }

     // Devuelve el texto visible de una opcion cuando solo tenemos guardado su id.
     function textoSelectPorValorPersonaWizard(selector, valor) {
         const campo = document.querySelector(selector);
         if (!campo || !valor) return valor || '';

         const opcion = Array.from(campo.options || [])
             .find(item => String(item.value) === String(valor));

         return opcion?.text?.trim() || valor;
     }

     // Lista todos los telefonos que realmente se enviaran al controlador.
     function resumenTelefonosPersonaWizard() {
         const filas = Array.from(document.querySelectorAll('.telefono-agregado'))
             .map((item, indice) => {
                 const numero = valorOcultoResumenPersonaWizard(item, 'numero');
                 const tipo = valorOcultoResumenPersonaWizard(item, 'tipo');

                 return [indice + 1, numero || 'Sin numero', tipo || 'Sin tipo'];
             });

         return tablaResumenPersonaWizard(
             'Telefonos que se guardaran',
             ['#', 'Numero', 'Tipo'],
             filas,
             'No se agregaron telefonos.'
         );
     }

     // Lista los rubros seleccionados desde el catalogo principal.
     function resumenRubrosPersonaWizard() {
         const filas = Array.from(document.getElementById('rubrosPersona')?.selectedOptions || [])
             .map((option, indice) => [indice + 1, option.textContent.trim() || 'Sin rubro']);

         return tablaResumenPersonaWizard(
             'Rubros que se guardaran',
             ['#', 'Rubro'],
             filas,
             'No se seleccionaron rubros.'
         );
     }

     // Lee telefonos o rubros internos de un responsable agregado desde el modal.
     function resumenSublistaResponsablePersonaWizard(item, tipoLista) {
         const patron = tipoLista === 'telefonos' ? /\[telefonos\]\[(\d+)\]\[(numero|tipo)\]$/ : /\[rubros\]\[(\d+)\]\[(nombre|estado)\]$/;
         const agrupados = {};

         item.querySelectorAll('input[name]').forEach(input => {
             const coincidencia = input.name.match(patron);
             if (!coincidencia) return;

             const indice = coincidencia[1];
             const campo = coincidencia[2];

             agrupados[indice] = agrupados[indice] || {};
             agrupados[indice][campo] = input.value;
         });

         const lineas = Object.values(agrupados).map((dato, indice) => {
             if (tipoLista === 'telefonos') {
                 return `${indice + 1}) ${dato.numero || 'Sin numero'} - ${dato.tipo || 'Sin tipo'}`;
             }

             return `${indice + 1}) ${dato.nombre || 'Sin rubro'} - ${dato.estado || 'Sin estado'}`;
         });

         return lineas.length ? lineas.join('; ') : 'Sin registros';
     }

     // Muestra fechas limpias en revision, sin hora cuando viene desde base de datos.
     function fechaCortaPersonaWizard(valor) {
         const texto = String(valor || '').trim();
         return texto ? texto.split('T')[0].split(' ')[0] : 'Sin fecha';
     }

     // Traduce el valor guardado en genero para que el usuario no vea 1 o 0.
     function generoTextoPersonaWizard(valor) {
         if (String(valor) === '1') return 'Masculino';
         if (String(valor) === '0') return 'Femenino';
         return 'Sin genero';
     }

     // Convierte una ruta guardada en storage a una URL visible para abrir el PDF.
     function urlDocumentoPersonaWizard(ruta) {
         const valor = String(ruta || '').trim();

         if (!valor) return '';

         if (/^https?:\/\//i.test(valor)) {
             return valor;
         }

         const base = String(baseAssetPersonaWizard || '').replace(/\/$/, '');
         const rutaLimpia = valor.replace(/^\/+/, '');
         const rutaStorage = rutaLimpia.startsWith('storage/') ? rutaLimpia : `storage/${rutaLimpia}`;

         return `${base}/${rutaStorage}`;
     }

     // Muestra el estado del PDF sin mezclarlo con texto plano: link si ya existe, etiqueta si es nuevo.
     function respaldoResponsableRevisionPersonaWizard(urlGuardada, tieneArchivoNuevo = false) {
         const url = urlDocumentoPersonaWizard(urlGuardada);

         if (url) {
             return `
                <div class="persona-review-responsable-data">
                    <span>Respaldo PDF</span>
                    <strong>
                        <a href="${escaparHtmlPersonaWizard(url)}" target="_blank" class="persona-review-pdf-link">
                            Ver PDF
                        </a>
                    </strong>
                </div>
            `;
         }

         return datoResponsableRevisionPersonaWizard(
             'Respaldo PDF',
             tieneArchivoNuevo ? 'PDF seleccionado' : 'Sin PDF'
         );
     }

     // Construye una linea campo/valor para el resumen de responsables.
     function datoResponsableRevisionPersonaWizard(etiqueta, valor) {
         return `
            <div class="persona-review-responsable-data">
                <span>${escaparHtmlPersonaWizard(etiqueta)}</span>
                <strong>${escaparHtmlPersonaWizard(valor || 'Sin dato')}</strong>
            </div>
        `;
     }

     // Convierte telefonos o rubros ocultos del responsable en una lista visual compacta.
     function listaResponsableRevisionPersonaWizard(item, tipoLista) {
         const patron = tipoLista === 'telefonos' ?
             /\[telefonos\]\[(\d+)\]\[(numero|tipo)\]$/ :
             /\[rubros\]\[(\d+)\]\[(nombre|estado)\]$/;
         const agrupados = {};

         item.querySelectorAll('input[name]').forEach(input => {
             const coincidencia = input.name.match(patron);
             if (!coincidencia) return;

             agrupados[coincidencia[1]] = agrupados[coincidencia[1]] || {};
             agrupados[coincidencia[1]][coincidencia[2]] = input.value;
         });

         const registros = Object.values(agrupados);

         if (!registros.length) {
             return '<span class="persona-review-responsable-empty">Sin registros</span>';
         }

         return registros.map((dato, index) => {
             const principal = tipoLista === 'telefonos' ? dato.numero : dato.nombre;
             const secundario = tipoLista === 'telefonos' ? dato.tipo : dato.estado;

             return `
                <span class="persona-review-responsable-chip">
                    ${index + 1}. ${escaparHtmlPersonaWizard(principal || 'Sin dato')}
                    <small>${escaparHtmlPersonaWizard(secundario || 'Sin detalle')}</small>
                </span>
            `;
         }).join('');
     }

     // Lista todos los responsables de empresa con una vista legible, ideal cuando hay varios registros.
     function resumenResponsablesPersonaWizard() {
         const responsables = Array.from(document.querySelectorAll('.responsable-agregado'));

         if (!responsables.length) {
             return `
                <div class="persona-review-table-block persona-review-responsables-block">
                    <h5>Responsables que se guardaran</h5>
                    <div class="persona-review-responsable-empty-state">
                        No se agregaron responsables.
                    </div>
                </div>
            `;
         }

         const filas = responsables.map((item, indice) => {
             const tipo = valorOcultoResumenPersonaWizard(item, 'tipo') || 'Sin tipo';
             const idPersona = valorOcultoResumenPersonaWizard(item, 'id_persona');
             const nombres = valorOcultoResumenPersonaWizard(item, 'nombres');
             const paterno = valorOcultoResumenPersonaWizard(item, 'apellido_paterno');
             const materno = valorOcultoResumenPersonaWizard(item, 'apellido_materno');
             const casado = valorOcultoResumenPersonaWizard(item, 'apellido_casado');
             const nombre = [nombres, paterno, materno, casado].filter(Boolean).join(' ') || 'Responsable existente';
             const territorioId = valorOcultoResumenPersonaWizard(item, 'id_territorio');
             const archivoRespaldo = item.querySelector('input[type="file"]');
             const respaldoGuardado = valorOcultoResumenPersonaWizard(item, 'url_respaldo');
             const tieneRespaldo = archivoRespaldo?.files?.length || respaldoGuardado;
             const estado = valorOcultoResumenPersonaWizard(item, 'estado') || 'Sin estado';

             return `
                <article class="persona-review-responsable-item">
                    <div class="persona-review-responsable-title">
                        <span class="persona-review-responsable-number">${indice + 1}</span>
                        <div>
                            <h6>${escaparHtmlPersonaWizard(nombre)}</h6>
                            <p>${escaparHtmlPersonaWizard(tipo)}${idPersona ? ' | ID persona: ' + escaparHtmlPersonaWizard(idPersona) : ' | Persona nueva'}</p>
                        </div>
                        <span class="persona-review-responsable-status">${escaparHtmlPersonaWizard(estado)}</span>
                    </div>

                    <div class="persona-review-responsable-grid">
                        <section>
                            <h6>Identificacion del responsable</h6>
                            ${datoResponsableRevisionPersonaWizard('CI', valorOcultoResumenPersonaWizard(item, 'ci') || 'Sin CI')}
                            ${datoResponsableRevisionPersonaWizard('NIT', valorOcultoResumenPersonaWizard(item, 'nit') || 'Sin NIT')}
                            ${datoResponsableRevisionPersonaWizard('Complemento', valorOcultoResumenPersonaWizard(item, 'complemento') || 'Sin dato')}
                            ${datoResponsableRevisionPersonaWizard('Expedido', valorOcultoResumenPersonaWizard(item, 'expedido') || 'Sin dato')}
                        </section>

                        <section>
                            <h6>Contacto del responsable</h6>
                            ${datoResponsableRevisionPersonaWizard('Correo', valorOcultoResumenPersonaWizard(item, 'correo') || 'Sin correo')}
                            ${datoResponsableRevisionPersonaWizard('Domicilio del responsable', valorOcultoResumenPersonaWizard(item, 'domicilio') || 'Sin domicilio')}
                            ${datoResponsableRevisionPersonaWizard('Territorio del responsable', textoSelectPorValorPersonaWizard('#nuevo_id_territorio', territorioId) || 'Sin territorio')}
                        </section>

                        <section>
                            <h6>Datos personales del responsable</h6>
                            ${datoResponsableRevisionPersonaWizard('Nacimiento', fechaCortaPersonaWizard(valorOcultoResumenPersonaWizard(item, 'fecha_nacimiento')))}
                            ${datoResponsableRevisionPersonaWizard('Genero', generoTextoPersonaWizard(valorOcultoResumenPersonaWizard(item, 'genero')))}
                            ${datoResponsableRevisionPersonaWizard('Ocupacion', valorOcultoResumenPersonaWizard(item, 'ocupacion') || 'Sin ocupacion')}
                        </section>

                        <section>
                            <h6>Rol del responsable</h6>
                            ${datoResponsableRevisionPersonaWizard('Rol', textoSelectPorValorPersonaWizard('#nuevo_id_rol', valorOcultoResumenPersonaWizard(item, 'id_rol')) || 'Sin rol')}
                            ${datoResponsableRevisionPersonaWizard('Fecha registro', fechaCortaPersonaWizard(valorOcultoResumenPersonaWizard(item, 'fecha_registro')))}
                            ${datoResponsableRevisionPersonaWizard('Fecha baja', fechaCortaPersonaWizard(valorOcultoResumenPersonaWizard(item, 'fecha_baja')))}
                            ${respaldoResponsableRevisionPersonaWizard(respaldoGuardado, Boolean(archivoRespaldo?.files?.length))}
                        </section>

                        <section class="is-list">
                            <h6>Telefonos del responsable</h6>
                            <div>${listaResponsableRevisionPersonaWizard(item, 'telefonos')}</div>
                        </section>

                        <section class="is-list">
                            <h6>Rubros del responsable</h6>
                            <div>${listaResponsableRevisionPersonaWizard(item, 'rubros')}</div>
                        </section>
                    </div>
                </article>
            `;
         }).join('');

         return `
            <div class="persona-review-table-block persona-review-responsables-block">
                <h5>Responsables que se guardaran</h5>
                <div class="persona-review-responsables-list">
                    ${filas}
                </div>
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

     // Limpia el CI/NIT para usarlo como base del nombre de usuario.
     function textoUsuarioSeguroPersonaWizard(valor) {
         return String(valor || '')
             .toLowerCase()
             .replace(/[^a-z0-9]/g, '');
     }

     // Sugiere usuario con CI para natural o NIT/matricula para empresa.
     function sugerirNombreUsuarioPersonaWizard() {
         const tipo = tipoPersonaWizard();
         const base = tipo === 'EMPRESA'
             ? valorPersonaWizard('[name="form_nit"]') || valorPersonaWizard('[name="form_matricula"]')
             : valorPersonaWizard('[name="form_ci"]');

         return textoUsuarioSeguroPersonaWizard(base);
     }

     // Genera una contrasena temporal legible y con longitud suficiente.
     function generarPasswordCuentaPersona(forzar = false) {
         const campo = document.getElementById('form_usuario_password');
         if (!campo || (campo.value && !forzar)) return;
         if (formPersonaWizard?.dataset.modoFormulario === 'edit' && !forzar) return;

         const caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';
         let password = '';

        for (let i = 0; i < 10; i++) {
            password += caracteres[Math.floor(Math.random() * caracteres.length)];
        }

        campo.value = password;
        guardarBorradorPersonaWizard(false);
        actualizarProgresoPersonaWizard();
    }

     // Sincroniza los campos de cuenta con los datos principales ya escritos.
     function sincronizarCuentaUsuarioPersona(forzar = false) {
         const panel = document.getElementById('persona_cuenta_usuario_panel');
         const usuario = document.getElementById('form_usuario_name');
         const correo = document.getElementById('form_usuario_email');

         if (!panel || !usuario || !correo) return;

         panel.classList.remove('hidden');
         panel.querySelectorAll('input').forEach(campo => campo.disabled = false);

         const usuarioSugerido = sugerirNombreUsuarioPersonaWizard();
         const correoPrincipal = valorPersonaWizard('[name="form_correo"]');

         if (usuarioSugerido && (forzar || usuario.dataset.manual !== '1')) {
             usuario.value = usuarioSugerido;
             usuario.dataset.autocompletado = '1';
         }

         if (correoPrincipal && (forzar || correo.dataset.manual !== '1')) {
             correo.value = correoPrincipal;
             correo.dataset.autocompletado = '1';
         }

        actualizarProgresoPersonaWizard();
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

     function textoCamposPendientesPersonaWizard(pendientes) {
         if (!pendientes.length) {
             return '';
         }

         return `Falta: ${pendientes.join(', ')}.`;
     }

     function camposVaciosPersonaWizard(campos) {
         return campos
             .filter(([nombreCampo]) => {
                 const campo = buscarCampoPersonaWizard(nombreCampo);
                 return !campo || campo.disabled || String(campo.value || '').trim() === '';
             })
             .map(([, etiqueta]) => etiqueta);
     }

     // Cambia una fila del panel de progreso entre pendiente y listo.
     function marcarProgresoPersonaWizard(elemento, listo, pendientes = []) {
         if (!elemento) return;

         const estado = elemento.querySelector('.progreso-estado');
         const punto = elemento.querySelector('.progreso-punto');
         const panel = elemento.querySelector('.progreso-item-box');
         let detalle = elemento.querySelector('.progreso-detalle');

         elemento.classList.toggle('is-complete', listo);

         if (punto) {
             punto.dataset.numero = punto.dataset.numero || punto.textContent;
             punto.textContent = listo ? '\u2713' : punto.dataset.numero;
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

         if (!detalle && panel) {
             detalle = document.createElement('p');
             detalle.className = 'progreso-detalle';
             panel.appendChild(detalle);
         }

         if (detalle) {
             detalle.textContent = listo ? '' : textoCamposPendientesPersonaWizard(pendientes);
             detalle.classList.toggle('hidden', listo || pendientes.length === 0);
         }
     }

     // Actualiza el panel lateral de progreso segun los datos actuales.
     function actualizarProgresoPersonaWizard() {
         const tipo = tipoPersonaWizard();
         const pendientesTipo = tipo ? [] : ['tipo de registro'];
         const pendientesGenerales = camposVaciosPersonaWizard([
             ['form_correo', 'correo'],
             ['form_id_pais', 'pais'],
             ['form_id_territorio', 'departamento o territorio'],
         ]);

         const pendientesNatural = tipo === 'NATURAL'
             ? camposVaciosPersonaWizard([
                 ['form_ci', 'CI'],
                 ['form_nombres', 'nombres'],
                 ['form_apellido_paterno', 'apellido paterno'],
                 ['form_genero', 'genero'],
             ])
             : [];

         const pendientesEmpresa = tipo === 'EMPRESA'
             ? camposVaciosPersonaWizard([
                 ['form_nit', 'NIT'],
                 ['form_id_tipo_empresa', 'tipo de empresa'],
                 ['form_razon_social', 'razon social'],
                 ['form_matricula', 'matricula'],
             ])
             : [];

         const cantidadTelefonos = document.querySelectorAll('.telefono-agregado').length;
         const cantidadRubros = document.getElementById('rubrosPersona')?.selectedOptions.length || 0;
         const cantidadResponsables = document.querySelectorAll('.responsable-agregado').length;

         marcarProgresoPersonaWizard(progresoPersonaWizard.tipo, Boolean(tipo), pendientesTipo);
         marcarProgresoPersonaWizard(progresoPersonaWizard.generales, pendientesGenerales.length === 0, pendientesGenerales);
         marcarProgresoPersonaWizard(
             progresoPersonaWizard.especificos,
             tipo === 'EMPRESA' ? pendientesEmpresa.length === 0 : tipo === 'NATURAL' ? pendientesNatural.length === 0 : false,
             tipo === 'EMPRESA' ? pendientesEmpresa : tipo === 'NATURAL' ? pendientesNatural : ['datos especificos']
         );
         marcarProgresoPersonaWizard(
             progresoPersonaWizard.telefonos,
             cantidadTelefonos > 0,
             cantidadTelefonos > 0 ? [] : ['al menos un telefono']
         );
         marcarProgresoPersonaWizard(
             progresoPersonaWizard.complementos,
             tipo === 'EMPRESA' ? (cantidadRubros > 0 || cantidadResponsables > 0) : tipo === 'NATURAL' ? cantidadRubros > 0 : false,
             tipo === 'EMPRESA'
                 ? (cantidadRubros > 0 || cantidadResponsables > 0 ? [] : ['rubro o responsable'])
                 : tipo === 'NATURAL'
                     ? (cantidadRubros > 0 ? [] : ['rubro'])
                     : ['complementos']
         );

         // La cuenta depende de los datos base: correo y CI/NIT segun el tipo de registro.
         const datosBaseCuentaCompletos = Boolean(
             tipo &&
             valorPersonaWizard('[name="form_correo"]') &&
             (
                 tipo === 'NATURAL'
                     ? valorPersonaWizard('[name="form_ci"]')
                     : valorPersonaWizard('[name="form_nit"]')
             )
         );

         const cuentaCompleta = Boolean(
             datosBaseCuentaCompletos &&
             valorPersonaWizard('[name="form_usuario_name"]') &&
             valorPersonaWizard('[name="form_usuario_email"]') &&
             valorPersonaWizard('[name="form_id_role"]')
         );

         const pendientesCuenta = [];

         if (!datosBaseCuentaCompletos) {
             pendientesCuenta.push(tipo === 'NATURAL' ? 'correo y CI' : tipo === 'EMPRESA' ? 'correo y NIT' : 'tipo de registro');
         }

         pendientesCuenta.push(...camposVaciosPersonaWizard([
             ['form_usuario_name', 'nombre de usuario'],
             ['form_usuario_email', 'correo de acceso'],
             ['form_id_role', 'rol de acceso'],
         ]));

         marcarProgresoPersonaWizard(progresoPersonaWizard.cuenta, cuentaCompleta, [...new Set(pendientesCuenta)]);
     }

     // Actualiza el resumen del paso de revision usando secciones compactas.
     function actualizarResumenPersonaWizard() {
         if (!resumenPersonaWizard) return;

         const tipo = tipoPersonaWizard();
         const cantidadTelefonos = document.querySelectorAll('.telefono-agregado').length;
         const cantidadRubros = document.getElementById('rubrosPersona')?.selectedOptions.length || 0;
         const cantidadResponsables = document.querySelectorAll('.responsable-agregado').length;

         const paso1Tipo = [
             itemResumenPersonaWizard('Tipo de registro', tipo === 'EMPRESA' ? 'Empresa' : 'Persona natural'),
         ];

         const paso2DatosGenerales = [
             itemResumenPersonaWizard('Domicilio', valorPersonaWizard('[name="form_domicilio"]')),
             itemResumenPersonaWizard('NIT', valorPersonaWizard('[name="form_nit"]')),
             itemResumenPersonaWizard('Correo', valorPersonaWizard('[name="form_correo"]')),
             itemResumenPersonaWizard('Pais', textoSelectPersonaWizard('[name="form_id_pais"]')),
             itemResumenPersonaWizard('Ubicacion', textoSelectPersonaWizard('[name="form_id_territorio"]')),
             itemResumenPersonaWizard('Estado', textoSelectPersonaWizard('[name="form_estado"]'))
         ];

         let paso3DatosEspecificos = [];
         const paso4Complementos = [
             itemResumenPersonaWizard('Telefonos agregados', cantidadTelefonos),
             resumenTelefonosPersonaWizard()
         ];

         if (tipo === 'NATURAL') {
             paso3DatosEspecificos = [
                 itemResumenPersonaWizard('Nombre completo', nombreNaturalPersonaWizard()),
                 itemResumenPersonaWizard('Nombres', valorPersonaWizard('[name="form_nombres"]')),
                 itemResumenPersonaWizard('Apellido paterno', valorPersonaWizard('[name="form_apellido_paterno"]')),
                 itemResumenPersonaWizard('Apellido materno', valorPersonaWizard('[name="form_apellido_materno"]')),
                 itemResumenPersonaWizard('Apellido casado', valorPersonaWizard('[name="form_apellido_casado"]')),
                 itemResumenPersonaWizard('CI', valorPersonaWizard('[name="form_ci"]')),
                 itemResumenPersonaWizard('Complemento', valorPersonaWizard('[name="form_complemento"]')),
                 itemResumenPersonaWizard('Expedido', textoSelectPersonaWizard('[name="form_expedido"]')),
                 itemResumenPersonaWizard('Fecha de nacimiento', valorPersonaWizard('[name="form_fecha_nacimiento"]')),
                 itemResumenPersonaWizard('Genero', textoSelectPersonaWizard('[name="form_genero"]')),
                 itemResumenPersonaWizard('Ocupacion', textoSelectPersonaWizard('[name="form_id_ocupacion"]'))
             ];

             paso4Complementos.push(itemResumenPersonaWizard('Rubros agregados', cantidadRubros));
             paso4Complementos.push(resumenRubrosPersonaWizard());
         }

         if (tipo === 'EMPRESA') {
             paso3DatosEspecificos = [
                 itemResumenPersonaWizard('Tipo de empresa', textoSelectPersonaWizard('[name="form_id_tipo_empresa"]')),
                 itemResumenPersonaWizard('Razon social', valorPersonaWizard('[name="form_razon_social"]')),
                 itemResumenPersonaWizard('Matricula', valorPersonaWizard('[name="form_matricula"]')),
                 itemResumenPersonaWizard('Latitud', valorPersonaWizard('[name="form_latitud"]')),
                 itemResumenPersonaWizard('Longitud', valorPersonaWizard('[name="form_longitud"]')),
                 itemResumenPersonaWizard('Estado empresa', valorPersonaWizard('[name="form_estado_empresa"]'))
             ];

             paso4Complementos.push(itemResumenPersonaWizard('Responsables agregados', cantidadResponsables));
             paso4Complementos.push(resumenResponsablesPersonaWizard());
         }

         const paso5CuentaAcceso = [
             itemResumenPersonaWizard('Usuario de acceso', valorPersonaWizard('[name="form_usuario_name"]')),
             itemResumenPersonaWizard('Correo de acceso', valorPersonaWizard('[name="form_usuario_email"]')),
             itemResumenPersonaWizard('Rol de acceso', textoSelectPersonaWizard('[name="form_id_role"]')),
             itemResumenPersonaWizard(
                 formPersonaWizard?.dataset.modoFormulario === 'edit' ? 'Cambio de contrasena' : 'Contrasena',
                 valorPersonaWizard('[name="form_usuario_password"]') || (
                     formPersonaWizard?.dataset.modoFormulario === 'edit' && formPersonaWizard?.dataset.cuentaExistente === '1'
                         ? 'Se mantiene la actual'
                         : 'Se generara al guardar'
                 )
             )
         ];

         resumenPersonaWizard.innerHTML = [
             grupoResumenPersonaWizard('Paso 1 - Tipo de registro', 'Define si se guardara como persona natural o empresa.', paso1Tipo),
             grupoResumenPersonaWizard('Paso 2 - Datos generales', 'Datos que alimentan la tabla personas y su territorio.', paso2DatosGenerales),
             grupoResumenPersonaWizard('Paso 3 - Datos especificos', tipo === 'EMPRESA' ? 'Datos que alimentan la tabla empresas.' : 'Datos que alimentan la tabla naturals.', paso3DatosEspecificos),
             grupoResumenPersonaWizard('Paso 4 - Complementos', tipo === 'EMPRESA' ? 'Telefonos y responsables que se guardaran relacionados a la empresa.' : 'Telefonos y rubros que se guardaran relacionados a la persona natural.', paso4Complementos),
             grupoResumenPersonaWizard('Paso 5 - Cuenta de acceso', 'Usuario, correo, rol y contrasena que se guardaran para iniciar sesion.', paso5CuentaAcceso),
         ].join('');
     }

     // Mantiene el paso de revision sincronizado cuando se cambia un dato sin salir de ese paso.
     function refrescarResumenSiEstaEnRevisionPersonaWizard() {
         if (typeof pasoPersonaActual === 'undefined' || typeof pasosPersona === 'undefined') return;

         if (pasoPersonaActual === pasosPersona.length - 1) {
             actualizarResumenPersonaWizard();
         }
     }

     // Indica en que paso debe abrir el wizard segun el primer campo con error.
     function pasoDesdeErrorPersonaWizard(nombreCampo) {
         if (!nombreCampo || nombreCampo === 'form_tipo_registro') return 0;

         const camposGenerales = [
             'form_domicilio',
             'form_nit',
             'form_correo',
             'form_id_territorio',
             'form_estado'
         ];

         const camposEspecificos = [
             'form_ci',
             'form_complemento',
             'form_expedido',
             'form_nombres',
             'form_apellido_paterno',
             'form_apellido_materno',
             'form_apellido_casado',
             'form_fecha_nacimiento',
             'form_genero',
             'form_id_ocupacion',
             'form_id_tipo_empresa',
             'form_razon_social',
             'form_matricula',
             'form_latitud',
             'form_longitud',
             'form_estado_empresa'
         ];

         if (camposGenerales.includes(nombreCampo)) return 1;
         if (camposEspecificos.includes(nombreCampo)) return 2;
         if (nombreCampo.startsWith('telefonos') || nombreCampo.startsWith('rubros') || nombreCampo.startsWith('responsables')) return 3;
         if (nombreCampo.startsWith('form_usuario_') || nombreCampo === 'form_id_role') return 4;

         return 0;
     }

     // Marca visualmente el paso donde Laravel detecto el primer error.
     function pasoInicialPorErroresPersonaWizard() {
         if (!tieneErroresServidorPersona || erroresPersonaWizard.length === 0) {
             return tipoPersonaWizard() ? pasoPersonaRestaurado : 0;
         }

         return pasoDesdeErrorPersonaWizard(erroresPersonaWizard[0]);
     }

     // Reconstruye telefonos y rubros que volvieron con old() despues de una validacion fallida.
     function rehidratarListasOldPersonaWizard() {
         if (!tieneErroresServidorPersona) return;

         telefonosOldPersonaWizard.forEach(telefono => {
             const numeroInput = document.getElementById('numeroTelefono');
             const tipoInput = document.getElementById('tipoTelefono');

             if (!numeroInput || !tipoInput || !telefono?.numero) return;

             numeroInput.value = telefono.numero;
             tipoInput.value = telefono.tipo || telefono.estado || 'CELULAR';
             agregarTelefonoPersona();
         });     }

     // Guarda los campos normales del formulario en localStorage.
     function guardarBorradorPersonaWizard(mostrarMensaje = true) {
         if (esModoEdicionPersona) return;
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

             estadoBorrador.textContent = `Avance temporal ${hora}`;
             estadoBorrador.className =
                 'persona-save-pill bg-emerald-50 text-emerald-700';
         }

         if (mostrarMensaje) {
             console.info('Borrador de persona guardado en este navegador.');
         }
     }

     // Limpia el registro nuevo cuando se entra desde el boton Registrar, no desde una validacion fallida.
     function limpiarRegistroNuevoPersonaWizard() {
         if (esModoEdicionPersona || tieneErroresServidorPersona) return;

         localStorage.removeItem(claveBorradorPersona);

         if (tipoRegistro) {
             tipoRegistro.value = '';
             tipoRegistro.dispatchEvent(new Event('change', { bubbles: true }));
         }

         limpiarDatosGeneralesPersonaWizard();
         limpiarTelefonosPersonaWizard();
         limpiarSeccionNatural();
         limpiarSeccionEmpresa();
         actualizarTipoRapidoPersonaWizard();
     }

     // Restaura el borrador local solo cuando Laravel vuelve con errores de validacion.
     function restaurarBorradorPersonaWizard() {
         if (esModoEdicionPersona) return;
         if (!tieneErroresServidorPersona) {
             localStorage.removeItem(claveBorradorPersona);
             return;
         }

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
                 estadoBorrador.textContent = 'Datos recuperados';
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
             mostrarErrorTipoRegistroPersonaWizard('Seleccione el tipo de registro para continuar.');
             return false;
         }

         limpiarErrorCampoPersonaWizard('tipo_registro');
         return true;
     }

     function reglasObligatoriasPersonaWizard() {
         const tipo = tipoPersonaWizard();
         const reglas = [
             ['form_tipo_registro', 'Seleccione el tipo de registro.'],
             ['form_correo', 'Ingrese el correo electrónico.'],
             ['form_id_pais', 'Seleccione el país.'],
             ['form_id_territorio', 'Seleccione el departamento o territorio.'],
             ['form_usuario_name', 'Ingrese el nombre de usuario.'],
             ['form_usuario_email', 'Ingrese el correo de acceso.'],
             ['form_id_role', 'Seleccione el rol de acceso.'],
         ];

         if (tipo === 'NATURAL') {
             reglas.push(
                 ['form_ci', 'Ingrese el CI de la persona natural.'],
                 ['form_nombres', 'Ingrese los nombres.'],
                 ['form_apellido_paterno', 'Ingrese el apellido paterno.'],
                 ['form_genero', 'Seleccione el género.'],
             );
         }

         if (tipo === 'EMPRESA') {
             reglas.push(
                 ['form_nit', 'Ingrese el NIT de la empresa.'],
                 ['form_id_tipo_empresa', 'Seleccione el tipo de empresa.'],
                 ['form_razon_social', 'Ingrese la razón social.'],
                 ['form_matricula', 'Ingrese la matrícula.'],
             );
         }

         return reglas;
     }

     function aplicarRequiredFrontendPersonaWizard() {
         if (!formPersonaWizard) return;

         formPersonaWizard.querySelectorAll('[data-required-persona-wizard="1"]').forEach(campo => {
             campo.removeAttribute('required');
             campo.removeAttribute('aria-required');
             campo.removeAttribute('data-required-persona-wizard');
         });

         reglasObligatoriasPersonaWizard().forEach(([nombreCampo]) => {
             const campo = buscarCampoPersonaWizard(nombreCampo);
             if (!campo || campo.disabled) return;

             campo.setAttribute('required', 'required');
             campo.setAttribute('aria-required', 'true');
             campo.dataset.requiredPersonaWizard = '1';
         });
     }

     function validarEmailPersonaWizard(nombreCampo, mensaje) {
         const campo = buscarCampoPersonaWizard(nombreCampo);
         if (!campo || campo.disabled || String(campo.value || '').trim() === '') {
             return null;
         }

         return campo.checkValidity() ? null : mostrarErrorCampoPersonaWizard(nombreCampo, mensaje);
     }

     function validarPasswordPersonaWizard() {
         const campo = buscarCampoPersonaWizard('form_usuario_password');
         const valor = String(campo?.value || '').trim();

         if (!campo || campo.disabled || valor === '' || valor.length >= 8) {
             limpiarErrorCampoPersonaWizard('form_usuario_password');
             return null;
         }

         return mostrarErrorCampoPersonaWizard('form_usuario_password', 'La contraseña debe tener al menos 8 caracteres.');
     }

     function validarFormularioPersonaWizard() {
         aplicarRequiredFrontendPersonaWizard();
         limpiarErrorCampoPersonaWizard('tipo_registro');

         let primerCampoError = null;

         reglasObligatoriasPersonaWizard().forEach(([nombreCampo, mensaje]) => {
             const campo = buscarCampoPersonaWizard(nombreCampo);
             limpiarErrorCampoPersonaWizard(nombreCampo);

             if (!campo || campo.disabled) return;

             if (String(campo.value || '').trim() === '') {
                 const campoConError = nombreCampo === 'form_tipo_registro'
                     ? mostrarErrorTipoRegistroPersonaWizard(mensaje)
                     : mostrarErrorCampoPersonaWizard(nombreCampo, mensaje);

                 primerCampoError = primerCampoError || campoConError;
             }
         });

         primerCampoError = primerCampoError || validarEmailPersonaWizard('form_correo', 'Ingrese un correo electrónico válido.');
         primerCampoError = primerCampoError || validarEmailPersonaWizard('form_usuario_email', 'Ingrese un correo de acceso válido.');
         primerCampoError = primerCampoError || validarPasswordPersonaWizard();

         if (!primerCampoError) {
             return true;
         }

         const nombreCampo = primerCampoError.getAttribute('name') || primerCampoError.id;
         mostrarPasoPersonaWizard(pasoDesdeErrorPersonaWizard(nombreCampo));

         setTimeout(() => {
             primerCampoError.focus?.();
             primerCampoError.scrollIntoView?.({ behavior: 'smooth', block: 'center' });
         }, 80);

         return false;
     }

     // Muestra SweetAlert cuando intentan saltar directo a cualquier burbuja sin elegir el tipo de registro.
     function mostrarAvisoTipoRegistroPersonaWizard(forzar = false) {
         if (tipoPersonaWizard() !== '') return;
         if (!forzar && avisoTipoRegistroPersonaMostrado) return;

         avisoTipoRegistroPersonaMostrado = true;

         if (typeof Swal === 'undefined') return;

         Swal.fire({
             icon: 'info',
             title: 'Seleccione el tipo de registro',
             text: 'Primero debe elegir si registrara una persona natural o una empresa.',
             confirmButtonText: 'Entendido',
             confirmButtonColor: '#0f766e'
         });
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

     // Guarda el avance local antes de enviar; el guardado real lo hace el submit del formulario.
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
             limpiarErrorCampoPersonaWizard('tipo_registro');
         });
     });

     // Permite ir a una burbuja anterior o futura si ya se eligio tipo.
     burbujasPersona.forEach(burbuja => {
         burbuja.addEventListener('click', () => {
             const destino = Number(burbuja.dataset.wizardIr);

             if (destino > 0 && tipoPersonaWizard() === '') {
                 limpiarErrorCampoPersonaWizard('tipo_registro');
                 mostrarAvisoTipoRegistroPersonaWizard(true);
                 return;
             }

             mostrarPasoPersonaWizard(destino);
         });
     });

     // Si cambia el tipo, se reutiliza tu logica actual y se vuelve al primer paso.
     tipoRegistro?.addEventListener('change', () => {
         limpiarErrorCampoPersonaWizard('tipo_registro');

         if (typeof cambiarTipoRegistro === 'function') {
             cambiarTipoRegistro();
         }

         aplicarRequiredFrontendPersonaWizard();
         actualizarTipoRapidoPersonaWizard();
         actualizarProgresoPersonaWizard();
         mostrarPasoPersonaWizard(0);
     });

     // La cuenta toma datos base: correo principal y CI/NIT segun el tipo de registro.
     ['form_correo', 'form_ci', 'form_nit', 'form_matricula'].forEach(nombreCampo => {
         document.querySelector(`[name="${nombreCampo}"]`)?.addEventListener('input', () => {
             sincronizarCuentaUsuarioPersona(false);
             actualizarProgresoPersonaWizard();
         });
     });

     // Si el funcionario edita las credenciales, se respeta su valor y no se vuelve a pisar.
     ['form_usuario_name', 'form_usuario_email'].forEach(nombreCampo => {
         document.querySelector(`[name="${nombreCampo}"]`)?.addEventListener('input', (evento) => {
             evento.target.dataset.manual = '1';
             actualizarProgresoPersonaWizard();
         });
     });

     // Guarda automaticamente los campos simples mientras se escribe.
     formPersonaWizard?.addEventListener('input', (evento) => {
         limpiarErrorSiCampoCorregido(evento.target);
         guardarBorradorPersonaWizard(false);
         actualizarProgresoPersonaWizard();
         refrescarResumenSiEstaEnRevisionPersonaWizard();
     });
     formPersonaWizard?.addEventListener('change', (evento) => {
         limpiarErrorSiCampoCorregido(evento.target);
         guardarBorradorPersonaWizard(false);
         actualizarTipoRapidoPersonaWizard();
         actualizarProgresoPersonaWizard();
         refrescarResumenSiEstaEnRevisionPersonaWizard();
     });

      function sincronizarCamposAntesDeEnviarPersonaWizard() {
          if (!formPersonaWizard) return;

          formPersonaWizard.querySelectorAll('input[name], select[name], textarea[name]').forEach(campo => {
              if (campo.type === 'file') return;
              if (campo.name === 'form_tipo_registro') return;

              campo.dispatchEvent(new Event('change', { bubbles: true }));
              campo.dispatchEvent(new Event('blur', { bubbles: true }));
          });
      }

      // Antes del submit final se valida, se guarda el borrador y se bloquea doble envio.
      formPersonaWizard?.addEventListener('submit', (evento) => {
          if (typeof cambiarTipoRegistro === 'function') {
              cambiarTipoRegistro(false);
          }

          sincronizarCuentaUsuarioPersona(false);
          sincronizarCamposAntesDeEnviarPersonaWizard();
          guardarBorradorPersonaWizard(false);

          if (!validarFormularioPersonaWizard()) {
              evento.preventDefault();
              return;
          }

          if (typeof Swal !== 'undefined') {
              Swal.fire({
                  title: esModoEdicionPersona ? 'Actualizando registro' : 'Registrando datos',
                  text: 'Espere un momento, estamos guardando la información.',
                  allowOutsideClick: false,
                  allowEscapeKey: false,
                  didOpen: () => Swal.showLoading(),
              });
          }

          btnGuardarRegistro?.setAttribute('disabled', 'disabled');
         btnGuardarRegistro?.classList.add('opacity-70', 'cursor-not-allowed');
         btnPasoAnterior?.setAttribute('disabled', 'disabled');
         btnPasoSiguiente?.setAttribute('disabled', 'disabled');
         btnGuardarBorrador?.setAttribute('disabled', 'disabled');

         if (btnGuardarRegistro) {
             btnGuardarRegistro.textContent = esModoEdicionPersona ? 'Actualizando...' : 'Registrando...';
         }
     });

     // Refresca el progreso despues de botones que agregan o quitan listas dinamicas.
     document.addEventListener('click', (evento) => {
         const accionDinamica = evento.target.closest(
             '[onclick*="agregarTelefonoPersona"], [onclick*="quitarTelefonoPersona"], [onclick*="agregarNuevoResponsableTemporal"], [onclick*="quitarResponsableEmpresa"]'
         );

         if (!accionDinamica) return;

         setTimeout(() => {
             actualizarProgresoPersonaWizard();
             guardarBorradorPersonaWizard(false);
         }, 0);
     });

     // Inicializa el wizard: limpio al entrar a registrar, conserva datos solo si hay errores.
     limpiarRegistroNuevoPersonaWizard();
      restaurarBorradorPersonaWizard();
      rehidratarListasOldPersonaWizard();
      sincronizarCuentaUsuarioPersona(false);
      aplicarRequiredFrontendPersonaWizard();
      tipoRegistroAnterior = tipoPersonaWizard();
      mostrarPasoPersonaWizard(pasoInicialPorErroresPersonaWizard());

     // Si el navegador restaura la pagina desde cache, se vuelve a limpiar en modo registrar.
     window.addEventListener('pageshow', () => {
         if (tieneErroresServidorPersona) return;

         limpiarRegistroNuevoPersonaWizard();
         tipoRegistroAnterior = tipoPersonaWizard();
         mostrarPasoPersonaWizard(0);
     });
 </script>

 <!-- SCRIPT RESPONSABLES -->
 <script>
     let indiceResponsable = 0;
     let telefonosResponsableTemporal = [];
     let rubrosResponsableTemporal = [];
     let responsableEditandoElemento = null;
     let responsableEditandoIndice = null;
     let pdfTemporalResponsableModal = null;

     function abrirModalResponsable() {
         responsableEditandoElemento = null;
         responsableEditandoIndice = null;
         limpiarModalResponsable();
         configurarEncabezadoModalResponsable(false);
         document.getElementById('modalNuevoResponsable').classList.remove('hidden');
         prepararModoPersonaNueva();
     }

     function cerrarModalResponsable() {
         document.getElementById('modalNuevoResponsable').classList.add('hidden');
         responsableEditandoElemento = null;
         responsableEditandoIndice = null;
         configurarEncabezadoModalResponsable(false);
     }

     function configurarEncabezadoModalResponsable(esEdicion) {
         const titulo = document.getElementById('tituloModalResponsable');
         const boton = document.getElementById('btnGuardarResponsableModal');

         if (titulo) {
             titulo.textContent = esEdicion ? 'Editar responsable' : 'Registrar responsable';
         }

         if (boton) {
             boton.textContent = esEdicion ? 'Guardar cambios' : 'Agregar Responsable';
         }
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

         document.getElementById('formTelefonosResponsable').classList.remove('hidden');
         document.getElementById('formRubrosResponsable').classList.remove('hidden');

         document.getElementById('textoModoTelefonosResponsable').innerText =
             'Persona existente: los teléfonos solo se muestran como información.';

         document.getElementById('textoModoRubrosResponsable').innerText =
             'Persona existente: los rubros solo se muestran como información.';
         document.getElementById('textoModoTelefonosResponsable').innerText =
             'Persona existente: puede actualizar los telefonos de su registro propio.';

         document.getElementById('textoModoRubrosResponsable').innerText =
             'Persona existente: puede actualizar los rubros de su registro propio.';
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

         mostrarTelefonosExistentes(opcion.dataset.telefonos, false);
         mostrarRubrosExistentes(opcion.dataset.rubros, false);
     }

     function agregarTelefonoResponsableModal() {
         const numeroInput = document.getElementById('nuevo_telefono');
         const tipoInput = document.getElementById('nuevo_tipo_telefono');

         const numero = numeroInput.value.trim();
         const tipo = tipoInput.value;

         if (numero === '') {
             mostrarErrorCampoResponsable('nuevo_telefono', 'Ingrese el número de teléfono.');
             return;
         }

         limpiarErrorCampoResponsable('nuevo_telefono');

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

     function mostrarTelefonosExistentes(telefonosJson, soloLectura = true) {
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

         renderTelefonosResponsable(soloLectura);
     }

     function agregarRubroResponsableModal() {
         const nombreInput = document.getElementById('nuevo_rubro');
         const estadoInput = document.getElementById('nuevo_estado_rubro');

         const nombre = nombreInput.value.trim();
         const estado = estadoInput.value;

         if (nombre === '') {
             mostrarErrorCampoResponsable('nuevo_rubro', 'Ingrese el nombre del rubro.');
             return;
         }

         limpiarErrorCampoResponsable('nuevo_rubro');

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

     function mostrarRubrosExistentes(rubrosJson, soloLectura = true) {
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

         renderRubrosResponsable(soloLectura);
     }

     // Recalcula los numeros visibles cuando se agrega o quita un responsable.
     function actualizarNumerosResponsablesEmpresa() {
         document.querySelectorAll('.responsable-agregado .responsables-review-number').forEach((numero, index) => {
             numero.textContent = index + 1;
         });
     }

     // Muestra telefonos o rubros dentro de la fila del responsable agregado.
     function chipsResponsableEmpresa(registros, campoPrincipal, campoSecundario) {
         if (!Array.isArray(registros) || registros.length === 0) {
             return '<span class="responsables-review-empty-inline">Sin registros</span>';
         }

         return registros.map((registro, index) => `
            <span class="responsables-review-chip">
                ${index + 1}. ${escaparHtmlPersonaWizard(registro[campoPrincipal] || 'Sin dato')}
                <small>${escaparHtmlPersonaWizard(registro[campoSecundario] || registro.estado || registro.tipo || 'Sin detalle')}</small>
            </span>
        `).join('');
     }

     // Construye la fila visual de un responsable sin cambiar los input hidden que usa el controlador.
     function plantillaResponsableEmpresa(datos, hiddenTelefonos = '', hiddenRubros = '', tieneRespaldo = false) {
         const nombreCompleto = datos.nombreCompleto || 'Responsable agregado';
         const tipoResponsable = datos.tipoResponsable || 'EXISTENTE';
         const indiceFormulario = Number.isInteger(datos.indiceFormulario) ? datos.indiceFormulario : indiceResponsable;
         const numeroVisible = datos.numeroVisible || indiceFormulario + 1;
         const etiquetaTipo = tipoResponsable === 'EXISTENTE' ? 'Existente' : 'Nuevo';
         const territorioTexto = textoSelectPorValorPersonaWizard('#nuevo_id_territorio', datos.idTerritorio) || 'Sin territorio';
         const estado = datos.estadoResponsable || 'ACTIVO';
         const telefonosVisibles = chipsResponsableEmpresa(datos.telefonos || [], 'numero', 'tipo');
         const rubrosVisibles = chipsResponsableEmpresa(datos.rubros || [], 'nombre', 'estado');

         return `
            <div class="responsables-review-title">
                <span class="responsables-review-number">${numeroVisible}</span>
                <div>
                    <strong>${escaparHtmlPersonaWizard(nombreCompleto)}</strong>
                    <small>${escaparHtmlPersonaWizard(etiquetaTipo)}${datos.idPersonaExistente ? ' | ID persona: ' + escaparHtmlPersonaWizard(datos.idPersonaExistente) : ' | Persona nueva'}</small>
                </div>

                <span class="responsables-review-pill ${estado === 'ACTIVO' ? 'is-ok' : ''}">${escaparHtmlPersonaWizard(estado)}</span>

                <div class="responsables-review-actions">
                    <button type="button" onclick="editarResponsableEmpresa(this)" class="responsables-review-edit">
                        Editar
                    </button>

                    <button type="button" onclick="quitarResponsableEmpresa(this)" class="responsables-review-remove">
                        Quitar
                    </button>
                </div>
            </div>

            <div class="responsables-review-grid">
                <section>
                    <h6>Identificacion del responsable</h6>
                    ${datoResponsableRevisionPersonaWizard('CI', datos.ci || 'Sin CI')}
                    ${datoResponsableRevisionPersonaWizard('NIT', datos.nit || 'Sin NIT')}
                    ${datoResponsableRevisionPersonaWizard('Complemento', datos.complemento || 'Sin dato')}
                    ${datoResponsableRevisionPersonaWizard('Expedido', datos.expedido || 'Sin dato')}
                </section>

                <section>
                    <h6>Contacto del responsable</h6>
                    ${datoResponsableRevisionPersonaWizard('Correo', datos.correo || 'Sin correo')}
                    ${datoResponsableRevisionPersonaWizard('Domicilio del responsable', datos.domicilio || 'Sin domicilio')}
                    ${datoResponsableRevisionPersonaWizard('Territorio del responsable', territorioTexto)}
                </section>

                <section>
                    <h6>Datos personales del responsable</h6>
                    ${datoResponsableRevisionPersonaWizard('Nacimiento', fechaCortaPersonaWizard(datos.fechaNacimiento))}
                    ${datoResponsableRevisionPersonaWizard('Genero', generoTextoPersonaWizard(datos.genero))}
                    ${datoResponsableRevisionPersonaWizard('Ocupacion', datos.ocupacion || 'Sin ocupacion')}
                </section>

                <section>
                    <h6>Rol del responsable</h6>
                    ${datoResponsableRevisionPersonaWizard('Rol', datos.rolNombre || 'Sin rol')}
                    ${datoResponsableRevisionPersonaWizard('Fecha registro', fechaCortaPersonaWizard(datos.fechaRegistro))}
                    ${datoResponsableRevisionPersonaWizard('Fecha baja', fechaCortaPersonaWizard(datos.fechaBaja))}
                    ${respaldoResponsableRevisionPersonaWizard(datos.urlRespaldo, tieneRespaldo)}
                </section>

                <section class="is-list">
                    <h6>Telefonos del responsable</h6>
                    <div>${telefonosVisibles}</div>
                </section>

                <section class="is-list">
                    <h6>Rubros del responsable</h6>
                    <div>${rubrosVisibles}</div>
                </section>
            </div>

            <input type="hidden" name="responsables[${indiceFormulario}][tipo]" value="${tipoResponsable}">
            <input type="hidden" name="responsables[${indiceFormulario}][id_persona]" value="${datos.idPersonaExistente || ''}">

            <input type="hidden" name="responsables[${indiceFormulario}][domicilio]" value="${datos.domicilio || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][nit]" value="${datos.nit || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][correo]" value="${datos.correo || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][id_territorio]" value="${datos.idTerritorio || ''}">

            <input type="hidden" name="responsables[${indiceFormulario}][nombres]" value="${datos.nombres || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][apellido_paterno]" value="${datos.paterno || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][apellido_materno]" value="${datos.materno || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][apellido_casado]" value="${datos.casado || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][ci]" value="${datos.ci || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][complemento]" value="${datos.complemento || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][expedido]" value="${datos.expedido || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][fecha_nacimiento]" value="${datos.fechaNacimiento || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][genero]" value="${datos.genero || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][ocupacion]" value="${datos.ocupacion || ''}">

            <input type="hidden" name="responsables[${indiceFormulario}][id_rol]" value="${datos.idRol || ''}">
            ${datos.urlRespaldo ? `<input type="hidden" name="responsables[${indiceFormulario}][url_respaldo]" value="${datos.urlRespaldo}">` : ''}
            <input type="hidden" name="responsables[${indiceFormulario}][fecha_registro]" value="${datos.fechaRegistro || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][fecha_baja]" value="${datos.fechaBaja || ''}">
            <input type="hidden" name="responsables[${indiceFormulario}][estado]" value="${estado}">

            ${hiddenTelefonos}
            ${hiddenRubros}
        `;
     }

     function indiceFormularioResponsableDesdeItem(item) {
         const input = item?.querySelector('input[name^="responsables["]');
         const coincidencia = input?.name?.match(/^responsables\[(\d+)\]/);

         return coincidencia ? Number(coincidencia[1]) : indiceResponsable;
     }

     function sublistaResponsableDesdeItem(item, tipoLista) {
         const patron = tipoLista === 'telefonos'
             ? /\[telefonos\]\[(\d+)\]\[(numero|tipo)\]$/
             : /\[rubros\]\[(\d+)\]\[(nombre|estado)\]$/;
         const agrupados = {};

         item.querySelectorAll('input[name]').forEach(input => {
             const coincidencia = input.name.match(patron);
             if (!coincidencia) return;

             agrupados[coincidencia[1]] = agrupados[coincidencia[1]] || {};
             agrupados[coincidencia[1]][coincidencia[2]] = input.value;
         });

         return Object.values(agrupados);
     }

     function seleccionarPersonaResponsableModal(valor) {
         const select = document.getElementById('modal_id_persona_responsable');

         if (select?.tomselect) {
             select.tomselect.setValue(valor || '', true);
         } else if (select) {
             select.value = valor || '';
         }
     }

     function asignarFechaResponsableModal(nombre, valor) {
         const fecha = String(valor || '').trim().split('T')[0].split(' ')[0];
         const campo = document.querySelector(`[name="${nombre}"]`);

         if (!campo) return;

         campo.value = fecha;
         campo.setAttribute('value', fecha);
         campo.dispatchEvent(new InputEvent('input', { bubbles: true }));
         campo.dispatchEvent(new Event('change', { bubbles: true }));
         campo.dispatchEvent(new Event('blur', { bubbles: true }));
     }

     // Encuentra si el responsable ya esta agregado en la lista temporal de la empresa.
     // Se compara por id_persona cuando es una persona existente y por CI cuando es una persona nueva.
     function encontrarResponsableEmpresaAgregado(idPersonaExistente, ci, itemEditando = null) {
         const idNormalizado = String(idPersonaExistente || '').trim();
         const ciNormalizado = String(ci || '').trim().toLowerCase();
         const responsablesAgregados = document.querySelectorAll('#listaResponsablesEmpresa .responsable-agregado');

         return Array.from(responsablesAgregados).find(item => {
             if (itemEditando && item === itemEditando) {
                 return false;
             }

             const idRegistrado = String(valorOcultoResumenPersonaWizard(item, 'id_persona') || '').trim();
             const ciRegistrado = String(valorOcultoResumenPersonaWizard(item, 'ci') || '').trim().toLowerCase();

             return (idNormalizado && idRegistrado === idNormalizado)
                 || (ciNormalizado && ciRegistrado === ciNormalizado);
         });
     }

     // Quita avisos generales del modal para que no queden mensajes viejos al corregir datos.
     function limpiarAvisoModalResponsable() {
         document.getElementById('avisoResponsableDuplicado')?.remove();
     }

     // Muestra un aviso dentro del modal. No abre otro modal y no cierra lo que el usuario esta llenando.
     function mostrarAvisoModalResponsable(mensaje) {
         const cuerpoModal = document.querySelector('#modalNuevoResponsable [data-modal-body-responsable]');

         if (!cuerpoModal) return;

         limpiarAvisoModalResponsable();

         const aviso = document.createElement('div');
         aviso.id = 'avisoResponsableDuplicado';
         aviso.className = 'responsable-modal-warning';
         aviso.textContent = mensaje;

         cuerpoModal.prepend(aviso);
         cuerpoModal.scrollTo({ top: 0, behavior: 'smooth' });
     }

     // Edita la fila temporal del responsable sin convertirlo en datos propios de la empresa.
     function editarResponsableEmpresa(boton) {
         const item = boton.closest('.responsable-agregado');
         if (!item) return;

         limpiarModalResponsable();

         responsableEditandoElemento = item;
         responsableEditandoIndice = indiceFormularioResponsableDesdeItem(item);

         configurarEncabezadoModalResponsable(true);
         document.getElementById('modalNuevoResponsable').classList.remove('hidden');

         const idPersona = valorOcultoResumenPersonaWizard(item, 'id_persona');
         seleccionarPersonaResponsableModal(idPersona);
         document.getElementById('nuevo_id_persona_existente').value = idPersona;

         document.getElementById('nuevo_domicilio').value = valorOcultoResumenPersonaWizard(item, 'domicilio');
         document.getElementById('nuevo_nit').value = valorOcultoResumenPersonaWizard(item, 'nit');
         document.getElementById('nuevo_correo').value = valorOcultoResumenPersonaWizard(item, 'correo');
         document.getElementById('nuevo_id_territorio').value = valorOcultoResumenPersonaWizard(item, 'id_territorio');
         document.getElementById('nuevo_nombres').value = valorOcultoResumenPersonaWizard(item, 'nombres');
         document.getElementById('nuevo_apellido_paterno').value = valorOcultoResumenPersonaWizard(item, 'apellido_paterno');
         document.getElementById('nuevo_apellido_materno').value = valorOcultoResumenPersonaWizard(item, 'apellido_materno');
         document.getElementById('nuevo_apellido_casado').value = valorOcultoResumenPersonaWizard(item, 'apellido_casado');
         document.getElementById('nuevo_ci').value = valorOcultoResumenPersonaWizard(item, 'ci');
         document.getElementById('nuevo_complemento').value = valorOcultoResumenPersonaWizard(item, 'complemento');
         document.getElementById('nuevo_expedido').value = valorOcultoResumenPersonaWizard(item, 'expedido');
         document.getElementById('nuevo_genero').value = valorOcultoResumenPersonaWizard(item, 'genero');
         document.getElementById('nuevo_ocupacion').value = valorOcultoResumenPersonaWizard(item, 'ocupacion');
         document.getElementById('nuevo_id_rol').value = valorOcultoResumenPersonaWizard(item, 'id_rol');
         document.getElementById('nuevo_estado_responsable').value = valorOcultoResumenPersonaWizard(item, 'estado') || 'ACTIVO';

         asignarFechaResponsableModal('nuevo_fecha_nacimiento', valorOcultoResumenPersonaWizard(item, 'fecha_nacimiento'));
         asignarFechaResponsableModal('nuevo_fecha_registro', valorOcultoResumenPersonaWizard(item, 'fecha_registro'));
         asignarFechaResponsableModal('nuevo_fecha_baja', valorOcultoResumenPersonaWizard(item, 'fecha_baja'));

         telefonosResponsableTemporal = sublistaResponsableDesdeItem(item, 'telefonos');
         rubrosResponsableTemporal = sublistaResponsableDesdeItem(item, 'rubros');

         document.getElementById('modalNuevoResponsable').dataset.urlRespaldoActual =
             valorOcultoResumenPersonaWizard(item, 'url_respaldo');
         mostrarRespaldoGuardadoResponsableModal(valorOcultoResumenPersonaWizard(item, 'url_respaldo'));

         if (idPersona) {
             prepararModoPersonaExistente();
             renderTelefonosResponsable(false);
             renderRubrosResponsable(false);
         } else {
             prepararModoPersonaNueva();
             renderTelefonosResponsable(false);
             renderRubrosResponsable(false);
         }
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

         let primerCampoError = null;

         if (idPersonaExistente === '') {
             primerCampoError = validarCampoResponsable(
                 'nuevo_nombres',
                 nombres,
                 'Ingrese los nombres del responsable.',
                 primerCampoError
             );
             primerCampoError = validarCampoResponsable(
                 'nuevo_apellido_paterno',
                 paterno,
                 'Ingrese el apellido paterno del responsable.',
                 primerCampoError
             );
             primerCampoError = validarCampoResponsable(
                 'nuevo_ci',
                 ci,
                 'Ingrese el CI del responsable.',
                 primerCampoError
             );
             primerCampoError = validarCampoResponsable(
                 'nuevo_correo',
                 correo,
                 'Ingrese el correo del responsable.',
                 primerCampoError
             );
             primerCampoError = validarCampoResponsable(
                 'nuevo_id_territorio',
                 idTerritorio,
                 'Seleccione el territorio del responsable.',
                 primerCampoError
             );
         }

         primerCampoError = validarCampoResponsable(
             'nuevo_id_rol',
             idRol,
             'Seleccione el rol o cargo del responsable.',
             primerCampoError
         );

         const respaldoEsPdf = !archivoRespaldo ||
             archivoRespaldo.type === 'application/pdf' ||
             archivoRespaldo.name.toLowerCase().endsWith('.pdf');

         if (!respaldoEsPdf) {
             primerCampoError = primerCampoError || mostrarErrorCampoResponsable(
                 'nuevo_url_respaldo',
                 'El respaldo del responsable debe ser un PDF.'
             );
         } else {
             limpiarErrorCampoResponsable('nuevo_url_respaldo');
         }

         const responsableDuplicado = encontrarResponsableEmpresaAgregado(
             idPersonaExistente,
             ci,
             responsableEditandoElemento
         );

         if (responsableDuplicado) {
             const campoDuplicado = idPersonaExistente ? 'modal_id_persona_responsable' : 'nuevo_ci';

             mostrarAvisoModalResponsable(
                 'Este responsable ya fue agregado a la empresa. Cierre este modal y use el boton Editar del responsable existente si necesita corregir datos.'
             );

             mostrarErrorCampoResponsable(
                 campoDuplicado,
                 'Este responsable ya existe en la lista de responsables.'
             );

             document.getElementById(campoDuplicado)?.focus?.();
             return;
         }

         if (primerCampoError) {
             primerCampoError.scrollIntoView({ behavior: 'smooth', block: 'center' });
             primerCampoError.focus?.();
             return;
         }

         const lista = document.getElementById('listaResponsablesEmpresa');
         document.getElementById('mensajeSinResponsables')?.remove();

         const nombreCompleto = `${nombres} ${paterno} ${materno}`.trim();
         const tipoResponsable = idPersonaExistente ? 'EXISTENTE' : 'NUEVO';
         const indiceDestino = responsableEditandoElemento ? responsableEditandoIndice : indiceResponsable;
         const item = responsableEditandoElemento || document.createElement('div');
         const archivoRespaldoAnterior = responsableEditandoElemento
             ? responsableEditandoElemento.querySelector('input[type="file"][name*="[archivo_respaldo]"]')
             : null;
         const urlRespaldoActual = document.getElementById('modalNuevoResponsable')?.dataset.urlRespaldoActual || '';

         item.className = 'responsable-agregado responsable-review-row';
         item.dataset.indiceResponsable = indiceDestino;

         let hiddenTelefonos = '';
         let hiddenRubros = '';

         telefonosResponsableTemporal.forEach((telefono, index) => {
             hiddenTelefonos += `
                <input type="hidden" name="responsables[${indiceDestino}][telefonos][${index}][numero]" value="${telefono.numero}">
                <input type="hidden" name="responsables[${indiceDestino}][telefonos][${index}][tipo]" value="${telefono.tipo}">
            `;
         });

         rubrosResponsableTemporal.forEach((rubro, index) => {
             hiddenRubros += `
                <input type="hidden" name="responsables[${indiceDestino}][rubros][${index}][nombre]" value="${rubro.nombre}">
                <input type="hidden" name="responsables[${indiceDestino}][rubros][${index}][estado]" value="${rubro.estado}">
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

         item.innerHTML = plantillaResponsableEmpresa({
             tipoResponsable,
             idPersonaExistente,
             domicilio,
             nit,
             correo,
             idTerritorio,
             nombres,
             paterno,
             materno,
             casado,
             ci,
             complemento,
             expedido,
             fechaNacimiento,
             genero,
             ocupacion,
             idRol,
             rolNombre,
             fechaRegistro,
             fechaBaja,
             estadoResponsable,
             nombreCompleto,
             urlRespaldo: urlRespaldoActual,
             indiceFormulario: indiceDestino,
             numeroVisible: responsableEditandoElemento
                 ? Number(item.querySelector('.responsables-review-number')?.textContent) || indiceDestino + 1
                 : indiceDestino + 1,
             telefonos: telefonosResponsableTemporal,
             rubros: rubrosResponsableTemporal,
         }, hiddenTelefonos, hiddenRubros, Boolean(archivoRespaldo));

         // Adjunta el PDF al responsable agregado para enviarlo junto al formulario final.
         if (archivoRespaldo) {
             if (typeof DataTransfer === 'undefined') {
                 mostrarErrorCampoResponsable('nuevo_url_respaldo', 'Su navegador no pudo adjuntar el PDF. Intente nuevamente.');
                 return;
             }

             const archivoTemporal = new DataTransfer();
             archivoTemporal.items.add(archivoRespaldo);

             const inputArchivo = document.createElement('input');
             inputArchivo.type = 'file';
             inputArchivo.name = `responsables[${indiceDestino}][archivo_respaldo]`;
             inputArchivo.className = 'hidden';
             inputArchivo.files = archivoTemporal.files;

             item.appendChild(inputArchivo);
         } else if (archivoRespaldoAnterior) {
             archivoRespaldoAnterior.name = `responsables[${indiceDestino}][archivo_respaldo]`;
             item.appendChild(archivoRespaldoAnterior);
         }

         if (!responsableEditandoElemento) {
             lista.appendChild(item);
             indiceResponsable++;
         }

         actualizarNumerosResponsablesEmpresa();

         responsableEditandoElemento = null;
         responsableEditandoIndice = null;

         cerrarModalResponsable();
         limpiarModalResponsable();
         refrescarResumenSiEstaEnRevisionPersonaWizard();
     }

     // Reconstruye un responsable ya agregado cuando la pagina vuelve con errores de validacion.
     // Esto evita perder lo guardado desde el modal si fallo un campo externo al modal.
     function rehidratarResponsablePersonaWizard(responsable) {
         const lista = document.getElementById('listaResponsablesEmpresa');
         if (!lista) return;

         document.getElementById('mensajeSinResponsables')?.remove();

         const tipoResponsable = responsable.tipo || 'EXISTENTE';
         const nombreCompleto = responsable.nombre_completo || [
             responsable.nombres,
             responsable.apellido_paterno,
             responsable.apellido_materno
         ].filter(Boolean).join(' ').trim() || 'Responsable agregado';

         let hiddenTelefonos = '';
         let hiddenRubros = '';

         (responsable.telefonos || []).forEach((telefono, index) => {
             hiddenTelefonos += `
                <input type="hidden" name="responsables[${indiceResponsable}][telefonos][${index}][numero]" value="${telefono.numero || ''}">
                <input type="hidden" name="responsables[${indiceResponsable}][telefonos][${index}][tipo]" value="${telefono.tipo || telefono.estado || 'CELULAR'}">
            `;
         });

         (responsable.rubros || []).forEach((rubro, index) => {
             hiddenRubros += `
                <input type="hidden" name="responsables[${indiceResponsable}][rubros][${index}][nombre]" value="${rubro.nombre || ''}">
                <input type="hidden" name="responsables[${indiceResponsable}][rubros][${index}][estado]" value="${rubro.estado || 'ACTIVO'}">
            `;
         });

         const item = document.createElement('div');

         item.className = 'responsable-agregado responsable-review-row';

         item.innerHTML = `
            <span class="font-medium text-gray-700">${nombreCompleto}</span>

            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                ${tipoResponsable === 'EXISTENTE'
                    ? 'bg-blue-100 text-blue-700'
                    : 'bg-green-100 text-green-700'}">
                ${tipoResponsable}
            </span>

            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-700">
                ${responsable.rol_nombre || responsable.rol || ''}
            </span>

            ${responsable.url_respaldo ? `
                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                    PDF
                </span>
            ` : ''}

            <button type="button"
                onclick="quitarResponsableEmpresa(this)"
                class="text-red-500 hover:text-red-700 text-base font-bold leading-none">
                x
            </button>

            <input type="hidden" name="responsables[${indiceResponsable}][tipo]" value="${tipoResponsable}">
            <input type="hidden" name="responsables[${indiceResponsable}][id_persona]" value="${responsable.id_persona || ''}">

            <input type="hidden" name="responsables[${indiceResponsable}][domicilio]" value="${responsable.domicilio || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][nit]" value="${responsable.nit || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][correo]" value="${responsable.correo || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][id_territorio]" value="${responsable.id_territorio || ''}">

            <input type="hidden" name="responsables[${indiceResponsable}][nombres]" value="${responsable.nombres || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][apellido_paterno]" value="${responsable.apellido_paterno || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][apellido_materno]" value="${responsable.apellido_materno || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][apellido_casado]" value="${responsable.apellido_casado || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][ci]" value="${responsable.ci || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][complemento]" value="${responsable.complemento || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][expedido]" value="${responsable.expedido || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][fecha_nacimiento]" value="${responsable.fecha_nacimiento || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][genero]" value="${responsable.genero || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][ocupacion]" value="${responsable.ocupacion || ''}">

            <input type="hidden" name="responsables[${indiceResponsable}][id_rol]" value="${responsable.id_rol || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][url_respaldo]" value="${responsable.url_respaldo || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][fecha_registro]" value="${responsable.fecha_registro || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][fecha_baja]" value="${responsable.fecha_baja || ''}">
            <input type="hidden" name="responsables[${indiceResponsable}][estado]" value="${responsable.estado || 'ACTIVO'}">

            ${hiddenTelefonos}
            ${hiddenRubros}
        `;

         item.innerHTML = plantillaResponsableEmpresa({
             tipoResponsable,
             idPersonaExistente: responsable.id_persona || '',
             domicilio: responsable.domicilio || '',
             nit: responsable.nit || '',
             correo: responsable.correo || '',
             idTerritorio: responsable.id_territorio || '',
             nombres: responsable.nombres || '',
             paterno: responsable.apellido_paterno || '',
             materno: responsable.apellido_materno || '',
             casado: responsable.apellido_casado || '',
             ci: responsable.ci || '',
             complemento: responsable.complemento || '',
             expedido: responsable.expedido || '',
             fechaNacimiento: responsable.fecha_nacimiento || '',
             genero: responsable.genero || '',
             ocupacion: responsable.ocupacion || '',
             idRol: responsable.id_rol || '',
             rolNombre: responsable.rol_nombre || responsable.rol || '',
             fechaRegistro: responsable.fecha_registro || '',
             fechaBaja: responsable.fecha_baja || '',
             estadoResponsable: responsable.estado || 'ACTIVO',
             nombreCompleto,
             urlRespaldo: responsable.url_respaldo || '',
             telefonos: responsable.telefonos || [],
             rubros: responsable.rubros || [],
         }, hiddenTelefonos, hiddenRubros, Boolean(responsable.url_respaldo));

         lista.appendChild(item);
         actualizarNumerosResponsablesEmpresa();
         indiceResponsable++;
         refrescarResumenSiEstaEnRevisionPersonaWizard();
     }

     function quitarResponsableEmpresa(boton) {
         boton.closest('.responsable-agregado').remove();

         const lista = document.getElementById('listaResponsablesEmpresa');

         if (lista.children.length === 0) {
             lista.innerHTML = `
                <span id="mensajeSinResponsables" class="responsables-review-empty">
                    Todavía no se agregaron responsables.
                </span>
            `;
         }

         actualizarNumerosResponsablesEmpresa();
         refrescarResumenSiEstaEnRevisionPersonaWizard();
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

     // Obtiene un nombre legible desde una URL guardada para mostrarlo en el control PDF.
     function nombreArchivoResponsableDesdeUrl(url) {
         const texto = String(url || '').split('?')[0].split('#')[0];
         const nombre = texto.split('/').pop();

         return nombre ? decodeURIComponent(nombre) : 'PDF guardado';
     }

     // Actualiza el texto y el estado de los botones compactos del respaldo PDF.
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

     // Libera el URL temporal creado por el navegador cuando se selecciona otro archivo.
     function liberarPdfTemporalResponsableModal() {
         if (pdfTemporalResponsableModal) {
             URL.revokeObjectURL(pdfTemporalResponsableModal);
             pdfTemporalResponsableModal = null;
         }
     }

     // Reinicia el control PDF sin alterar otros campos del responsable.
     function limpiarRespaldoResponsableModal(mensaje = 'Seleccione un respaldo PDF si corresponde.') {
         const inputPdf = document.getElementById('nuevo_url_respaldo');
         const modal = document.getElementById('modalNuevoResponsable');

         if (inputPdf) inputPdf.value = '';
         if (modal) modal.dataset.urlRespaldoActual = '';

         liberarPdfTemporalResponsableModal();
         actualizarVistaRespaldoResponsableModal('Sin PDF seleccionado', mensaje, '', false);
         limpiarErrorCampoResponsable('nuevo_url_respaldo');
     }

     // Carga en el control compacto el PDF guardado de un responsable que se esta editando.
     function mostrarRespaldoGuardadoResponsableModal(url) {
         liberarPdfTemporalResponsableModal();

         if (!url) {
             actualizarVistaRespaldoResponsableModal(
                 'Sin PDF seleccionado',
                 'Seleccione un respaldo PDF si corresponde.',
                 '',
                 false
             );
             return;
         }

         actualizarVistaRespaldoResponsableModal(
             nombreArchivoResponsableDesdeUrl(url),
             'PDF guardado actualmente.',
             url,
             true
         );
     }

     function limpiarModalResponsable() {
         limpiarErroresResponsableModal();
         limpiarAvisoModalResponsable();
         limpiarDatosPersonaResponsable();
         limpiarListasResponsableModal();

         const modal = document.getElementById('modalNuevoResponsable');
         if (modal) modal.dataset.urlRespaldoActual = '';

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

     // Limpia el error debajo del campo apenas el usuario vuelve a escribir o seleccionar.
     [
         'numeroTelefono',
         'modal_id_persona_responsable',
         'nuevo_nombres',
         'nuevo_apellido_paterno',
         'nuevo_ci',
         'nuevo_correo',
         'nuevo_id_territorio',
         'nuevo_id_rol',
         'nuevo_telefono',
         'nuevo_rubro',
         'nuevo_url_respaldo',
     ].forEach(idCampo => {
         const campo = document.getElementById(idCampo);

         const limpiarValidacionCampo = () => {
             limpiarErrorCampoResponsable(idCampo);

             if (idCampo === 'modal_id_persona_responsable' || idCampo.startsWith('nuevo_')) {
                 limpiarAvisoModalResponsable();
             }
         };

         campo?.addEventListener('input', limpiarValidacionCampo);
         campo?.addEventListener('change', limpiarValidacionCampo);
     });

     // Recupera responsables agregados si el formulario vuelve con errores de validacion.
     if (typeof tieneErroresServidorPersona !== 'undefined' && tieneErroresServidorPersona) {
         responsablesOldPersonaWizard.forEach(responsable => {
             rehidratarResponsablePersonaWizard(responsable);
         });
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

         // Valida PDF antes de habilitar vista previa/guardado.
         const esPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
         if (!esPdf) {
             mostrarErrorCampoResponsable('nuevo_url_respaldo', 'Solo se permiten archivos PDF.');
             e.target.value = '';
             return;
         }

         limpiarErrorCampoResponsable('nuevo_url_respaldo');
         liberarPdfTemporalResponsableModal();

         const modalResponsable = document.getElementById('modalNuevoResponsable');
         if (modalResponsable) modalResponsable.dataset.urlRespaldoActual = '';

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
