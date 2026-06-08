<?php

declare(strict_types=1);

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Enums\UserSchemaHookEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Plugin\CapellAdminPlugin;
use Capell\Tests\Fixtures\Models\User;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\ResetUserWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Events\WelcomeTourCompleted;
use Capell\WelcomeTour\Events\WelcomeTourRestarted;
use Capell\WelcomeTour\Events\WelcomeTourSnoozed;
use Capell\WelcomeTour\Events\WelcomeTourStarted;
use Capell\WelcomeTour\Events\WelcomeTourStepCompleted;
use Capell\WelcomeTour\Filament\Concerns\HasContextualWelcomeTour;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Filament\Widgets\WelcomeTourChecklistWidget;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\ContextualWelcomeTourRegistry;
use Capell\WelcomeTour\Support\WelcomeTourStepContributor;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

beforeEach(function (): void {
    CapellAdmin::clearWelcomeTourSteps();
    resolve(ContextualWelcomeTourRegistry::class)->clear();
});

it('uses the package dashboard page and registers the filament tour plugin', function (): void {
    expect(CapellAdmin::getDashboardPage())->toBe(WelcomeTourDashboard::class);

    $panel = Panel::make();

    CapellAdminPlugin::make()->register($panel);

    expect($panel->hasPlugin('filament-tour'))->toBeTrue();
});

it('registers the onboarding checklist dashboard widget', function (): void {
    expect(CapellAdmin::getDashboardWidgets(DashboardEnum::Main))
        ->toContain(WelcomeTourChecklistWidget::class);
});

it('registers default welcome tour steps from configured translation keys', function (): void {
    resolve(WelcomeTourStepRegistrar::class)->register();

    $steps = CapellAdmin::getWelcomeTourSteps();

    expect($steps)->toHaveCount(7)
        ->and($steps[0]->key)->toBe('capell-welcome-tour.introduction')
        ->and($steps[1]->element)->toBe('.fi-sidebar-nav')
        ->and($steps[3]->key)->toBe('capell-welcome-tour.sites')
        ->and($steps[4]->key)->toBe('capell-welcome-tour.pages')
        ->and($steps[5]->key)->toBe('capell-welcome-tour.media')
        ->and(welcomeTourText($steps[0]->title))->toBe('Welcome to Capell')
        ->and(welcomeTourText($steps[0]->description))->toBe('This quick tour highlights the main admin areas you will use to manage sites and content.');
});

it('does not fall back to default steps when settings are explicitly empty', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [];
    $settings->save();

    resolve(WelcomeTourStepRegistrar::class)->register();

    expect(CapellAdmin::getWelcomeTourSteps())->toBe([]);
});

it('passes plain translated step descriptions to the tour package once', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [
        [
            'key' => 'custom.copy',
            'title' => 'Copy',
            'description' => "Use site's content & media.",
            'element' => null,
            'visible' => true,
        ],
    ];
    $settings->save();

    resolve(WelcomeTourStepRegistrar::class)->register();

    $steps = CapellAdmin::getWelcomeTourSteps();

    expect(welcomeTourText($steps[0]->description))
        ->toBe("Use site's content & media.")
        ->not->toContain('&#039;')
        ->not->toContain('&amp;');
});

it('accepts literal step titles and filters configured steps by role and first-run window', function (): void {
    $recentAdmin = User::factory()->create(['created_at' => now()->subDay()]);
    $recentAdmin->setAttribute('role', 'admin');

    test()->actingAs($recentAdmin);

    $settings = WelcomeTourSettings::instance();
    $settings->steps = [
        [
            'key' => 'custom.literal',
            'title' => 'Literal tour title',
            'description' => 'Literal tour description',
            'element' => null,
            'visible' => true,
            'roles' => ['admin'],
            'user_created_within_days' => 7,
        ],
        [
            'key' => 'custom.editor-only',
            'title' => 'Editor only',
            'description' => 'Hidden for admins',
            'element' => null,
            'visible' => true,
            'roles' => ['editor'],
        ],
    ];
    $settings->save();

    resolve(WelcomeTourStepRegistrar::class)->register();

    $steps = CapellAdmin::getWelcomeTourSteps();

    expect($steps)->toHaveCount(1)
        ->and($steps[0]->key)->toBe('custom.literal')
        ->and(welcomeTourText($steps[0]->title))->toBe('Literal tour title')
        ->and(welcomeTourText($steps[0]->description))->toBe('Literal tour description');
});

