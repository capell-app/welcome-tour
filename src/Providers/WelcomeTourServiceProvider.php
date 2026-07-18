<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Providers;

use Capell\Admin\Contracts\Bridges\UserResourceBridge;
use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Data\Extensions\ExtensionManagementSurfaceData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Support\Packages\AbstractPackageServiceProvider;
use Capell\Core\Support\Settings\SettingsGroupMetadata;
use Capell\WelcomeTour\Filament\Extenders\WelcomeTourPanelExtender;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Filament\Widgets\WelcomeTourChecklistFilamentWidget;
use Capell\WelcomeTour\Livewire\WelcomeTourOrchestrator;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\ContextualWelcomeTourRegistry;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Filament\Support\Icons\Heroicon;
use Livewire\Livewire;
use Override;
use Spatie\LaravelPackageTools\Package;

final class WelcomeTourServiceProvider extends AbstractPackageServiceProvider
{
    public static string $name = 'capell-welcome-tour';

    public static string $packageName = 'capell-app/welcome-tour';

    /**
     * @return array<array-key, mixed>
     */
    public static function getSettingMigrations(): array
    {
        return [
            '2026_05_10_190836_01_add_welcome_tour_settings',
        ];
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name(self::$name)
            ->hasConfigFile()
            ->hasMigration('2026_06_04_000001_create_welcome_tour_user_states_table')
            ->hasTranslations();
    }

    public function registeringPackage(): void
    {
        parent::registeringPackage();

        $this->app->singleton(ContextualWelcomeTourRegistry::class);
    }

    #[Override]
    protected function bootInstalledPackage(): self
    {
        if (! $this->shouldRegisterRuntime()) {
            return $this;
        }

        Livewire::component('capell-welcome-tour.orchestrator', WelcomeTourOrchestrator::class);
        $this->app->tag([WelcomeTourPanelExtender::class], AdminPanelExtender::TAG);
        $this->app->tag([WelcomeTourUserResourceBridge::class], UserResourceBridge::TAG);

        CapellAdmin::useDashboardPage(WelcomeTourDashboard::class);
        CapellAdmin::registerDashboardFilamentWidget(WelcomeTourChecklistFilamentWidget::class, DashboardEnum::Main);
        CapellAdmin::registerExtensionManagementSurface(ExtensionManagementSurfaceData::settings(
            packageName: self::$packageName,
            label: 'capell-welcome-tour::welcome_tour.settings_label',
            settingsGroup: WelcomeTourSettings::group(),
            icon: Heroicon::OutlinedSparkles,
        ));

        $this->registerSettings();
        resolve(WelcomeTourStepRegistrar::class)->register();
        $contextualTours = config('capell-welcome-tour.contextual_tours', []);

        resolve(ContextualWelcomeTourRegistry::class)->registerConfiguredTours(
            is_array($contextualTours) ? $contextualTours : [],
        );

        return $this;
    }

    private function shouldRegisterRuntime(): bool
    {
        if (! config('capell-welcome-tour.enabled', true)) {
            return false;
        }

        return CapellCore::isPackageInstalled(self::$packageName);
    }

    private function registerSettings(): void
    {
        $this->surface()->settingsClass(WelcomeTourSettings::group(), WelcomeTourSettings::class);
        $this->surface()->settingsSchema(WelcomeTourSettings::group(), WelcomeTourSettingsSchema::class);

        $this->surface()->settingsMetadata(new SettingsGroupMetadata(
            group: WelcomeTourSettings::group(),
            label: 'capell-welcome-tour::welcome_tour.settings_label',
            icon: Heroicon::OutlinedSparkles,
            navigationGroup: 'capell-admin::navigation.group_system',
            navigationSort: 92,
            packageName: self::$packageName,
        ));
    }
}
