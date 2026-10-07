<?php

declare(strict_types=1);

use Capell\Admin\Contracts\Bridges\UserResourceBridge;
use Capell\Tests\Support\PackageInstallationTestCase;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;

it('registers installed runtime once during in-process installation', function (): void {
    /** @return list<class-string<WelcomeTourUserResourceBridge>> */
    $surface = static function (Application $app): array {
        $contributions = [];
        foreach ($app->tagged(UserResourceBridge::TAG) as $contribution) {
            if ($contribution instanceof WelcomeTourUserResourceBridge) {
                $contributions[] = $contribution::class;
            }
        }

        return $contributions;
    };

    $fresh = [];
    PackageInstallationTestCase::assertFreshInstalledBoot('welcome-tour', static function (Application $app) use ($surface, &$fresh): void {
        $fresh = $surface($app);
        expect($fresh)->toBe([WelcomeTourUserResourceBridge::class]);
    });

    PackageInstallationTestCase::assertInProcessInstallation('welcome-tour', static function (Application $app, Closure $refresh) use ($surface, $fresh): void {
        expect($surface($app))->toBe([]);
        $refresh();
        expect($surface($app))->toBe($fresh);

        $schedule = $app->make(Schedule::class);
        $scheduledEvents = $schedule->events();
        $listeners = $app->make(Dispatcher::class)->getRawListeners();
        $refresh();
        expect($surface($app))->toBe($fresh)
            ->and($app->make(Dispatcher::class)->getRawListeners())->toBe($listeners)
            ->and($schedule->events())->toBe($scheduledEvents);
    });
});

it('registers the runtime after enabling a previously disabled package in the same process', function (): void {
    PackageInstallationTestCase::assertInProcessInstallation('welcome-tour', static function (Application $app, Closure $refresh): void {
        $originalEnabled = config('capell-welcome-tour.enabled');

        try {
            config(['capell-welcome-tour.enabled' => false]);
            $refresh();

            expect($app->tagged(UserResourceBridge::TAG))
                ->not->toContain(WelcomeTourUserResourceBridge::class);

            config(['capell-welcome-tour.enabled' => true]);
            $refresh();

            expect(collect($app->tagged(UserResourceBridge::TAG))
                ->filter(static fn (object $bridge): bool => $bridge instanceof WelcomeTourUserResourceBridge))
                ->toHaveCount(1);
        } finally {
            config(['capell-welcome-tour.enabled' => $originalEnabled]);
        }
    });
});
