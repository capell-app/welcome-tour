<?php

declare(strict_types=1);

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Enums\UserSchemaHookEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Plugin\CapellAdminPlugin;
use Capell\Tests\Fixtures\Models\User;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    CapellAdmin::clearWelcomeTourSteps();
});

it('uses the package dashboard page and registers the filament tour plugin', function (): void {
    expect(CapellAdmin::getDashboardPage())->toBe(WelcomeTourDashboard::class);

    $panel = Panel::make();

    CapellAdminPlugin::make()->register($panel);

    expect($panel->hasPlugin('filament-tour'))->toBeTrue();
});

it('registers default welcome tour steps from configured translation keys', function (): void {
    resolve(WelcomeTourStepRegistrar::class)->register();

    $steps = CapellAdmin::getWelcomeTourSteps();

    expect($steps)->toHaveCount(4)
        ->and($steps[0]->key)->toBe('capell-welcome-tour.introduction')
        ->and($steps[1]->element)->toBeNull()
        ->and(($steps[0]->title)())->toBe('Welcome to Capell')
        ->and(($steps[0]->description)())->toBe('This quick tour highlights the main admin areas you will use to manage sites and content.');
});

it('does not fall back to default steps when settings are explicitly empty', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [];
    $settings->save();

    resolve(WelcomeTourStepRegistrar::class)->register();

    expect(CapellAdmin::getWelcomeTourSteps())->toBe([]);
});

it('escapes configured step descriptions before passing them to the tour package', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [
        [
            'key' => 'custom.unsafe',
            'title' => 'Unsafe',
            'description' => '<script>alert("xss")</script>',
            'element' => null,
            'visible' => true,
        ],
    ];
    $settings->save();

    resolve(WelcomeTourStepRegistrar::class)->register();

    $steps = CapellAdmin::getWelcomeTourSteps();

    expect(($steps[0]->description)())
        ->toBe('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')
        ->not->toContain('<script>');
});

it('builds the dashboard welcome tour for users who have it enabled', function (): void {
    $user = User::factory()->create();
    test()->actingAs($user);

    $settings = WelcomeTourSettings::instance();
    $settings->steps = [];
    $settings->save();

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.menu',
        title: 'Menu',
        description: 'Use the menu',
        element: '.fi-sidebar-nav',
    );

    $tours = (new WelcomeTourDashboard)->tours();

    expect($tours)->toHaveCount(1)
        ->and($tours[0]->getId())->toBe('capell_admin_welcome')
        ->and($tours[0]->getSteps())->toHaveCount(1)
        ->and($tours[0]->getSteps()[0]->getDispatchOnNext())->toBe([
            'name' => 'capell-welcome-tour::dismiss',
            'params' => [],
        ]);
});

it('stores the user preference when the dashboard receives the tour dismiss event', function (): void {
    $user = User::factory()->create();
    test()->actingAs($user);

    (new WelcomeTourDashboard)->dismissWelcomeTour();

    expect(CanShowWelcomeTourAction::run($user->refresh()))->toBeFalse();
});

it('does not build the dashboard welcome tour for users who have it disabled', function (): void {
    $user = User::factory()->create();
    SetUserWelcomeTourPreferenceAction::run($user, enabled: false);
    test()->actingAs($user);

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.menu',
        title: 'Menu',
        description: 'Use the menu',
        element: '.fi-sidebar-nav',
    );

    expect((new WelcomeTourDashboard)->tours())->toBe([]);
});

it('does not build the dashboard welcome tour when disabled globally', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->enabled = false;
    $settings->save();

    $user = User::factory()->create();
    test()->actingAs($user);

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.menu',
        title: 'Menu',
        description: 'Use the menu',
        element: '.fi-sidebar-nav',
    );

    expect(CanShowWelcomeTourAction::run($user))->toBeFalse()
        ->and((new WelcomeTourDashboard)->tours())->toBe([]);
});

it('stores welcome tour visibility per user', function (): void {
    $enabledUser = User::factory()->create();
    $disabledUser = User::factory()->create();

    SetUserWelcomeTourPreferenceAction::run($disabledUser, enabled: false);

    expect(CanShowWelcomeTourAction::run($enabledUser))->toBeTrue()
        ->and(CanShowWelcomeTourAction::run($disabledUser))->toBeFalse();

    SetUserWelcomeTourPreferenceAction::run($disabledUser, enabled: true);

    expect(CanShowWelcomeTourAction::run($disabledUser))->toBeTrue();
});

it('preserves unrelated dismissed hints when storing welcome tour visibility', function (): void {
    $user = User::factory()->create();

    DB::table('users')
        ->where('id', $user->getKey())
        ->update(['dismissed_hints' => json_encode(['other-hint'], JSON_THROW_ON_ERROR)]);

    SetUserWelcomeTourPreferenceAction::run($user, enabled: false);

    $dismissedHints = json_decode(
        (string) DB::table('users')->where('id', $user->getKey())->value('dismissed_hints'),
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($dismissedHints)->toBe([
        'other-hint',
        CanShowWelcomeTourAction::DISMISSED_HINT_KEY,
    ]);
});

it('only adds the user resource tour toggle while editing users', function (): void {
    $bridge = new WelcomeTourUserResourceBridge;
    $record = User::factory()->create();

    expect($bridge->extendComponentsForHook(
        Schema::make(),
        UserSchemaHookEnum::AfterIdentity,
        UserSchemaContextData::forCreate(),
    ))->toBe([])
        ->and($bridge->extendComponentsForHook(
            Schema::make(),
            UserSchemaHookEnum::AfterIdentity,
            UserSchemaContextData::forEdit($record, [], 'default'),
        ))->toHaveCount(1);
});
