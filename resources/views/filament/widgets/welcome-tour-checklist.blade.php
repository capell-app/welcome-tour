<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('capell-welcome-tour::welcome_tour.checklist_heading') }}
        </x-slot>

        <x-slot name="description">
            {{ __('capell-welcome-tour::welcome_tour.checklist_description', ['completed' => $this->completedCount(), 'total' => count($this->items())]) }}
        </x-slot>

        <div class="space-y-3">
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
                            {{ $item->description }}
                        </div>

                        @if ($item->url !== null)
                            <a
                                href="{{ $item->url }}"
                                class="text-primary-600 hover:text-primary-500 dark:text-primary-400 mt-2 inline-flex text-sm font-medium"
                            >
                                {{ __('capell-welcome-tour::welcome_tour.checklist_open') }}
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
