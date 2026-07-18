<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Widgets;

use Capell\Admin\Actions\DismissHintAction;
use Capell\Admin\Contracts\CapellFilamentWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Core\Contracts\Extensions\RegistersExtensionFilamentWidget;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

final class WelcomeTourChecklistFilamentWidget extends Widget implements CapellFilamentWidgetContract, RegistersExtensionFilamentWidget
{
    use GatedByRoleAndSettings;

    private const string DISMISSED_CHECKLIST_HINT_KEY = 'welcome.checklist.v1';

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['editor', 'admin', 'super_admin'];

    protected static string $settingsKey = 'welcome_tour.checklist';

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
        return BuildWelcomeTourChecklistAction::run();
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
        }

        session()->put('capell_welcome_tour.active', true);
        session()->put('capell_welcome_tour.show_checklist', true);
        $this->redirect(request()->url());
    }

    public function shouldShowChecklist(): bool
    {
        if ((bool) session()->get('capell_welcome_tour.show_checklist', false)) {
            return true;
        }

        $user = auth()->user();

        if (! $user instanceof Model || ! WelcomeTourSchema::hasDismissedHintsColumn($user->getTable())) {
            return ! (bool) session()->get('capell_welcome_tour.checklist_dismissed', false);
        }

        $rawDismissedHints = DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->value('dismissed_hints');
        $dismissedHints = is_string($rawDismissedHints) ? json_decode($rawDismissedHints, true) : [];

        return ! in_array(self::DISMISSED_CHECKLIST_HINT_KEY, is_array($dismissedHints) ? $dismissedHints : [], true);
    }

    public function dismissChecklist(): void
    {
        session()->forget('capell_welcome_tour.show_checklist');
        $user = auth()->user();

        if ($user instanceof Model && WelcomeTourSchema::hasDismissedHintsColumn($user->getTable())) {
            DismissHintAction::run($user, self::DISMISSED_CHECKLIST_HINT_KEY);

            return;
        }

        session()->put('capell_welcome_tour.checklist_dismissed', true);
    }

    public function dismissTourCallout(): void
    {
        $user = auth()->user();

        if ($user instanceof Model) {
            SetUserWelcomeTourPreferenceAction::run($user, enabled: false);
        }
    }
}
