<div
    x-data="{ suppressDismiss: false }"
    x-init="
        const controller = new AbortController();
        let stopWaitingForTourElements = () => {};
        const dismiss = () => { if (! $data.suppressDismiss) Livewire.dispatch('capell-welcome-tour::dismiss'); };
        const targetSelector = @js($currentTargetSelector);
        const targetSelectors = @js($targetSelectors ?? []);
        const tourIdToOpen = @js($tourIdToOpen);
        const targetsAvailable = () => {
            try {
                return (targetSelectors.length ? targetSelectors : [targetSelector]).filter(Boolean).every((selector) => document.querySelector(selector));
            } catch {
                return false;
            }
        };
        if (tourIdToOpen) stopWaitingForTourElements = Livewire.on('filament-tour::loaded-elements', ({ tours = [] }) => {
            if (! tours.some((tour) => tour.id === `tour_${tourIdToOpen}`)) return;
            stopWaitingForTourElements();
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (! targetsAvailable()) {
                    $data.suppressDismiss = true;
                    Livewire.dispatch('capell-welcome-tour::target-unavailable');
                    document.querySelector('.driver-popover-close-btn')?.click();
                    return;
                }
                queueMicrotask(() => Livewire.dispatch('filament-tour::open-tour', { id: tourIdToOpen }));
            }));
        });
        document.addEventListener('click', (event) => {
            if (event.target.closest('.driver-popover-close-btn')) dismiss();
        }, { capture: true, signal: controller.signal });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && document.body.classList.contains('driver-active')) dismiss();
        }, { signal: controller.signal });
        document.addEventListener('livewire:navigating', () => {
            stopWaitingForTourElements();
            controller.abort();
        }, { once: true, signal: controller.signal });
    "
>
    @if ($tourIdToOpen)
        <div
            class="flex flex-wrap items-center gap-3"
            role="status"
            data-tour-progress
        >
            @if ($isPreview ?? false)
                <span
                    >{{ __('capell-welcome-tour::welcome_tour.preview_notice') }}</span
                >
            @endif
            <x-filament::button
                type="button"
                color="gray"
                wire:click="snooze"
                x-on:click="suppressDismiss = true; document.querySelector('.driver-popover-close-btn')?.click()"
            >
                {{ __('capell-welcome-tour::welcome_tour.snooze_tour') }}
            </x-filament::button>
        </div>
    @endif
</div>
