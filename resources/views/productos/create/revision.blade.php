{{-- Paso 4: revision completa antes de guardar producto. --}}
<div class="producto-step" data-producto-step="3">
    <section class="producto-section">
        <div class="producto-review-intro">
            <h3>Resumen antes de guardar</h3>
            <p>Verifique los datos que se registraran en productos, ingredientes, presentaciones y registros.</p>
        </div>

        {{-- El resumen se arma con JavaScript leyendo los mismos inputs que se enviaran al controlador. --}}
        <div id="resumenProductoWizard" class="producto-review-grid"></div>
    </section>
</div>
