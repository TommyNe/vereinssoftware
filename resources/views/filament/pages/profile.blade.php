<x-filament-panels::page>

    <form
        wire:submit="saveProfile"
        class="space-y-6"
    >
        {{ $this->form }}

        <x-filament::button
            type="submit"
        >
            Profil speichern
        </x-filament::button>
    </form>

</x-filament-panels::page>
