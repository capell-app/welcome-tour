<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Core\Facades\CapellCore;
use Capell\WelcomeTour\Filament\Extenders\WelcomeTourPanelExtender;
use Capell\WelcomeTour\Providers\WelcomeTourServiceProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

it('does not resolve unrelated panel extenders while registering its own', function (): void {
    $tagsProperty = new ReflectionProperty(app(), 'tags');
    $originalTags = $tagsProperty->getValue(app());
    throw_unless(is_array($originalTags), RuntimeException::class, 'Expected application container tags.');

    $constructions = 0;
    app()->bind('welcome-tour-unrelated-panel-extender-probe', function () use (&$constructions): AdminPanelExtender {
        $constructions++;

        return new class implements AdminPanelExtender
        {
            public function extend(Panel $panel): void {}
        };
    });
    app()->tag('welcome-tour-unrelated-panel-extender-probe', AdminPanelExtender::TAG);

    try {
        $provider = new WelcomeTourServiceProvider(app());
        $provider->registeringPackage();

        expect($constructions)->toBe(0);
    } finally {
        $tagsProperty->setValue(app(), $originalTags);
    }
});

it('registers the installed tour panel extender before the panel consumes it', function (): void {
    $tagsProperty = new ReflectionProperty(app(), 'tags');
    $originalTags = $tagsProperty->getValue(app());
    throw_unless(is_array($originalTags), RuntimeException::class, 'Expected application container tags.');
    $tags = $originalTags;
    $tags[AdminPanelExtender::TAG] = array_values(array_filter(
        (array) ($tags[AdminPanelExtender::TAG] ?? []),
        static fn (mixed $abstract): bool => $abstract !== WelcomeTourPanelExtender::class,
    ));
    $tagsProperty->setValue(app(), $tags);
    CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName, true);

    try {
        $provider = new WelcomeTourServiceProvider(app());

        $provider->registeringPackage();
        $provider->registeringPackage();

        $welcomeExtenders = collect(app()->tagged(AdminPanelExtender::TAG))
            ->filter(static fn (mixed $extender): bool => $extender instanceof WelcomeTourPanelExtender);

        expect($welcomeExtenders)->toHaveCount(1);

        $panel = Panel::make();
        $welcomeExtender = $welcomeExtenders->first();
        throw_unless($welcomeExtender instanceof WelcomeTourPanelExtender, RuntimeException::class, 'Expected the Welcome Tour panel extender.');
        $welcomeExtender->extend($panel);

        expect($panel->hasPlugin('filament-tour'))->toBeTrue();

        $reflection = new ReflectionProperty($panel, 'renderHooks');
        $renderHooks = $reflection->getValue($panel);
        throw_unless(is_array($renderHooks), RuntimeException::class, 'Expected panel render hooks.');
        $bodyStartScopes = $renderHooks[PanelsRenderHook::BODY_START] ?? null;
        throw_unless(is_array($bodyStartScopes), RuntimeException::class, 'Expected body-start render hooks.');
        $bodyStartHooks = $bodyStartScopes[''] ?? null;
        throw_unless(is_array($bodyStartHooks), RuntimeException::class, 'Expected unscoped body-start hooks.');
        expect($bodyStartHooks)->toHaveCount(2);

        $rendered = '';
        foreach ($bodyStartHooks as $hook) {
            throw_unless($hook instanceof Closure, RuntimeException::class, 'Expected a body-start render hook.');
            $rendered .= (string) $hook();
        }

        expect($rendered)->toContain('wire:id')
            ->toContain('x-data');
    } finally {
        $tagsProperty->setValue(app(), $originalTags);
        CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName);
    }
});

it('does not render an orchestrator hook when the package is not installed', function (): void {
    $tagsProperty = new ReflectionProperty(app(), 'tags');
    $originalTags = $tagsProperty->getValue(app());
    throw_unless(is_array($originalTags), RuntimeException::class, 'Expected the application container tags to be an array.');

    $tags = $originalTags;
    $tags[AdminPanelExtender::TAG] = array_values(array_filter(
        (array) ($tags[AdminPanelExtender::TAG] ?? []),
        static fn (mixed $abstract): bool => $abstract !== WelcomeTourPanelExtender::class,
    ));

    $tagsProperty->setValue(app(), $tags);
    CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName, false);

    try {
        $provider = new WelcomeTourServiceProvider(app());

        $provider->registeringPackage();

        $welcomeExtenders = collect(app()->tagged(AdminPanelExtender::TAG))
            ->filter(static fn (mixed $extender): bool => $extender instanceof WelcomeTourPanelExtender);
        $panel = Panel::make();

        $rendered = '';
        $exception = null;

        try {
            foreach ($welcomeExtenders as $extender) {
                $extender->extend($panel);
            }

            $reflection = new ReflectionProperty($panel, 'renderHooks');
            $renderHooks = $reflection->getValue($panel);
            $bodyStartHooks = [];

            if (is_array($renderHooks)) {
                $bodyStartHooks = $renderHooks[PanelsRenderHook::BODY_START] ?? [];
                $bodyStartHooks = is_array($bodyStartHooks) ? ($bodyStartHooks[''] ?? []) : [];
            }

            foreach ((array) $bodyStartHooks as $hook) {
                if ($hook instanceof Closure) {
                    $rendered .= (string) $hook();
                }
            }
        } catch (Throwable $caught) {
            $exception = $caught;
        }

        expect($welcomeExtenders)->toHaveCount(1)
            ->and($exception)->toBeNull()
            ->and($rendered)->toBe('')
            ->and($panel->hasPlugin('filament-tour'))->toBeFalse();
    } finally {
        $tagsProperty->setValue(app(), $originalTags);
        CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName);
    }
});

it('does not add tour panel behaviour when the package is disabled', function (): void {
    $originalEnabled = config('capell-welcome-tour.enabled');
    config()->set('capell-welcome-tour.enabled', false);
    CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName, true);

    try {
        $panel = Panel::make();
        resolve(WelcomeTourPanelExtender::class)->extend($panel);

        $reflection = new ReflectionProperty($panel, 'renderHooks');
        $renderHooks = $reflection->getValue($panel);
        throw_unless(is_array($renderHooks), RuntimeException::class, 'Expected panel render hooks.');
        $bodyStartScopes = $renderHooks[PanelsRenderHook::BODY_START] ?? [];
        throw_unless(is_array($bodyStartScopes), RuntimeException::class, 'Expected body-start render hook scopes.');

        expect($panel->hasPlugin('filament-tour'))->toBeFalse()
            ->and($bodyStartScopes[''] ?? [])->toBeEmpty();
    } finally {
        config()->set('capell-welcome-tour.enabled', $originalEnabled);
        CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName);
    }
});
