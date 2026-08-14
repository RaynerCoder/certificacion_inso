<div class="pagos-table-actions">
    <x-wire-button href="{{ route('pagos_show', $pago) }}" green xs>
        Ver
    </x-wire-button>
    @if (auth()->user()?->puede('pagos.validar'))
        <x-wire-button href="{{ route('pagos_edit', $pago) }}" blue xs>
            Editar
        </x-wire-button>
    @endif
</div>
