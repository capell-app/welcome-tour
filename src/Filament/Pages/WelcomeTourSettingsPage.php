<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Capell\Admin\Filament\Pages\AbstractPackageSettingsPage;

final class WelcomeTourSettingsPage extends AbstractPackageSettingsPage
{
    use HasPageShield;

    protected static string $settingsGroup = 'welcome-tour';

    protected static ?string $slug = 'extensions/welcome-tour/settings';
}
