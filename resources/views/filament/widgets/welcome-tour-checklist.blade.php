<x-filament-widgets::widget>
    @if ($this->shouldShowChecklist())
        <x-filament::section
            compact
            collapsible
        >
            <x-slot name="heading">
                {{ __('capell-welcome-tour::welcome_tour.checklist_heading') }}
            </x-slot>

            <x-slot name="description">
                {{ __('capell-welcome-tour::welcome_tour.checklist_description', ['completed' => $this->completedCount(), 'total' => count($this->items())]) }}
            </x-slot>

            <ul
                id="capell-welcome-tour-checklist"
                data-tour-id="welcome-tour-checklist"
                class="divide-y divide-gray-100 dark:divide-white/10"
            >
                @foreach ($this->items() as $item)
                    <li class="flex items-start gap-3 py-2.5 first:pt-0">
                        <div
                            @class([
                            'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
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
                                class="text-sm text-gray-500 dark:text-gray-400"
                            >
                                {{ $item->explanation !== '' ? $item->explanation : $item->description }}
                            </div>

                            <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1">
                                @if ($item->url !== null)
                                    <a
                                        href="{{ $item->url }}"
                                        class="text-primary-600 hover:text-primary-500 dark:text-primary-400 text-sm font-medium"
                                    >
                                        {{ __('capell-welcome-tour::welcome_tour.checklist_open') }}
                                    </a>
                                @endif

                                @if (! $item->complete || $item->manuallyCompleted)
                                    <button
                                        type="button"
                                        wire:click="setChecklistItemCompletion(@js($item->key), {{ $item->manuallyCompleted ? 'false' : 'true' }})"
                                        class="text-primary-600 hover:text-primary-500 dark:text-primary-400 text-sm font-medium"
                                    >
                                        {{ __($item->manuallyCompleted ? 'capell-welcome-tour::welcome_tour.checklist_mark_incomplete' : 'capell-welcome-tour::welcome_tour.checklist_mark_complete') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <div class="mt-3 flex flex-wrap gap-3">
                @if ($this->hasTourSteps())
                    <x-filament::button
                        wire:click="startTour"
                        color="gray"
                        size="sm"
                        icon="heroicon-o-arrow-path"
                    >
                        {{ __('capell-welcome-tour::welcome_tour.restart_tour') }}
                    </x-filament::button>
                @endif
                <x-filament::button
                    wire:click="dismissChecklist"
                    color="gray"
                    size="sm"
                >
                    {{ __('capell-welcome-tour::welcome_tour.dismiss_checklist') }}
                </x-filament::button>
            </div>
        </x-filament::section>
    @endif
</x-filament-widgets::widget>
