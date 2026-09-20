<x-filament-panels::page>
    <div class="space-y-6">

        <x-filament::section>
            <x-slot name="heading">
                Aktive Sitzungen
            </x-slot>

            <x-slot name="description">
                Geräte und Browser, auf denen dein Konto aktuell angemeldet ist.
            </x-slot>

            <div class="space-y-4">
                @foreach ($this->getSessions() as $session)
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div>
                            <div class="font-medium">
                                {{ $session->user_agent ?: 'Unbekanntes Gerät' }}
                            </div>

                            <div class="text-sm text-gray-500">
                                IP:
                                {{ $session->ip_address ?? 'unbekannt' }}
                            </div>

                            <div class="text-sm text-gray-500">
                                Zuletzt aktiv:
                                {{
                                    \Carbon\CarbonImmutable::createFromTimestamp(
                                        $session->last_activity
                                    )->diffForHumans()
                                }}
                            </div>
                        </div>

                        @if (
                            $this->isCurrentSession(
                                $session->id
                            )
                        )
                            <x-filament::badge
                                color="success"
                            >
                                Aktuelles Gerät
                            </x-filament::badge>
                        @endif
                        @if (
                            ! $this->isCurrentSession(
                                $session->id
                            )
                        )
                            <x-filament::button
                                size="sm"
                                color="danger"
                                wire:click="
                                revokeSession(
                                    '{{ $session->id }}'
                                )
                            "
                            >
                                Abmelden
                            </x-filament::button>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
