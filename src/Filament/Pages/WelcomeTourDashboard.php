<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Pages;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourChaptersAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Capell\WelcomeTour\Events\WelcomeTourCompleted;
use Capell\WelcomeTour\Events\WelcomeTourStarted;
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

        if (! $user instanceof Model || ! ResolveWelcomeTourEnabledAction::run()) {
            return [];
        }

        if (! $this->isTourActive() || (! (bool) session()->get('capell_welcome_tour.active', false) && CanShowWelcomeTourAction::run($user) !== true)) {
            return [];
        }

        $allChapters = ResolveWelcomeTourChaptersAction::run(
            CapellAdmin::getWelcomeTourSteps(),
            new WelcomeTourUserStateData([], null, null, false),
        );
        $chapters = ResolveWelcomeTourChaptersAction::run(
            ResolveWelcomeTourStepsForUserAction::run($user, CapellAdmin::getWelcomeTourSteps()),
            GetUserWelcomeTourStateAction::run($user, self::TOUR_KEY),
        );

        if ($chapters === []) {
            return [];
        }

        event(new WelcomeTourStarted($user, self::TOUR_KEY));

        return array_map(function ($chapter) use ($allChapters): Tour {
            $originalIndex = array_search($chapter->key, array_column($allChapters, 'key'), true);
            $original = $allChapters[$originalIndex === false ? 0 : $originalIndex];
            $steps = [];
            foreach ($chapter->steps as $step) {
                $mapped = WelcomeTourStepFactory::make($step);
                $mapped->title(__('capell-welcome-tour::welcome_tour.tour_progress', [
                    'chapter' => ($originalIndex === false ? 0 : $originalIndex) + 1, 'chapters' => count($allChapters),
                    'step' => (int) array_search($step->key, array_column($original->steps, 'key'), true) + 1, 'steps' => count($original->steps),
                ]) . ' — ' . value($step->title));
                $mapped->dispatchOnNext('capell-welcome-tour::record-step', stepKey: $step->key);
                $steps[] = $mapped;
            }

            $steps[count($steps) - 1]->dispatchOnNext(
                'capell-welcome-tour::complete-chapter',
                chapterKey: $chapter->key,
            );

            return Tour::make(self::TOUR_KEY . '.' . $chapter->key)
                ->route($chapter->route)
                ->alwaysShow()
                ->nextButtonLabel(__('capell-admin::button.next'))
                ->previousButtonLabel(__('capell-admin::button.previous'))
                ->doneButtonLabel(__('capell-admin::button.done'))
                ->steps(...$steps);
        }, $chapters);
    }

    public function restartWelcomeTour(): null
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            session()->forget(['capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
            RestartWelcomeTourProgressAction::run($user, self::TOUR_KEY);
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
        session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
        event(new WelcomeTourCompleted($user, self::TOUR_KEY));
    }

    public function snoozeWelcomeTour(): null
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            SnoozeUserWelcomeTourAction::run($user, hours: 24, tourKey: self::TOUR_KEY);
        }

        session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);

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

    private function isTourActive(): bool
    {
        return config('capell-welcome-tour.presentation_mode', false)
            || (bool) session()->get('capell_welcome_tour.active', false);
    }
}
