<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Extenders;

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Filament\Pages\CapellDashboard;
use Capell\Core\Facades\CapellCore;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Providers\WelcomeTourServiceProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use JibayMcs\FilamentTour\FilamentTourPlugin;

final class WelcomeTourPanelExtender implements AdminPanelExtender
{
    public function extend(Panel $panel): void
    {
        if (config('capell-welcome-tour.enabled', true) !== true
            || ! CapellCore::isPackageInstalled(WelcomeTourServiceProvider::$packageName)) {
            return;
        }

        $panel->pages([WelcomeTourDashboard::class]);

        if (! $panel->hasPlugin('filament-tour')) {
            $panel->plugin(FilamentTourPlugin::make()->onlyVisibleOnce(false));
        }

        $panel
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render("@livewire('capell-welcome-tour.orchestrator')"),
            )
            ->renderHook(
                PanelsRenderHook::PAGE_HEADER_ACTIONS_AFTER,
                fn (): string => view('capell-welcome-tour::filament.actions.replay-tour')->render(),
                CapellDashboard::class,
            );
    }
}
