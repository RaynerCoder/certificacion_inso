<div data-qr-fila class="grid gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-3">
    <label class="text-sm">Campo del sistema
        <select data-qr-campo name="form_qr_campos[{{ $indice }}][campo]" class="mt-1 block w-full rounded-lg border-slate-300">
            @foreach (\App\Models\TipoCertificado::CAMPOS_QR as $codigo => $nombre)
                <option value="{{ $codigo }}" @selected(($fila['campo'] ?? '') === $codigo)>{{ $nombre }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm">Etiqueta pública
        <input data-qr-etiqueta type="text" name="form_qr_campos[{{ $indice }}][etiqueta]"
            value="{{ $fila['etiqueta'] ?? '' }}" maxlength="100" class="mt-1 block w-full rounded-lg border-slate-300">
    </label>
    <div class="flex items-end gap-2">
        <button type="button" data-qr-subir class="rounded-lg border px-3 py-2 disabled:opacity-40" aria-label="Subir campo">Subir</button>
        <button type="button" data-qr-bajar class="rounded-lg border px-3 py-2 disabled:opacity-40" aria-label="Bajar campo">Bajar</button>
        <button type="button" data-qr-quitar class="rounded-lg border px-3 py-2 text-red-700">Quitar</button>
    </div>
</div>
