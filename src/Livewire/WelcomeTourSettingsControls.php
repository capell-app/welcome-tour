<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Livewire;

use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\WelcomeTour\Actions\BuildWelcomeTourSummaryAction;
use Capell\WelcomeTour\Actions\Users\StartWelcomeTourForCurrentUserAction;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class WelcomeTourSettingsControls extends Component
{
    public function preview(): void
    {
        abort_unless(ExtensionsPage::canManageExtensions(), 403);
        $this->redirect(StartWelcomeTourForCurrentUserAction::run(preview: true));
    }

    public function restart(): void
    {
        abort_unless(ExtensionsPage::canManageExtensions(), 403);
        $this->redirect(StartWelcomeTourForCurrentUserAction::run());
    }

    public function render(): View
    {
        abort_unless(ExtensionsPage::canManageExtensions(), 403);

        return view('capell-welcome-tour::filament.settings.controls', [
            'summary' => BuildWelcomeTourSummaryAction::run(),
        ]);
    }
}
