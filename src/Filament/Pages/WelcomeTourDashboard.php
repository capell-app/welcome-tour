<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourChaptersAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SetWelcomeTourChecklistVisibilityAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Events\WelcomeTourCompleted;
use Capell\WelcomeTour\Events\WelcomeTourStarted;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Capell\WelcomeTour\Support\WelcomeTourStepFactory;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\FilamentTour\Tour\HasTour;
use JibayMcs\FilamentTour\Tour\Tour;
use Override;

class WelcomeTourDashboard extends CapellDashboard
{
    use HasTour;

    private const string TOUR_KEY = 'capell_admin_welcome';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'welcome-tour/welcome-tour-dashboard';

    protected static string $routePath = '/welcome-tour/welcome-tour-dashboard';

    /**
     * @return array<int, Tour>
     */
    public function tours(): array
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return [];
        }

        if (! $this->isTourActive($user) || CanShowWelcomeTourAction::run($user) !== true) {
            return [];
        }

        $chapters = ResolveWelcomeTourChaptersAction::run(
            CapellAdmin::getWelcomeTourSteps(),
            GetUserWelcomeTourStateAction::run($user, self::TOUR_KEY),
        );

        if ($chapters === []) {
            return [];
        }

        event(new WelcomeTourStarted($user, self::TOUR_KEY));

        $chapter = $chapters[0];
        $steps = array_map(WelcomeTourStepFactory::make(...), $chapter->steps);
        $steps[count($steps) - 1]->dispatchOnNext('capell-welcome-tour::complete-chapter', chapterKey: $chapter->key);

        return [Tour::make(self::TOUR_KEY . '.' . $chapter->key)
            ->route($chapter->route)
            ->alwaysShow()
            ->nextButtonLabel(__('capell-admin::button.next'))
            ->previousButtonLabel(__('capell-admin::button.previous'))
            ->doneButtonLabel(__('capell-admin::button.done'))
            ->steps(...$steps)];
    }

    public function restartWelcomeTour(): null
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            RestartWelcomeTourProgressAction::run($user, self::TOUR_KEY);
            SetWelcomeTourChecklistVisibilityAction::run($user, visible: true, tourKey: self::TOUR_KEY);
            session()->put('capell_welcome_tour.active', true);
        }

        Notification::make()
            ->title(__('capell-welcome-tour::welcome_tour.restart_tour_notification'))
            ->success()
            ->send();

        $this->redirect('/' . trim(AdminPanelEntrypoint::path(), '/'));

        return null;
    }

    public function recordWelcomeTourStep(string $stepKey): void
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            RecordWelcomeTourStepAction::run($user, $stepKey, self::TOUR_KEY);
        }
    }

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
        session()->forget('capell_welcome_tour.active');
        event(new WelcomeTourCompleted($user, self::TOUR_KEY));
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

    private function isTourActive(Model $user): bool
    {
        if ((bool) session()->get('capell_welcome_tour.active', false)) {
            return true;
        }

        $dashboardPath = trim(AdminPanelEntrypoint::path(), '/');

        if (! config('capell-welcome-tour.presentation_mode', false)
            || trim(request()->path(), '/') !== $dashboardPath) {
            return false;
        }

        $store = resolve(WelcomeTourStateStoreResolver::class)->resolve();

        if ($store->hasAutoStarted($user, self::TOUR_KEY)) {
            return false;
        }

        $store->markAutoStarted($user, self::TOUR_KEY);
        session()->put('capell_welcome_tour.active', true);

        return true;
    }
}
