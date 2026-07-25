<x-filament::button
    color="gray"
    icon="heroicon-o-academic-cap"
    size="sm"
    data-tour-id="welcome-tour-dashboard"
    x-on:click="Livewire.dispatch('capell-welcome-tour::restart')"
>
    {{ __('capell-welcome-tour::welcome_tour.restart') }}
</x-filament::button>
