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

final class WelcomeTourChecklistFilamentWidget extends Widget implements CapellFilamentWidgetContract, RegistersExtensionFilamentWidget
{
    use GatedByRoleAndSettings;

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
            && WelcomeTourSchema::hasUserStateTable()
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
        $this->redirect('/' . trim(AdminPanelEntrypoint::path(), '/'));
    }

    public function shouldShowChecklist(): bool
    {
        $user = auth()->user();

        return ! $user instanceof Model || ! GetUserWelcomeTourStateAction::run($user)->checklistDismissed;
    }

    public function dismissChecklist(): void
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            SetWelcomeTourChecklistVisibilityAction::run($user, visible: false);
        }
    }

    public function toggleChecklistItem(string $itemKey): void
    {
        $user = auth()->user();

        if (! $user instanceof Model) {
            return;
        }

        $item = collect($this->items())->firstWhere('key', $itemKey);

        if (! $item instanceof WelcomeTourChecklistItemData) {
            return;
        }

        SetWelcomeTourChecklistItemCompletionAction::run($user, $itemKey, completed: ! $item->manuallyCompleted);
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
