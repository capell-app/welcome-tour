<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Concerns;

use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Events\WelcomeTourCompleted;
use Capell\WelcomeTour\Events\WelcomeTourStarted;
use Capell\WelcomeTour\Support\ContextualWelcomeTourRegistry;
use Capell\WelcomeTour\Support\WelcomeTourStepFactory;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\FilamentTour\Tour\HasTour;
use JibayMcs\FilamentTour\Tour\Tour;
use Livewire\Attributes\On;

trait HasContextualWelcomeTour
{
    use HasTour;

    private const string CONTEXTUAL_DISMISS_EVENT = 'capell-welcome-tour::contextual-dismiss';

    private const string CONTEXTUAL_STEP_COMPLETED_EVENT = 'capell-welcome-tour::contextual-step-completed';

    /**
     * @return array<int, Tour>
     */
    public function tours(): array
    {
        $user = auth()->user();
        $tourKey = $this->welcomeTourKey();

        if (! $user instanceof Model || CanShowWelcomeTourAction::run($user, $tourKey) !== true) {
            return [];
        }

        $tourSteps = ResolveWelcomeTourStepsForUserAction::run($user, resolve(ContextualWelcomeTourRegistry::class)->stepsFor($tourKey), $tourKey);

        if ($tourSteps === []) {
            return [];
        }

        $steps = array_map(WelcomeTourStepFactory::make(...), $tourSteps);

        event(new WelcomeTourStarted($user, $tourKey));

        foreach ($tourSteps as $index => $tourStep) {
            $eventName = $index === array_key_last($tourSteps)
                ? self::CONTEXTUAL_DISMISS_EVENT
                : self::CONTEXTUAL_STEP_COMPLETED_EVENT;

            $steps[$index]->dispatchOnNext($eventName, stepKey: $tourStep->key, tourKey: $tourKey);
        }

        return [
            Tour::make($tourKey)
                ->route('/' . trim(request()->path(), '/'))
                ->nextButtonLabel(__('capell-admin::button.next'))
                ->previousButtonLabel(__('capell-admin::button.previous'))
                ->doneButtonLabel(__('capell-admin::button.done'))
                ->steps(...$steps),
        ];
    }

    #[On(self::CONTEXTUAL_STEP_COMPLETED_EVENT)]
    public function recordContextualWelcomeTourStep(string $stepKey, string $tourKey): void
    {
        $user = auth()->user();

        if ($user instanceof Model && $tourKey === $this->welcomeTourKey()
            && collect(resolve(ContextualWelcomeTourRegistry::class)->stepsFor($tourKey))->contains('key', $stepKey)) {
            RecordWelcomeTourStepAction::run($user, $stepKey, $tourKey);
        }
    }

    #[On(self::CONTEXTUAL_DISMISS_EVENT)]
    public function dismissContextualWelcomeTour(?string $stepKey = null, ?string $tourKey = null): void
    {
        $user = auth()->user();
        $tourKey ??= $this->welcomeTourKey();

        if (! $user instanceof Model || $tourKey !== $this->welcomeTourKey()) {
            return;
        }

        if (is_string($stepKey) && $stepKey !== '') {
            $this->recordContextualWelcomeTourStep($stepKey, $tourKey);
        }

        SetUserWelcomeTourPreferenceAction::run($user, enabled: false, tourKey: $tourKey);
        event(new WelcomeTourCompleted($user, $tourKey));
    }

    protected function welcomeTourKey(): string
    {
        $welcomeTourKey = get_object_vars($this)['welcomeTourKey'] ?? null;

        return is_string($welcomeTourKey) && $welcomeTourKey !== ''
            ? $welcomeTourKey
            : str_replace('\\', '.', static::class);
    }
}
