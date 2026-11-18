<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Core\Enums\ExtensionStatusEnum;
use Capell\Core\Facades\CapellCore;
use Capell\Core\Models\CapellExtension;
use Capell\Core\Models\Site;
use Capell\Core\Support\Activity\ActivityLogCompat;
use Capell\Core\Support\Packages\TrustedCorePackages;

/**
 * Setup signals a checklist item can complete on. Each one needs a person's action after the
 * installer finished, so a fresh install never starts with these items already ticked.
 */
final class WelcomeTourSetupSignals
{
    /** A signed-in person has saved a site: the installer creates the site without a causer. */
    public function siteSaved(): bool
    {
        $activity = ActivityLogCompat::activityModelClass();

        if (! WelcomeTourSchema::hasTable((new $activity)->getTable())) {
            return false;
        }

        return $activity::query()
            ->where('subject_type', (new Site)->getMorphClass())
            ->where('event', 'updated')
            ->whereNotNull('causer_id')
            ->exists();
    }

    /** An extension beyond the installer's defaults was enabled after the installation completed. */
    public function extensionInstalledAfterSetup(): bool
    {
        if (! WelcomeTourSchema::hasTable((new CapellExtension)->getTable())) {
            return false;
        }

        $installedAt = CapellExtension::query()->where('composer_name', 'capell-app/core')->value('installed_at');

        if ($installedAt === null) {
            return false;
        }

        return CapellExtension::query()
            ->where('status', ExtensionStatusEnum::Enabled)
            ->where('installed_at', '>', $installedAt)
            ->pluck('composer_name')
            ->contains(fn (string $name): bool => ! TrustedCorePackages::contains($name)
                && ! $this->isDefaultInstallSelection($name));
    }

    /** The installer pre-ticks it: by its manifest's `defaultSelected`, or as a default aggregate member. */
    private function isDefaultInstallSelection(string $name): bool
    {
        return CapellCore::hasPackage($name)
            ? TrustedCorePackages::isDefaultInstallSelectionPackage(CapellCore::getPackage($name))
            : TrustedCorePackages::isDefaultInstallSelection($name);
    }
}
