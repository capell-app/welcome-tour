<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Livewire;

use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourChaptersAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Data\WelcomeTourChapterData;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;

final class WelcomeTourOrchestrator extends Component
{
    private const string TOUR_KEY = 'capell_admin_welcome';

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
            session()->forget('capell_welcome_tour.active');

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
            session()->forget('capell_welcome_tour.active');
        }
    }

    #[On('capell-welcome-tour::restart')]
    public function restart(): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        RestartWelcomeTourProgressAction::run($user, self::TOUR_KEY);
        session()->put('capell_welcome_tour.active', true);
        session()->put('capell_welcome_tour.show_checklist', true);

        $this->redirect('/' . trim(AdminPanelEntrypoint::path(), '/'));
    }

    public function render(): View
    {
        $user = auth()->user();
        $autoStart = false;
        $tourIdToOpen = null;
        $currentChapterKey = null;
        $currentTargetSelector = null;

        $dashboardPath = trim(AdminPanelEntrypoint::path(), '/');
        $isDashboardRequest = trim(request()->path(), '/') === $dashboardPath;

        if ($user instanceof Model && $isDashboardRequest && config('capell-welcome-tour.presentation_mode', false)) {
            $store = resolve(WelcomeTourStateStoreResolver::class)->resolve();
            $autoStart = ! $store->hasAutoStarted($user, self::TOUR_KEY);

            if ($autoStart) {
                $store->markAutoStarted($user, self::TOUR_KEY);
                session()->put('capell_welcome_tour.active', true);
                $tourIdToOpen = self::TOUR_KEY . '.dashboard';
            }
        }

        if ($user instanceof Model && $this->isTourActive()) {
            $currentPath = '/' . trim(request()->path(), '/');
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

            if ($chapter !== null && count($chapter->steps) === 1) {
                $currentChapterKey = $chapter->key;
                $currentTargetSelector = $chapter->steps[0]->element;
            }
        }

        return view('capell-welcome-tour::livewire.welcome-tour-orchestrator', [
            'autoStart' => $autoStart,
            'tourIdToOpen' => $tourIdToOpen,
            'currentChapterKey' => $currentChapterKey,
            'currentTargetSelector' => $currentTargetSelector,
        ]);
    }

    private function isTourActive(): bool
    {
        return config('capell-welcome-tour.presentation_mode', false)
            || (bool) session()->get('capell_welcome_tour.active', false);
    }
}
