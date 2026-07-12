<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Widgets;

use Capell\Admin\Contracts\CapellFilamentWidgetContract;
use Capell\Admin\Filament\Concerns\GatedByRoleAndSettings;
use Capell\Core\Contracts\Extensions\RegistersExtensionFilamentWidget;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Filament\Widgets\Widget;
use Livewire\Attributes\Computed;

final class WelcomeTourChecklistFilamentWidget extends Widget implements CapellFilamentWidgetContract, RegistersExtensionFilamentWidget
{
    use GatedByRoleAndSettings;

    /** @var list<string> */
    protected static array $rolesConfigKeys = ['editor', 'admin', 'super_admin'];

    protected static string $settingsKey = 'welcome_tour.checklist';

    protected string $view = 'capell-welcome-tour::filament.widgets.welcome-tour-checklist';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -1;

    public static function compatibleCapellApiVersion(): string
    {
        return '^4.0';
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
}
