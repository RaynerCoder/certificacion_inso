@php
    $qr = $tipoCertificado->configuracionQr();
    $camposQr = array_values(old('form_qr_campos', $qr['campos'] ?? []));
@endphp
<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" data-qr-configuracion>
    <h2 class="text-lg font-bold text-slate-800">Verificación mediante QR</h2>
    <p class="mt-1 text-sm text-slate-600">Elija los datos públicos, sus etiquetas y el orden en que aparecerán al escanear el certificado.</p>
    <input type="hidden" name="form_qr_habilitado" value="0">
    <label class="my-4 flex items-center gap-2 font-semibold text-slate-700">
        <input type="checkbox" name="form_qr_habilitado" value="1" @checked(old('form_qr_habilitado', $qr['habilitado'] ?? false))>
        Habilitar verificación mediante QR
    </label>
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 p-3">
        <p class="text-sm text-slate-600" data-qr-resumen>{{ count($camposQr) }} datos públicos seleccionados</p>
        <button type="button" data-qr-abrir class="rounded-lg border border-teal-700 px-4 py-2 text-sm font-semibold text-teal-800">Configurar datos del QR</button>
    </div>
    <dialog data-qr-modal aria-labelledby="titulo-configuracion-qr"
        class="m-auto max-h-[85vh] w-[min(960px,95vw)] rounded-2xl border-0 p-0 shadow-xl backdrop:bg-slate-900/50">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">
            <h3 id="titulo-configuracion-qr" class="text-lg font-bold text-slate-800">Datos públicos del QR</h3>
            <button type="button" data-qr-cerrar aria-label="Cerrar configuración del QR" class="rounded-lg px-3 py-2 text-slate-600">Cerrar</button>
        </div>
        <div class="space-y-4 overflow-y-auto px-6 py-4">
    @error('form_qr_campos') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    @foreach ($errors->get('form_qr_campos.*') as $mensajes)
        @foreach ($mensajes as $mensaje) <p class="mt-2 text-sm text-red-600">{{ $mensaje }}</p> @endforeach
    @endforeach
    @error('form_qr_titulo') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
    <label class="block text-sm font-medium text-slate-700">Título de la página pública
        <input type="text" name="form_qr_titulo" maxlength="120" value="{{ old('form_qr_titulo', $qr['titulo'] ?? 'Verificación de certificado') }}" class="my-2 block w-full rounded-lg border-slate-300">
    </label>
    <div data-qr-filas class="space-y-3">
        @foreach ($camposQr as $indice => $fila)
            @include('tipos_certificados.fila_qr', compact('indice', 'fila'))
        @endforeach
    </div>
    <template data-qr-fila-nueva>
        @include('tipos_certificados.fila_qr', ['indice' => 0, 'fila' => ['campo' => '', 'etiqueta' => '']])
    </template>
    <button type="button" data-qr-agregar class="my-4 rounded-lg bg-teal-700 px-4 py-2 font-semibold text-white">Agregar dato público</button>
    <p class="text-sm text-slate-600">Puede preparar los campos antes de activar el QR. En la plantilla, marque la imagen que se reemplazará por el QR real. Los datos elegidos también se aplican al consultar certificados ya emitidos.</p>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-6 py-4">
            <p class="text-sm text-slate-500">La configuración se guarda con «Guardar cambios» en la página principal.</p>
            <button type="button" data-qr-cerrar class="rounded-lg bg-teal-700 px-5 py-2 font-semibold text-white">Listo</button>
        </div>
    </dialog>
</section>
@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.querySelector('[data-qr-configuracion]');
        if (!panel || panel.dataset.iniciado) return;
        panel.dataset.iniciado = '1';
        const lista = panel.querySelector('[data-qr-filas]');
        const agregar = panel.querySelector('[data-qr-agregar]');
        const modal = panel.querySelector('[data-qr-modal]');
        panel.querySelector('[data-qr-abrir]').addEventListener('click', () => modal.showModal());
        panel.querySelectorAll('[data-qr-cerrar]').forEach(boton => boton.addEventListener('click', () => modal.close()));
        modal.addEventListener('keydown', evento => {
            if (evento.key === 'Enter' && evento.target.matches('input')) evento.preventDefault();
        });
        function actualizar() {
            const filas = [...lista.querySelectorAll('[data-qr-fila]')];
            const usados = filas.map(fila => fila.querySelector('[data-qr-campo]').value);
            filas.forEach((fila, indice) => {
                fila.querySelector('[data-qr-campo]').name = `form_qr_campos[${indice}][campo]`;
                fila.querySelector('[data-qr-etiqueta]').name = `form_qr_campos[${indice}][etiqueta]`;
                fila.querySelector('[data-qr-subir]').disabled = indice === 0;
                fila.querySelector('[data-qr-bajar]').disabled = indice === filas.length - 1;
                [...fila.querySelector('[data-qr-campo]').options].forEach(opcion => {
                    opcion.disabled = opcion.value !== usados[indice] && usados.includes(opcion.value);
                });
            });
            agregar.disabled = filas.length >= {{ count(\App\Models\TipoCertificado::CAMPOS_QR) }};
            const etiquetas = filas.map(fila => fila.querySelector('[data-qr-etiqueta]').value.trim()).filter(Boolean);
            panel.querySelector('[data-qr-resumen]').textContent = `${filas.length} datos públicos: ${etiquetas.slice(0, 3).join(', ')}${etiquetas.length > 3 ? '…' : ''}`;
        }
        panel.addEventListener('click', function (evento) {
            const boton = evento.target.closest('button');
            if (!boton) return;
            if (boton === agregar) {
                const fila = panel.querySelector('[data-qr-fila-nueva]').content.firstElementChild.cloneNode(true);
                const usados = [...lista.querySelectorAll('[data-qr-campo]')].map(select => select.value);
                const select = fila.querySelector('[data-qr-campo]');
                const opcion = [...select.options].find(item => !usados.includes(item.value));
                if (!opcion) return;
                select.value = opcion.value;
                fila.querySelector('[data-qr-etiqueta]').value = opcion.textContent;
                lista.append(fila);
            } else {
                const fila = boton.closest('[data-qr-fila]');
                if (!fila) return;
                if (boton.hasAttribute('data-qr-quitar')) fila.remove();
                if (boton.hasAttribute('data-qr-subir') && fila.previousElementSibling) lista.insertBefore(fila, fila.previousElementSibling);
                if (boton.hasAttribute('data-qr-bajar') && fila.nextElementSibling) lista.insertBefore(fila.nextElementSibling, fila);
            }
            actualizar();
        });
        lista.addEventListener('change', actualizar);
        lista.addEventListener('input', actualizar);
        actualizar();
        @if ($errors->has('form_qr_campos') || $errors->has('form_qr_campos.*') || $errors->has('form_qr_titulo'))
            modal.showModal();
        @endif
    });
</script>
@endpush