it('builds the onboarding checklist from configured setup conditions', function (): void {
    $items = BuildWelcomeTourChecklistAction::run();

    expect($items)->toHaveCount(3)
        ->and($items[0]->key)->toBe('create-site')
        ->and($items[0]->complete)->toBeFalse();
});

it('registers configured contextual tours for pages, media, and sites', function (): void {
    resolve(ContextualWelcomeTourRegistry::class)->registerConfiguredTours(config('capell-welcome-tour.contextual_tours'));

    $registry = resolve(ContextualWelcomeTourRegistry::class);

    expect($registry->stepsFor('capell_admin_sites'))->toHaveCount(2)
        ->and($registry->stepsFor('capell_admin_pages'))->toHaveCount(2)
        ->and($registry->stepsFor('capell_admin_media'))->toHaveCount(2)
        ->and(welcomeTourText($registry->stepsFor('capell_admin_pages')[0]->title))->toBe('Organise the page tree');
});

it('allows packages to contribute contextual page steps by tour key', function (): void {
    WelcomeTourStepContributor::contextualStep(
        tourKey: 'capell_admin_pages',
        key: 'demo-kit.pages.helper',
        title: 'Demo page helper',
        description: 'Use this package-specific page helper.',
        element: '#demo-page-helper',
        sort: 5,
    );

    $steps = resolve(ContextualWelcomeTourRegistry::class)->stepsFor('capell_admin_pages');

    expect($steps)->toHaveCount(1)
        ->and($steps[0]->key)->toBe('demo-kit.pages.helper')
        ->and($steps[0]->element)->toBe('#demo-page-helper');
});

it('builds contextual tours from the opt-in filament trait', function (): void {
    Event::fake([WelcomeTourStarted::class]);

    $user = User::factory()->create();
    test()->actingAs($user);
    request()->server->set('REQUEST_URI', '/admin/pages');
    request()->server->set('PATH_INFO', '/admin/pages');

    WelcomeTourStepContributor::contextualStep(
        tourKey: 'capell_admin_pages',
        key: 'capell-welcome-tour.pages.context.tree',
        title: 'Pages',
        description: 'Use the page tree.',
        element: '.fi-ta',
    );

    $page = new class
    {
        use HasContextualWelcomeTour;

        protected string $welcomeTourKey = 'capell_admin_pages';
    };

    $tours = $page->tours();

    expect($tours)->toHaveCount(1)
        ->and($tours[0]->getId())->toBe('capell_admin_pages')
        ->and($tours[0]->getSteps())->toHaveCount(1);

    Event::assertDispatched(WelcomeTourStarted::class);
});

function welcomeTourText(Closure|string|HtmlString|View $value): string
{
    if ($value instanceof Closure) {
        $value = $value();
    }

    if ($value instanceof HtmlString) {
        return $value->toHtml();
    }

    if ($value instanceof View) {
        return $value->render();
    }

    return (string) $value;
}

it('builds the dashboard welcome tour for users who have it enabled', function (): void {
    Event::fake([WelcomeTourStarted::class]);

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
            'params' => ['stepKey' => 'capell-welcome-tour.menu'],
        ]);

    Event::assertDispatched(WelcomeTourStarted::class);
});

it('reads already registered steps when building the dashboard tour', function (): void {
    $this->app->instance(WelcomeTourStepRegistrar::class, new class
    {
        public function register(): never
        {
            throw new LogicException('The dashboard should not register configured steps while rendering tours.');
        }
    });

    $user = User::factory()->create();
    test()->actingAs($user);

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.menu',
        title: 'Menu',
        description: 'Use the menu',
        element: '.fi-sidebar-nav',
    );

    expect((new WelcomeTourDashboard)->tours())->toHaveCount(1);
});

