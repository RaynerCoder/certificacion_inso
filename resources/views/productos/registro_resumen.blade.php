@php
    $unidadRegistro = trim((string) $registro->catalogoUnidad?->nombre);
    $abreviaturaRegistro = trim((string) $registro->catalogoUnidad?->abreviatura);
    $unidadRegistro = $unidadRegistro
        ? ($abreviaturaRegistro ? $unidadRegistro . ' (' . $abreviaturaRegistro . ')' : $unidadRegistro)
        : 'No registrada';
    $fechaRegistro = $registro->fecha_vigencia
        ? \Illuminate\Support\Carbon::parse($registro->fecha_vigencia)->format('d/m/Y')
        : 'No registrada';
@endphp

<article class="producto-show-register-item">
    <header class="producto-show-register-item-head">
        <div class="producto-show-register-title">
            <span><i class="fa-regular fa-file-lines"></i></span>
            <div>
                <h5>Registro {{ $numeroRegistro }}</h5>
                <p>{{ $subtituloRegistro }}</p>
            </div>
        </div>
        <span class="producto-show-status {{ $registro->estado === 'ACTIVO' ? '' : 'is-inactive' }}">{{ $registro->estado ?: 'SIN ESTADO' }}</span>
    </header>
    <dl class="producto-show-register-data">
        <div><dt>Código de autorización</dt><dd>{{ $registro->codigo_autorizacion ?: 'No registrado' }}</dd></div>
        <div><dt>Número de lote</dt><dd>{{ $registro->numero_lote ?: 'No registrado' }}</dd></div>
        <div><dt>Fecha de vigencia</dt><dd>{{ $fechaRegistro }}</dd></div>
        <div><dt>Cantidad registrada</dt><dd>{{ $formatearCantidad($registro->cantidad) }}</dd></div>
        <div><dt>Unidad</dt><dd>{{ $unidadRegistro }}</dd></div>
        <div><dt>Certificados vinculados</dt><dd>{{ $registro->certificadosRegistros->count() }}</dd></div>
    </dl>
</article>
