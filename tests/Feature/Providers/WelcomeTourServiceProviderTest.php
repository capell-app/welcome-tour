<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Tests\Support\InstalledRuntimeParity;
use Capell\Tests\Support\InstalledRuntimeState;
use Capell\Tests\Support\InstalledRuntimeTestCase;
use Capell\WelcomeTour\Filament\Extenders\WelcomeTourPanelExtender;
use Capell\WelcomeTour\Filament\Widgets\WelcomeTourChecklistFilamentWidget;
use Capell\WelcomeTour\Providers\WelcomeTourServiceProvider;
use Capell\WelcomeTour\Support\ContextualWelcomeTourRegistry;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\ViewManager;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use JibayMcs\FilamentTour\FilamentTourServiceProvider;

it('does not resolve unrelated panel extenders during registration', function (): void {
    app()->bind('welcome-tour-unrelated-panel-extender-probe', static function (): never {
        throw new RuntimeException('Unrelated panel extender was resolved during registration.');
    });
    app()->tag('welcome-tour-unrelated-panel-extender-probe', AdminPanelExtender::TAG);

    $provider = new WelcomeTourServiceProvider(app());
    $provider->registeringPackage();

    $panel = Panel::make();
    resolve(WelcomeTourPanelExtender::class)->extend($panel);
    expect($panel->hasPlugin('filament-tour'))->toBeTrue();
});

it('renders one tour orchestrator after repeated package registration', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('welcome-tour'), true);
        $app->register(FilamentTourServiceProvider::class);

        $provider = $app->getProvider(WelcomeTourServiceProvider::class);
        throw_unless($provider instanceof WelcomeTourServiceProvider, RuntimeException::class);
        $provider->registeringPackage();
        $provider->registeringPackage();

        $panel = Panel::make();
        foreach ($app->tagged(AdminPanelExtender::TAG) as $extender) {
            if ($extender instanceof AdminPanelExtender) {
                $extender->extend($panel);
            }
        }

        $app->instance(ViewManager::class, new ViewManager);
        FilamentView::clearResolvedInstance();
        $panel->boot();
        $html = FilamentView::renderHook(PanelsRenderHook::BODY_START)->toHtml();

        expect($panel->hasPlugin('filament-tour'))->toBeTrue()
            ->and(substr_count($html, 'wire:id='))->toBe(2)
            ->and($html)->toContain('x-data');
    });
});

it('does not render tour controls when uninstalled or disabled', function (bool $installed, bool $enabled): void {
    InstalledRuntimeState::run(static function () use ($installed, $enabled): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('welcome-tour'), $installed);
        config()->set('capell-welcome-tour.enabled', $enabled);
        $panel = Panel::make();
        foreach ($app->tagged(AdminPanelExtender::TAG) as $extender) {
            if ($extender instanceof AdminPanelExtender) {
                $extender->extend($panel);
            }
        }

        $app->instance(ViewManager::class, new ViewManager);
        FilamentView::clearResolvedInstance();
        $panel->boot();

        expect($panel->hasPlugin('filament-tour'))->toBeFalse()
            ->and(FilamentView::renderHook(PanelsRenderHook::BODY_START)->toHtml())->toBe('');
    });
})->with([[false, true], [true, false]]);

it('retains one panel extender and the contextual registry across provider instances', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('welcome-tour'), false);
        $registry = $app->make(ContextualWelcomeTourRegistry::class);

        new WelcomeTourServiceProvider($app)->registeringPackage();
        new WelcomeTourServiceProvider($app)->registeringPackage();

        expect(array_filter(iterator_to_array($app->tagged(AdminPanelExtender::TAG)), static fn (mixed $extender): bool => $extender instanceof WelcomeTourPanelExtender))->toHaveCount(1)
            ->and($app->make(ContextualWelcomeTourRegistry::class))->toBe($registry);
    });
});

it('keeps retained user controls inactive until the configuration enables them', function (): void {
    InstalledRuntimeState::run(static function (): void {
        $app = InstalledRuntimeTestCase::boot(InstalledRuntimeParity::manifest('welcome-tour'), true, static function (Application $app): void {
            $app->make(Repository::class)->set('capell-welcome-tour.enabled', false);
        });
        $bridge = $app->make(WelcomeTourUserResourceBridge::class);
        $context = UserSchemaContextData::forCreate();

        expect($bridge->supports($context))->toBeFalse()
            ->and(WelcomeTourChecklistFilamentWidget::canView())->toBeFalse();
        config(['capell-welcome-tour.enabled' => true]);
        expect($bridge->supports($context))->toBeTrue();
        config(['capell-welcome-tour.enabled' => false]);
        expect($bridge->supports($context))->toBeFalse();
    });
});
