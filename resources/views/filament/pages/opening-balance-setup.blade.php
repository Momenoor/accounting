<x-filament-panels::page>
    <x-filament-panels::form :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
                             wire:submit.prevent="save">
        {{$this->form}}

        <x-filament-panels::form.actions
            :actions="$this->getFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page>
