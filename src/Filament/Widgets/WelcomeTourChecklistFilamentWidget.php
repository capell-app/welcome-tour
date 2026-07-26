<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Widgets;

use Capell\Admin\Contracts\CapellFilamentWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\Core\Contracts\Extensions\RegistersExtensionFilamentWidget;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SetWelcomeTourChecklistItemCompletionAction;
use Capell\WelcomeTour\Actions\Users\SetWelcomeTourChecklistVisibilityAction;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

final class WelcomeTourChecklistFilamentWidget extends Widget implements CapellFilamentWidgetContract, RegistersExtensionFilamentWidget
{
    use GatedByRoleAndSettings;

    #[Locked]
    public string $returnPath = '';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['editor', 'admin', 'super_admin'];

    protected static string $settingsKey = '';

    protected string $view = 'capell-welcome-tour::filament.widgets.welcome-tour-checklist';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    public function mount(): void
    {
        $this->returnPath = request()->getRequestUri();
    }

    /**
     * @return list<WelcomeTourChecklistItemData>
     */
    #[Computed(persist: true, seconds: 60)]
    public function items(): array
    {
        $user = auth()->user();

        return BuildWelcomeTourChecklistAction::run($user instanceof Model ? $user : null);
    }

    public function completedCount(): int
    {
        return count(array_filter(
            $this->items(),
            fn (WelcomeTourChecklistItemData $item): bool => $item->complete,
        ));
    }

    public function shouldShowCallout(): bool
    {
        $user = auth()->user();

        return ! config('capell-welcome-tour.presentation_mode', false)
            && $user instanceof Model
            && ! (bool) session()->get('capell_welcome_tour.active', false)
            && WelcomeTourSchema::hasTable('sites')
            && DB::table('sites')->exists()
            && CanShowWelcomeTourAction::run($user);
    }

    public function startTour(): void
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            RestartWelcomeTourProgressAction::run($user);
            SetWelcomeTourChecklistVisibilityAction::run($user, visible: true);
        }

        session()->put('capell_welcome_tour.active', true);
        session()->put('capell_welcome_tour.show_checklist', true);
        $this->redirect($this->returnPath !== ''
            ? $this->returnPath
            : '/' . AdminPanelEntrypoint::path());
    }

    public function shouldShowChecklist(): bool
    {
        if ((bool) session()->get('capell_welcome_tour.show_checklist', false)) {
            return true;
        }

        $user = auth()->user();

        if (! $user instanceof Model) {
            return ! (bool) session()->get('capell_welcome_tour.checklist_dismissed', false);
        }

        return ! GetUserWelcomeTourStateAction::run($user)->checklistDismissed;
    }

    public function dismissChecklist(): void
    {
        session()->forget('capell_welcome_tour.show_checklist');
        $user = auth()->user();

        if ($user instanceof Model) {
            SetWelcomeTourChecklistVisibilityAction::run($user, visible: false);

            return;
        }

        session()->put('capell_welcome_tour.checklist_dismissed', true);
    }

    public function setChecklistItemCompletion(string $itemKey, bool $completed): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        SetWelcomeTourChecklistItemCompletionAction::run($user, $itemKey, $completed);
        unset($this->items);
    }

    public function dismissTourCallout(): void
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            SetUserWelcomeTourPreferenceAction::run($user, enabled: false);
        }
    }
}
