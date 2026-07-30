<x-filament-widgets::widget>
    @if ($this->shouldShowCallout())
        <x-filament::section class="mb-6">
            <x-slot name="heading">
                {{ __('capell-welcome-tour::welcome_tour.callout_heading') }}
            </x-slot>
            <x-slot name="description">
                {{ __('capell-welcome-tour::welcome_tour.callout_description') }}
            </x-slot>

            <div class="flex flex-wrap gap-3">
                <x-filament::button
                    wire:click="startTour"
                    icon="heroicon-o-play"
                >
                    {{ __('capell-welcome-tour::welcome_tour.take_tour') }}
                </x-filament::button>
                <x-filament::button
                    wire:click="dismissTourCallout"
                    color="gray"
                >
                    {{ __('capell-welcome-tour::welcome_tour.not_now') }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif

    @if ($this->shouldShowChecklist())
        <x-filament::section>
            <x-slot name="heading">
                {{ __('capell-welcome-tour::welcome_tour.checklist_heading') }}
            </x-slot>

            <x-slot name="description">
                {{ __('capell-welcome-tour::welcome_tour.checklist_description', ['completed' => $this->completedCount(), 'total' => count($this->items())]) }}
            </x-slot>

            <div
                id="capell-welcome-tour-checklist"
                data-tour-id="welcome-tour-checklist"
                class="space-y-3"
            >
                @foreach ($this->items() as $item)
                    <div
                        class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div
                            @class([
                            'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                            'border-success-500 bg-success-50 text-success-700 dark:bg-success-950 dark:text-success-300' => $item->complete,
                            'border-gray-300 bg-gray-50 text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300' => ! $item->complete,
                        ])
                        >
                            {{ $item->complete ? '✓' : '•' }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div
                                class="text-sm font-medium text-gray-950 dark:text-white"
                            >
                                {{ $item->label }}
                            </div>

                            <div
                                class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ $item->explanation !== '' ? $item->explanation : $item->description }}
                            </div>

                            @if ($item->url !== null)
                                <a
                                    href="{{ $item->url }}"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400 mt-2 inline-flex text-sm font-medium"
                                >
                                    {{ __('capell-welcome-tour::welcome_tour.checklist_open') }}
                                </a>
                            @endif

                            @if (! $item->complete || $item->manuallyCompleted)
                                <button
                                    type="button"
                                    wire:click="setChecklistItemCompletion(@js($item->key), {{ $item->manuallyCompleted ? 'false' : 'true' }})"
                                    class="text-primary-600 hover:text-primary-500 dark:text-primary-400 mt-2 block text-sm font-medium"
                                >
                                    {{ __($item->manuallyCompleted ? 'capell-welcome-tour::welcome_tour.checklist_mark_incomplete' : 'capell-welcome-tour::welcome_tour.checklist_mark_complete') }}
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap gap-3">
                <x-filament::button
                    wire:click="startTour"
                    color="gray"
                    icon="heroicon-o-arrow-path"
                >
                    {{ __('capell-welcome-tour::welcome_tour.restart_tour') }}
                </x-filament::button>
                <x-filament::button
                    wire:click="dismissChecklist"
                    color="gray"
                >
                    {{ __('capell-welcome-tour::welcome_tour.dismiss_checklist') }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
