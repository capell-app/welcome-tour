<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Filament\Panel;
use JibayMcs\FilamentTour\FilamentTourPlugin;

final class WelcomeTourPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if ($panel->hasPlugin('filament-tour')) {
            return;
        }

        $panel->plugin(FilamentTourPlugin::make());
    }
}
