<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Tests;

use AmidEsfahani\FilamentTinyEditor\TinyeditorServiceProvider;
use Awcodes\BadgeableColumn\BadgeableColumnServiceProvider;
use BezhanSalleh\FilamentShield\FilamentShieldServiceProvider;
use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Providers\AdminServiceProvider;
use Capell\Admin\Providers\Filament\AdminPanelProvider;
use Capell\Core\Facades\CapellCore;
use Capell\Tests\Packages\PackagesTestCase;
use Capell\Tests\Support\Concerns\CreatesAdminUser;
use Capell\WelcomeTour\Providers\WelcomeTourServiceProvider;
use CmsMulti\FilamentClearCache\FilamentClearCacheServiceProvider;
use CodeWithDennis\FilamentSelectTree\FilamentSelectTreeServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Guava\IconPicker\IconPickerServiceProvider;
use JibayMcs\FilamentTour\FilamentTourServiceProvider;
use LaraZeus\SpatieTranslatable\SpatieTranslatableServiceProvider;
use Livewire\LivewireServiceProvider;
use Override;
use Pboivin\FilamentPeek\FilamentPeekServiceProvider;
use Saade\FilamentAdjacencyList\FilamentAdjacencyListServiceProvider;
use STS\FilamentImpersonate\FilamentImpersonateServiceProvider;
use Tanmuhittin\LaravelGoogleTranslate\LaravelGoogleTranslateServiceProvider;

abstract class WelcomeTourTestCase extends PackagesTestCase
{
    use CreatesAdminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        $this->registerAndMigrateSettings(
            CapellCore::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/core/database/settings',
        );

        $this->registerAndMigrateSettings(
            CapellAdmin::getSettingMigrations(),
            __DIR__ . '/../../../vendor/capell-app/admin/database/settings',
        );

        $this->registerAndMigrateSettings(
            WelcomeTourServiceProvider::getSettingMigrations(),
            __DIR__ . '/../database/settings',
        );
    }

    #[Override]
    protected function getPackageServiceName(): string
    {
        return 'capell-welcome-tour';
    }

    /**
     * @return class-string[]
     */
    #[Override]
    protected function getPackageProviders(mixed $app): array
    {
        return [
            ...parent::getPackageProviders($app),
            ActionsServiceProvider::class,
            BadgeableColumnServiceProvider::class,
            SpatieTranslatableServiceProvider::class,
            TinyeditorServiceProvider::class,
            FilamentServiceProvider::class,
            FilamentAdjacencyListServiceProvider::class,
            FilamentShieldServiceProvider::class,
            FilamentSelectTreeServiceProvider::class,
            FilamentClearCacheServiceProvider::class,
            FilamentPeekServiceProvider::class,
            FilamentTourServiceProvider::class,
            FilamentImpersonateServiceProvider::class,
            FormsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            IconPickerServiceProvider::class,
            LaravelGoogleTranslateServiceProvider::class,
            SupportServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            NotificationsServiceProvider::class,
            AdminServiceProvider::class,
            AdminPanelProvider::class,
            LivewireServiceProvider::class,
            WelcomeTourServiceProvider::class,
        ];
    }

    #[Override]
    protected function getEnvironmentSetUp(mixed $app): void
    {
        parent::getEnvironmentSetUp($app);

        CapellCore::forcePackageInstalled(AdminServiceProvider::$packageName);
        CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName);
    }
}
