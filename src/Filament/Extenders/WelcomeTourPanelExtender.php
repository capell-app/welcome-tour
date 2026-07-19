<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use JibayMcs\FilamentTour\FilamentTourPlugin;

final class WelcomeTourPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        $panel->pages([WelcomeTourDashboard::class]);

        if (! $panel->hasPlugin('filament-tour')) {
            $panel->plugin(FilamentTourPlugin::make()->onlyVisibleOnce(false));
        }

        $panel->renderHook(
            PanelsRenderHook::BODY_START,
            fn (): string => Blade::render("@livewire('capell-welcome-tour.orchestrator')"),
        );
    }
}
