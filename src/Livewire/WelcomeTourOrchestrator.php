<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Livewire;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourChaptersAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Data\WelcomeTourChapterData;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

final class WelcomeTourOrchestrator extends Component
{
    private const string TOUR_KEY = 'capell_admin_welcome';

    #[Locked]
    public string $pagePath = '';

    #[On('capell-welcome-tour::start')]
    public function start(): void
    {
        $this->restart();
    }

    #[On('capell-welcome-tour::complete-chapter')]
    public function completeChapter(string $chapterKey): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        $chapters = ResolveWelcomeTourChaptersAction::run(
            CapellAdmin::getWelcomeTourSteps(),
            GetUserWelcomeTourStateAction::run($user, self::TOUR_KEY),
        );
        $chapter = collect($chapters)->firstWhere('key', $chapterKey);

        if ($chapter === null) {
            return;
        }

        foreach ($chapter->steps as $step) {
            RecordWelcomeTourStepAction::run($user, $step->key, self::TOUR_KEY);
        }

        $remaining = ResolveWelcomeTourChaptersAction::run(
            CapellAdmin::getWelcomeTourSteps(),
            GetUserWelcomeTourStateAction::run($user, self::TOUR_KEY),
        );

        if ($remaining === []) {
            SetUserWelcomeTourPreferenceAction::run($user, enabled: false, tourKey: self::TOUR_KEY);
            session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);

            return;
        }

        $this->redirect($remaining[0]->route);
    }

    #[On('capell-welcome-tour::dismiss')]
    public function dismiss(): void
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            SetUserWelcomeTourPreferenceAction::run($user, enabled: false, tourKey: self::TOUR_KEY);
            session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
        }
    }

    #[On('capell-welcome-tour::restart')]
    public function restart(): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        session()->forget(['capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
        RestartWelcomeTourProgressAction::run($user, self::TOUR_KEY);
        session()->put('capell_welcome_tour.active', true);
        session()->put('capell_welcome_tour.show_checklist', true);

        $this->redirect('/' . trim(AdminPanelEntrypoint::path(), '/'));
    }

    #[On('capell-welcome-tour::record-step')]
    public function recordStep(string $stepKey): void
    {
        $user = auth()->user();
        if ($user instanceof Model && $this->isTourActive()
            && collect(CapellAdmin::getWelcomeTourSteps())->contains('key', $stepKey)) {
            RecordWelcomeTourStepAction::run($user, $stepKey, self::TOUR_KEY);
        }
    }

    #[On('capell-welcome-tour::target-unavailable')]
    public function targetUnavailable(): void
    {
        session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
        Notification::make()->title(__('capell-welcome-tour::welcome_tour.target_unavailable'))->warning()->send();
    }

    public function snooze(): void
    {
        $user = auth()->user();
        if ($user instanceof Model) {
            SnoozeUserWelcomeTourAction::run($user, hours: 24, tourKey: self::TOUR_KEY);
        }

        session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
    }

    public function render(): View
    {
        $user = auth()->user();
        $autoStart = false;
        $tourIdToOpen = null;
        $currentChapterKey = null;
        $currentTargetSelector = null;
        $targetSelectors = [];

        if ($this->pagePath === '') {
            $this->pagePath = '/' . trim(request()->path(), '/');
        }

        $dashboardPath = trim(AdminPanelEntrypoint::path(), '/');
        $isDashboardRequest = trim($this->pagePath, '/') === $dashboardPath;

        if ($user instanceof Model && ResolveWelcomeTourEnabledAction::run() && $isDashboardRequest && config('capell-welcome-tour.presentation_mode', false)) {
            $store = resolve(WelcomeTourStateStoreResolver::class)->resolve();
            $autoStart = ! $store->hasAutoStarted($user, self::TOUR_KEY);

            if ($autoStart) {
                $store->markAutoStarted($user, self::TOUR_KEY);
                session()->put('capell_welcome_tour.active', true);
                $tourIdToOpen = self::TOUR_KEY . '.dashboard';
            }
        }

        if ($user instanceof Model && $this->isTourActive()) {
            $currentPath = $this->pagePath;
            /** @var list<WelcomeTourChapterData> $chapters */
            $chapters = ResolveWelcomeTourChaptersAction::run(
                CapellAdmin::getWelcomeTourSteps(),
                GetUserWelcomeTourStateAction::run($user, self::TOUR_KEY),
            );

            /** @var WelcomeTourChapterData|null $chapter */
            $chapter = collect($chapters)->first(fn (WelcomeTourChapterData $chapter): bool => $chapter->route === $currentPath);

            if (! config('capell-welcome-tour.presentation_mode', false)
                && (bool) session()->get('capell_welcome_tour.active', false)
                && $chapter !== null) {
                $tourIdToOpen = self::TOUR_KEY . '.' . $chapter->key;
            }

            if ($chapter !== null) {
                $targetSelectors = array_values(array_filter(array_map(
                    fn ($step): ?string => $step->element,
                    ResolveWelcomeTourStepsForUserAction::run($user, $chapter->steps),
                )));
                $currentChapterKey = $chapter->key;
                $currentTargetSelector = $chapter->steps[0]->element;
            }
        }

        return view('capell-welcome-tour::livewire.welcome-tour-orchestrator', [
            'autoStart' => $autoStart,
            'targetSelectors' => $targetSelectors,
            'isPreview' => resolve(WelcomeTourStateStoreResolver::class)->isPreview(),
            'tourIdToOpen' => $tourIdToOpen,
            'currentChapterKey' => $currentChapterKey,
            'currentTargetSelector' => $currentTargetSelector,
        ]);
    }

    private function isTourActive(): bool
    {
        return ResolveWelcomeTourEnabledAction::run() && (config('capell-welcome-tour.presentation_mode', false)
            || (bool) session()->get('capell_welcome_tour.active', false));
    }
}
