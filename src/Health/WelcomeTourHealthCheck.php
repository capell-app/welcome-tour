<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Health;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Data\Diagnostics\DoctorCheckResultData;
use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\WelcomeTour\Filament\Extenders\WelcomeTourPanelExtender;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Filament\Panel;
use Illuminate\Support\Collection;
use JibayMcs\FilamentTour\FilamentTourPlugin;
use Throwable;

final class WelcomeTourHealthCheck implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^1.0';
    }

    /**
     * @return Collection<int, DoctorCheckResultData>
     */
    public static function runDiagnostics(): Collection
    {
        $check = new self;

        return collect([
            $check->tourPluginCheck(),
            $check->dashboardSurfaceCheck(),
            $check->settingsResolvableCheck(),
            $check->userDismissalStorageCheck(),
        ]);
    }

    public static function passed(): bool
    {
        return self::runDiagnostics()
            ->every(static fn (DoctorCheckResultData $result): bool => $result->passed);
    }

    /**
     * Asserts the Filament tour package and admin panel extender are available.
     */
    public function tourPluginCheck(): DoctorCheckResultData
    {
        $available = class_exists(FilamentTourPlugin::class)
            && class_exists(WelcomeTourPanelExtender::class);

        return new DoctorCheckResultData(
            label: 'Welcome Tour Filament plugin',
            passed: $available,
            message: $available
                ? 'The Filament tour plugin and Welcome Tour panel extender are available.'
                : 'The Filament tour plugin or Welcome Tour panel extender is missing.',
            remediation: $available
                ? null
                : 'Ensure jibaymcs/filament-tour is installed and WelcomeTourPanelExtender is autoloadable.',
        );
    }

    /**
     * Asserts the hidden tour renderer page can be registered with the admin panel.
     */
    public function dashboardSurfaceCheck(): DoctorCheckResultData
    {
        $registered = $this->isDashboardRegistered();

        return new DoctorCheckResultData(
            label: 'Welcome Tour dashboard surface',
            passed: $registered,
            message: $registered
                ? 'The Welcome Tour renderer page is registered with the admin panel.'
                : 'The Welcome Tour renderer page is not registered with the admin panel.',
            remediation: $registered
                ? null
                : 'Ensure WelcomeTourServiceProvider registers the installed package runtime.',
        );
    }

    /**
     * Asserts settings can resolve and expose their settings schema.
     */
    public function settingsResolvableCheck(): DoctorCheckResultData
    {
        $resolvable = $this->areSettingsResolvable();

        return new DoctorCheckResultData(
            label: 'Welcome Tour settings',
            passed: $resolvable,
            message: $resolvable
                ? 'Welcome Tour settings and schema metadata are resolvable.'
                : 'Welcome Tour settings or schema metadata are not resolvable.',
            remediation: $resolvable
                ? null
                : 'Run the Welcome Tour settings migration and ensure the settings registry is available.',
        );
    }

    /**
     * Asserts the host users table can persist per-user tour dismissal.
     */
    public function userDismissalStorageCheck(): DoctorCheckResultData
    {
        $hasLegacyColumn = WelcomeTourSchema::hasDismissedHintsColumn();
        $hasPackageState = WelcomeTourSchema::hasUserStateTable();
        $available = $hasLegacyColumn || $hasPackageState;

        return new DoctorCheckResultData(
            label: 'Welcome Tour user dismissal storage',
            passed: $available,
            message: $available
                ? 'Per-user tour dismissal storage is available through host user hints or package-owned state.'
                : 'No host user hints column or package-owned state table is available for tour dismissal.',
            remediation: $available
                ? null
                : 'Run the Welcome Tour package migration or add the users.dismissed_hints JSON column.',
        );
    }

    public function isDashboardRegistered(): bool
    {
        try {
            $panel = Panel::make()->id('welcome-tour-health');
            resolve(WelcomeTourPanelExtender::class)->extend($panel);

            return in_array(WelcomeTourDashboard::class, $panel->getPages(), true);
        } catch (Throwable) {
            return false;
        }
    }

    public function areSettingsResolvable(): bool
    {
        try {
            $settings = resolve(WelcomeTourSettings::class);
            $registry = resolve(SettingsSchemaRegistry::class);

            return $settings instanceof WelcomeTourSettings
                && $registry->getSettingsClass(WelcomeTourSettings::group()) === WelcomeTourSettings::class
                && $registry->getSchema(WelcomeTourSettings::group(), 'WelcomeTourSettingsSchema') === WelcomeTourSettingsSchema::class;
        } catch (Throwable) {
            return false;
        }
    }
}
