<div
    class="space-y-4"
    data-tour-settings-summary
>
    <p class="text-sm text-gray-600 dark:text-gray-400">
        {{ __('capell-welcome-tour::welcome_tour.summary_helper') }} {{ __($summary->eligible ? 'capell-welcome-tour::welcome_tour.eligible' : 'capell-welcome-tour::welcome_tour.ineligible') }}
    </p>
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <h3 class="font-semibold">
                {{ __('capell-welcome-tour::welcome_tour.checklist_heading') }}
            </h3>
            <ul class="mt-2 space-y-2">
                @forelse ($summary->checklist as $item)
                    <li>
                        <span>{{ $item->label }}</span>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $item->explanation }}</p>
                    </li>
                @empty
                    <li>
                        {{ __('capell-welcome-tour::welcome_tour.no_checklist') }}
                    </li>
                @endforelse
            </ul>
        </div>
        <div>
            <h3 class="font-semibold">
                {{ __('capell-welcome-tour::welcome_tour.chapters_heading') }}
            </h3>
            <ol class="mt-2 space-y-2">
                @forelse ($summary->chapters as $chapter)
                    <li>
                        @foreach ($chapter->steps as $step)
                            <p>{{ value($step->title) }}</p>
                        @endforeach
                    </li>
                @empty
                    <li data-tour-empty>
                        {{ __('capell-welcome-tour::welcome_tour.no_steps') }}
                    </li>
                @endforelse
            </ol>
        </div>
    </div>
    <div class="flex flex-wrap gap-3">
        <x-filament::button
            type="button"
            wire:click="preview"
            wire:loading.attr="disabled"
            :disabled="$summary->chapters === []"
        >
            {{ __('capell-welcome-tour::welcome_tour.preview_as_me') }}
        </x-filament::button>
        <x-filament::button
            type="button"
            color="gray"
            wire:click="restart"
            wire:loading.attr="disabled"
            :disabled="$summary->chapters === []"
        >
            {{ __('capell-welcome-tour::welcome_tour.restart_my_tour') }}
        </x-filament::button>
    </div>
    @error('tour')
        <p role="alert">{{ $message }}</p>
    @enderror
</div>
