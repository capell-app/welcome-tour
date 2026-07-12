<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\ResetUserWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Events\WelcomeTourCompleted;
use Capell\WelcomeTour\Events\WelcomeTourStarted;
use Capell\WelcomeTour\Support\WelcomeTourStepFactory;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\FilamentTour\Tour\HasTour;
use JibayMcs\FilamentTour\Tour\Tour;
use Livewire\Attributes\On;
use Override;

class WelcomeTourDashboard extends CapellDashboard
{
    use HasTour;

    private const string DISMISS_EVENT = 'capell-welcome-tour::dismiss';

    private const string STEP_COMPLETED_EVENT = 'capell-welcome-tour::step-completed';

    private const string TOUR_KEY = 'capell_admin_welcome';

    protected static ?string $slug = 'welcome-tour/welcome-tour-dashboard';

    /**
     * @return array<int, Tour>
     */
    public function tours(): array
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return [];
        }

        if (CanShowWelcomeTourAction::run($user) !== true) {
            return [];
        }

        $tourSteps = ResolveWelcomeTourStepsForUserAction::run(
            $user,
            CapellAdmin::getWelcomeTourSteps(),
            self::TOUR_KEY,
        );

        $steps = array_map(
            WelcomeTourStepFactory::make(...),
            $tourSteps,
        );

        if ($steps === []) {
            return [];
        }

        event(new WelcomeTourStarted($user, self::TOUR_KEY));

        foreach (array_values($tourSteps) as $index => $tourStep) {
            $eventName = $index === array_key_last($tourSteps) ? self::DISMISS_EVENT : self::STEP_COMPLETED_EVENT;

            $steps[$index]->dispatchOnNext($eventName, stepKey: $tourStep->key);
        }

        return [
            Tour::make(self::TOUR_KEY)
                ->route('/' . trim(AdminPanelEntrypoint::path(), '/'))
                ->nextButtonLabel(__('capell-admin::button.next'))
                ->previousButtonLabel(__('capell-admin::button.previous'))
                ->doneButtonLabel(__('capell-admin::button.done'))
                ->steps(...$steps),
        ];
    }

    #[On(self::STEP_COMPLETED_EVENT)]
    public function recordWelcomeTourStep(string $stepKey): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        RecordWelcomeTourStepAction::run($user, $stepKey, self::TOUR_KEY);
    }

    #[On(self::DISMISS_EVENT)]
    public function dismissWelcomeTour(?string $stepKey = null): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        if (is_string($stepKey) && $stepKey !== '') {
            RecordWelcomeTourStepAction::run($user, $stepKey, self::TOUR_KEY);
        }

        SetUserWelcomeTourPreferenceAction::run($user, enabled: false);
        event(new WelcomeTourCompleted($user, self::TOUR_KEY));
    }

    public function restartWelcomeTour(): null
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            ResetUserWelcomeTourAction::run($user, self::TOUR_KEY);
        }

        Notification::make()
            ->title(__('capell-welcome-tour::welcome_tour.restart_tour_notification'))
            ->success()
            ->send();

        return null;
    }

    public function snoozeWelcomeTour(): null
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            SnoozeUserWelcomeTourAction::run($user, hours: 24, tourKey: self::TOUR_KEY);
        }

        Notification::make()
            ->title(__('capell-welcome-tour::welcome_tour.snooze_tour_notification'))
            ->success()
            ->send();

        return null;
    }

    /**
     * @return array<int, Action>
     */
    #[Override]
    protected function getHeaderActions(): array
    {
        return [
            ...parent::getHeaderActions(),
            Action::make('snoozeWelcomeTour')
                ->label(__('capell-welcome-tour::welcome_tour.snooze_tour'))
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->action(fn (): null => $this->snoozeWelcomeTour()),
            Action::make('restartWelcomeTour')
                ->label(__('capell-welcome-tour::welcome_tour.restart_tour'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(fn (): null => $this->restartWelcomeTour()),
        ];
    }
}