it('records tour progress and resumes at the first incomplete step', function (): void {
    Event::fake([WelcomeTourStepCompleted::class]);

    $user = User::factory()->create();
    test()->actingAs($user);

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.menu',
        title: 'Menu',
        description: 'Use the menu',
        element: '.fi-sidebar-nav',
        sort: 10,
    );

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.dashboard',
        title: 'Dashboard',
        description: 'Use the dashboard',
        element: null,
        sort: 20,
    );

    (new WelcomeTourDashboard)->recordWelcomeTourStep('capell-welcome-tour.menu');

    $remainingSteps = ResolveWelcomeTourStepsForUserAction::run($user, CapellAdmin::getWelcomeTourSteps());

    expect(GetUserWelcomeTourStateAction::run($user)->completedStepKeys)
        ->toBe(['capell-welcome-tour.menu'])
        ->and($remainingSteps)->toHaveCount(1)
        ->and($remainingSteps[0]->key)->toBe('capell-welcome-tour.dashboard');

    Event::assertDispatched(WelcomeTourStepCompleted::class);
});

it('can restart the tour for a user after progress or dismissal', function (): void {
    Event::fake([WelcomeTourRestarted::class]);

    $user = User::factory()->create();

    RecordWelcomeTourStepAction::run($user, 'capell-welcome-tour.menu');
    SetUserWelcomeTourPreferenceAction::run($user, enabled: false);

    ResetUserWelcomeTourAction::run($user);

    $state = GetUserWelcomeTourStateAction::run($user);

    expect($state->completedStepKeys)->toBe([])
        ->and($state->dismissed)->toBeFalse()
        ->and(CanShowWelcomeTourAction::run($user))->toBeTrue();

    Event::assertDispatched(WelcomeTourRestarted::class);
});

it('can snooze the tour for a user and clear snooze on restart', function (): void {
    Event::fake([WelcomeTourSnoozed::class, WelcomeTourRestarted::class]);

    $user = User::factory()->create();

    SnoozeUserWelcomeTourAction::run($user);

    expect(CanShowWelcomeTourAction::run($user))->toBeFalse()
        ->and(GetUserWelcomeTourStateAction::run($user)->isSnoozed())->toBeTrue();

    ResetUserWelcomeTourAction::run($user);

    expect(CanShowWelcomeTourAction::run($user))->toBeTrue()
        ->and(GetUserWelcomeTourStateAction::run($user)->isSnoozed())->toBeFalse();

    Event::assertDispatched(WelcomeTourSnoozed::class);
    Event::assertDispatched(WelcomeTourRestarted::class);
});

it('persists user preferences in package state when dismissed hints are absent', function (): void {
    $user = User::factory()->create();

    SchemaFacade::table('users', function (Blueprint $table): void {
        $table->dropColumn('dismissed_hints');
    });

    SetUserWelcomeTourPreferenceAction::run($user, enabled: false);

    expect(CanShowWelcomeTourAction::run($user))->toBeFalse()
        ->and(GetUserWelcomeTourStateAction::run($user)->dismissed)->toBeTrue();

    SetUserWelcomeTourPreferenceAction::run($user, enabled: true);

    expect(CanShowWelcomeTourAction::run($user))->toBeTrue()
        ->and(GetUserWelcomeTourStateAction::run($user)->dismissed)->toBeFalse();
});

it('stores the user preference when the dashboard receives the tour dismiss event', function (): void {
    Event::fake([WelcomeTourCompleted::class]);

    $user = User::factory()->create();
    test()->actingAs($user);

    (new WelcomeTourDashboard)->dismissWelcomeTour();

    expect(CanShowWelcomeTourAction::run($user->refresh()))->toBeFalse();

    Event::assertDispatched(WelcomeTourCompleted::class);
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
