<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Extenders\AdminPanelExtender;
use Capell\Core\Facades\CapellCore;
use Capell\WelcomeTour\Filament\Extenders\WelcomeTourPanelExtender;
use Capell\WelcomeTour\Providers\WelcomeTourServiceProvider;
use Filament\Panel;
use Filament\View\PanelsRenderHook;

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
        $provider = app()->getProvider(WelcomeTourServiceProvider::class);
        throw_unless($provider instanceof WelcomeTourServiceProvider, RuntimeException::class, 'Expected the Welcome Tour service provider to be loaded.');

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

        expect($welcomeExtenders)->toBeEmpty()
            ->and($exception)->toBeNull()
            ->and($rendered)->toBe('');
    } finally {
        $tagsProperty->setValue(app(), $originalTags);
        CapellCore::forcePackageInstalled(WelcomeTourServiceProvider::$packageName);
    }
});
