<x-form-section submit="updatePassword" :without-header="true" class="perfil-password-form">
    <x-slot name="form">
        <div class="col-span-6 lg:col-span-2">
            <x-label for="current_password" value="Contraseña actual" />
            <x-input id="current_password" type="password" class="perfil-password-input mt-1 block w-full"
                wire:model="state.current_password" autocomplete="current-password" />
            <x-input-error for="current_password" class="mt-2" />
        </div>

        <div class="col-span-6 md:col-span-3 lg:col-span-2">
            <x-label for="password" value="Nueva contraseña" />
            <x-input id="password" type="password" class="perfil-password-input mt-1 block w-full"
                wire:model="state.password" autocomplete="new-password" />
            <x-input-error for="password" class="mt-2" />
        </div>

        <div class="col-span-6 md:col-span-3 lg:col-span-2">
            <x-label for="password_confirmation" value="Confirmar nueva contraseña" />
            <x-input id="password_confirmation" type="password" class="perfil-password-input mt-1 block w-full"
                wire:model="state.password_confirmation" autocomplete="new-password" />
            <x-input-error for="password_confirmation" class="mt-2" />
        </div>

        <div class="col-span-6">
            <p class="text-xs leading-5 text-slate-500">
                La contraseña debe tener al menos 8 caracteres. No utilice nuevamente su CI como contraseña permanente.
            </p>
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            Contraseña actualizada correctamente.
        </x-action-message>

        <x-button class="!bg-emerald-700 hover:!bg-emerald-800 focus:!bg-emerald-800 focus:!ring-emerald-500">
            Guardar contraseña
        </x-button>
    </x-slot>
</x-form-section>
