<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Widgets;

use Capell\Admin\Contracts\CapellFilamentWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\Core\Contracts\Extensions\RegistersExtensionFilamentWidget;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Actions\BuildWelcomeTourSummaryAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RestartWelcomeTourProgressAction;
use Capell\WelcomeTour\Actions\Users\SetWelcomeTourChecklistItemCompletionAction;
use Capell\WelcomeTour\Actions\Users\SetWelcomeTourChecklistVisibilityAction;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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
        $this->returnPath = $this->returnPathFromReferer()
            ?? '/' . trim(AdminPanelEntrypoint::path(), '/');

        if (! $this->shouldSendWelcomeNotification()) {
            return;
        }

        Notification::make('welcome-tour-introduction')
            ->title(__('capell-welcome-tour::welcome_tour.callout_heading'))
            ->body(__('capell-welcome-tour::welcome_tour.callout_description'))
            ->icon('heroicon-o-sparkles')
            ->actions([
                Action::make('take-tour')
                    ->label(__('capell-welcome-tour::welcome_tour.take_tour'))
                    ->dispatch('capell-welcome-tour::start')
                    ->close(),
                Action::make('not-now')
                    ->label(__('capell-welcome-tour::welcome_tour.not_now'))
                    ->color('gray')
                    ->dispatch('capell-welcome-tour::dismiss')
                    ->close(),
            ])
            ->persistent()
            ->send();
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

    public function shouldSendWelcomeNotification(): bool
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
        $destination = $this->returnPath !== ''
            ? $this->returnPath
            : '/' . AdminPanelEntrypoint::path();

        if ($user instanceof Model) {
            RestartWelcomeTourProgressAction::run($user);
            SetWelcomeTourChecklistVisibilityAction::run($user, visible: true);

            $firstChapter = BuildWelcomeTourSummaryAction::run()->chapters[0] ?? null;
            $currentPath = parse_url($destination, PHP_URL_PATH);

            if ($firstChapter !== null && $currentPath !== $firstChapter->route) {
                $destination = $firstChapter->route;
            }
        }

        session()->put('capell_welcome_tour.active', true);
        session()->put('capell_welcome_tour.show_checklist', true);
        $this->redirect($destination);
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

    private function returnPathFromReferer(): ?string
    {
        $referer = request()->headers->get('referer');

        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $parts = parse_url($referer);
        $host = is_array($parts) ? ($parts['host'] ?? null) : null;
        $path = is_array($parts) ? ($parts['path'] ?? null) : null;

        if (! is_string($host)
            || ! hash_equals(strtolower(request()->getHost()), strtolower($host))
            || ! is_string($path)
            || ! str_starts_with($path, '/')) {
            return null;
        }

        $query = $parts['query'] ?? null;

        return $path . (is_string($query) && $query !== '' ? '?' . $query : '');
    }
}
