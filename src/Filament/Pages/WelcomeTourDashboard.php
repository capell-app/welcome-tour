<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Pages;

use Capell\Admin\Data\WelcomeTourStepData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Support\WelcomeTourStepFactory;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\FilamentTour\Tour\HasTour;
use JibayMcs\FilamentTour\Tour\Step;
use JibayMcs\FilamentTour\Tour\Tour;
use Livewire\Attributes\On;

class WelcomeTourDashboard extends CapellDashboard
{
    use HasTour;

    private const DISMISS_EVENT = 'capell-welcome-tour::dismiss';

    /**
     * @return array<int, Tour>
     */
    public function tours(): array
    {
        $user = auth()->user();

        if (CanShowWelcomeTourAction::run($user instanceof Model ? $user : null) !== true) {
            return [];
        }

        resolve(WelcomeTourStepRegistrar::class)->register();

        $steps = collect(CapellAdmin::getWelcomeTourSteps())
            ->map(fn (WelcomeTourStepData $step): Step => WelcomeTourStepFactory::make($step))
            ->all();

        if ($steps === []) {
            return [];
        }

        $steps[array_key_last($steps)]->dispatchOnNext(self::DISMISS_EVENT);

        return [
            Tour::make('capell_admin_welcome')
                ->route('/' . trim(AdminPanelEntrypoint::path(), '/'))
                ->nextButtonLabel(__('capell-admin::button.next'))
                ->previousButtonLabel(__('capell-admin::button.previous'))
                ->doneButtonLabel(__('capell-admin::button.done'))
                ->steps(...$steps),
        ];
    }

    #[On(self::DISMISS_EVENT)]
    public function dismissWelcomeTour(): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        SetUserWelcomeTourPreferenceAction::run($user, enabled: false);
    }
}
