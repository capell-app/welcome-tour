<?php

declare(strict_types=1);

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Enums\DashboardEnum;
use Capell\Admin\Enums\UserSchemaHookEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Admin\Filament\Plugin\CapellAdminPlugin;
use Capell\Core\Support\Database\RuntimeSchemaState;
use Capell\Tests\Fixtures\Models\User;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Actions\CanShowWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\ResetUserWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SetWelcomeTourChecklistItemCompletionAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Capell\WelcomeTour\Data\WelcomeTourReadinessData;
use Capell\WelcomeTour\Enums\WelcomeTourReadinessStatus;
use Capell\WelcomeTour\Events\WelcomeTourCompleted;
use Capell\WelcomeTour\Events\WelcomeTourRestarted;
use Capell\WelcomeTour\Events\WelcomeTourSnoozed;
use Capell\WelcomeTour\Events\WelcomeTourStarted;
use Capell\WelcomeTour\Events\WelcomeTourStepCompleted;
use Capell\WelcomeTour\Filament\Concerns\HasContextualWelcomeTour;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Filament\Widgets\WelcomeTourChecklistFilamentWidget;
use Capell\WelcomeTour\Livewire\WelcomeTourOrchestrator;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\ContextualWelcomeTourRegistry;
use Capell\WelcomeTour\Support\WelcomeTourStepContributor;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;

use function Livewire\store;

use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('capell-welcome-tour.presentation_mode', false);
    $this->actingAsAdmin();
    CapellAdmin::clearWelcomeTourSteps();
    resolve(ContextualWelcomeTourRegistry::class)->clear();
});

it('registers its package view namespace', function (): void {
    expect(view()->make('capell-welcome-tour::livewire.welcome-tour-orchestrator'))->toBeInstanceOf(View::class);
});

it('renders a dashboard replay action with a stable tour target', function (): void {
    $html = view('capell-welcome-tour::filament.actions.replay-tour')->render();

    expect($html)
        ->toContain('data-tour-id="welcome-tour-dashboard"')
        ->toContain('capell-welcome-tour::restart');
});

it('cleans up keyboard dismissal listeners during Livewire navigation', function (): void {
    $html = view('capell-welcome-tour::livewire.welcome-tour-orchestrator', [
        'autoStart' => false,
        'tourIdToOpen' => null,
        'currentChapterKey' => null,
        'currentTargetSelector' => null,
    ])->render();

    expect($html)
        ->toContain('AbortController')
        ->toContain('livewire:navigating')
        ->toContain("event.key === 'Escape'")
        ->toContain("document.body.classList.contains('driver-active')");
});

it('waits for the requested filament tour registry entry before automatically opening the dashboard tour', function (): void {
    $html = view('capell-welcome-tour::livewire.welcome-tour-orchestrator', [
        'autoStart' => true,
        'tourIdToOpen' => 'capell_admin_welcome.dashboard',
        'currentChapterKey' => null,
        'currentTargetSelector' => null,
    ])->render();

    expect($html)
        ->toContain("Livewire.on('filament-tour::loaded-elements'")
        ->toContain('({ tours = [] })')
        ->toContain('tours.some((tour) => tour.id === `tour_${tourIdToOpen}`)')
        ->toContain("queueMicrotask(() => Livewire.dispatch('filament-tour::open-tour'")
        ->toContain("Livewire.dispatch('filament-tour::open-tour'")
        ->toContain('capell_admin_welcome.dashboard')
        ->toContain('stopWaitingForTourElements()')
        ->not->toContain('setTimeout(');
});

it('reports an unavailable target without completing its chapter', function (): void {
    $html = view('capell-welcome-tour::livewire.welcome-tour-orchestrator', [
        'autoStart' => false,
        'tourIdToOpen' => null,
        'currentChapterKey' => 'sites',
        'currentTargetSelector' => '[data-tour-id="welcome-tour-sites"]',
    ])->render();

    expect($html)
        ->toContain('document.querySelector(selector)')
        ->toContain('capell-welcome-tour::target-unavailable')
        ->not->toContain('capell-welcome-tour::complete-chapter')
        ->toContain('welcome-tour-sites');
});

it('uses the package dashboard page and registers the filament tour plugin', function (): void {
    $panel = Panel::make();

    CapellAdminPlugin::make()->register($panel);

    expect(CapellAdmin::getDashboardPage())->not->toBe(WelcomeTourDashboard::class)
        ->and($panel->getPages())->toContain(WelcomeTourDashboard::class)
        ->and($panel->hasPlugin('filament-tour'))->toBeTrue();
});

it('registers the onboarding checklist dashboard widget', function (): void {
    expect(CapellAdmin::getDashboardFilamentWidgets(DashboardEnum::Main))
        ->toContain(WelcomeTourChecklistFilamentWidget::class)
        ->and(WelcomeTourChecklistFilamentWidget::canView())->toBeTrue();
});

it('sends the welcome message as a persistent filament notification', function (): void {
    SchemaFacade::dropIfExists('sites');
    SchemaFacade::create('sites', static function (Blueprint $table): void {
        $table->id();
        $table->uuid('uuid');
    });
    DB::table('sites')->insert(['uuid' => (string) Str::uuid(), 'id' => 1]);
    resolve(RuntimeSchemaState::class)->refreshTable('sites');

    try {
        $widget = new WelcomeTourChecklistFilamentWidget;
        $widget->mount();

        $notifications = session()->get('filament.notifications');
        throw_unless(is_array($notifications), RuntimeException::class, 'Expected Filament notifications to be stored as an array.');

        $notification = reset($notifications);
        throw_unless(is_array($notification), RuntimeException::class, 'Expected one Filament notification payload.');

        $actions = $notification['actions'] ?? null;
        throw_unless(is_array($actions), RuntimeException::class, 'Expected Filament notification actions to be stored as an array.');

        $startAction = $actions[0] ?? null;
        $dismissAction = $actions[1] ?? null;
        throw_unless(is_array($startAction), RuntimeException::class, 'Expected the welcome-tour start action payload.');
        throw_unless(is_array($dismissAction), RuntimeException::class, 'Expected the welcome-tour dismiss action payload.');

        expect($notification['id'])->toBe('welcome-tour-introduction')
            ->and($notification['duration'])->toBe('persistent')
            ->and($notification['title'])->toBe(__('capell-welcome-tour::welcome_tour.callout_heading'))
            ->and($notification['body'])->toBe(__('capell-welcome-tour::welcome_tour.callout_description'))
            ->and($actions)->toHaveCount(2)
            ->and($startAction['event'])->toBe('capell-welcome-tour::start')
            ->and($startAction['shouldClose'])->toBeTrue()
            ->and($dismissAction['event'])->toBe('capell-welcome-tour::dismiss')
            ->and($dismissAction['shouldClose'])->toBeTrue();
    } finally {
        SchemaFacade::dropIfExists('sites');
        resolve(RuntimeSchemaState::class)->forgetTable('sites');
    }
});

it('activates a presentation tour before the renderer resolves its chapters', function (): void {
    config()->set('capell-welcome-tour.presentation_mode', true);

    $user = User::factory()->create();
    test()->actingAs($user);

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.dashboard',
        title: 'Dashboard',
        description: 'Start here',
        chapter: 'dashboard',
        route: '/admin',
    );

    $request = Request::create('/admin');
    $session = resolve(SessionManager::class)->driver();
    throw_unless($session instanceof Session, RuntimeException::class, 'Expected a Laravel session driver.');
    $request->setLaravelSession($session);
    app()->instance('request', $request);

    $html = (new WelcomeTourOrchestrator)->render()->render();

    expect(session()->get('capell_welcome_tour.active'))->toBeTrue()
        ->and((new WelcomeTourDashboard)->tours())->toHaveCount(1)
        ->and($html)->toContain('capell_admin_welcome.dashboard');
});

it('opens the first configured chapter for an active normal session', function (): void {
    $user = User::factory()->create();
    test()->actingAs($user);

    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.overview',
        title: 'Overview',
        description: 'Start here',
        chapter: 'overview',
        route: '/admin',
    );
    CapellAdmin::registerWelcomeTourStep(
        key: 'capell-welcome-tour.settings',
        title: 'Settings',
        description: 'Configure the workspace',
        chapter: 'settings',
        route: '/admin',
    );

    session()->put('capell_welcome_tour.active', true);

    $request = Request::create('/admin');
    $session = resolve(SessionManager::class)->driver();
    throw_unless($session instanceof Session, RuntimeException::class, 'Expected a Laravel session driver.');
    $request->setLaravelSession($session);
    app()->instance('request', $request);

    $html = (new WelcomeTourOrchestrator)->render()->render();

    expect($html)
        ->toContain('capell_admin_welcome.overview')
        ->not->toContain('capell_admin_welcome.settings')
        ->toContain('if (tourIdToOpen)')
        ->toContain("Livewire.dispatch('filament-tour::open-tour'");
});

it('lets users hide the checklist and reveal it again when replaying the tour', function (): void {
    $user = User::factory()->create();
    test()->actingAs($user);
    $widget = new WelcomeTourChecklistFilamentWidget;
    $request = Request::create(
        '/livewire-cfab9099/update',
        'POST',
        server: ['HTTP_REFERER' => 'http://localhost/admin?site=1'],
    );
    app()->instance('request', $request);
    $widget->mount();

    expect($widget->shouldShowChecklist())->toBeTrue();

    $widget->dismissChecklist();

    expect($widget->shouldShowChecklist())->toBeFalse();

    $widget->startTour();

    expect($widget->shouldShowChecklist())->toBeTrue()
        ->and($widget->returnPath)->toBe('/admin?site=1');
});

it('redirects tour replay to its locked page path instead of the Livewire update endpoint', function (): void {
    $user = User::factory()->create();
    test()->actingAs($user);
    $request = Request::create(
        '/livewire-cfab9099/update',
        'POST',
        server: ['HTTP_REFERER' => 'http://localhost/admin?site=1'],
    );
    app()->instance('request', $request);
    $widget = new WelcomeTourChecklistFilamentWidget;
    $widget->mount();
    $widget->startTour();

    expect(store($widget)->get('redirect'))->toBe('/admin?site=1');

    $fallbackWidget = new WelcomeTourChecklistFilamentWidget;
    $fallbackWidget->startTour();

    expect(store($fallbackWidget)->get('redirect'))->toBe('/admin');

    $externalRefererRequest = Request::create(
        '/livewire-cfab9099/update',
        'POST',
        server: ['HTTP_REFERER' => 'https://example.com/steal-session'],
    );
    app()->instance('request', $externalRefererRequest);
    $externalRefererWidget = new WelcomeTourChecklistFilamentWidget;
    $externalRefererWidget->mount();
    $externalRefererWidget->startTour();

    expect(store($externalRefererWidget)->get('redirect'))->toBe('/admin');
});

it('replays from chapter one without clearing dismissal history', function (): void {
    $user = User::factory()->create();
    test()->actingAs($user);

    RecordWelcomeTourStepAction::run($user, 'capell-welcome-tour.dashboard');
    SetUserWelcomeTourPreferenceAction::run($user, enabled: false);

    (new WelcomeTourOrchestrator)->restart();

    expect(GetUserWelcomeTourStateAction::run($user)->completedStepKeys)->toBe([])
        ->and(CanShowWelcomeTourAction::run($user))->toBeFalse()
        ->and(session()->get('capell_welcome_tour.active'))->toBeTrue()
        ->and(session()->get('capell_welcome_tour.show_checklist'))->toBeTrue();
});

it('starts the tour from the welcome notification event', function (): void {
    $user = auth()->user();
    throw_unless($user instanceof User, RuntimeException::class, 'Expected an authenticated test user.');

    (new WelcomeTourOrchestrator)->start();

    expect(session()->get('capell_welcome_tour.active'))->toBeTrue()
        ->and(session()->get('capell_welcome_tour.show_checklist'))->toBeTrue()
        ->and(GetUserWelcomeTourStateAction::run($user)->completedStepKeys)->toBe([]);
});

it('registers application manifest steps ahead of stale configured defaults', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [[
        'key' => 'capell-welcome-tour.dashboard',
        'title' => 'Stale dashboard title',
        'description' => 'Stale dashboard description',
        'element' => '.fi-page',
        'chapter' => 'dashboard',
        'route' => '/admin',
    ]];
    $settings->save();

    config()->set('capell-welcome-tour.manifest_steps', [[
        'key' => 'capell-welcome-tour.dashboard',
        'title' => 'Dashboard',
        'description' => 'Your overview',
        'element' => '[data-tour-id="welcome-tour-dashboard"]',
        'chapter' => 'dashboard',
        'route' => '@dashboard',
        'sort' => 10,
    ]]);

    resolve(WelcomeTourStepRegistrar::class)->register();

    $steps = CapellAdmin::getWelcomeTourSteps();

    expect($steps)->toHaveCount(1)
        ->and($steps[0]->key)->toBe('capell-welcome-tour.dashboard')
        ->and($steps[0]->chapter)->toBe('dashboard')
        ->and($steps[0]->route)->toBe('/admin')
        ->and($steps[0]->element)->toBe('[data-tour-id="welcome-tour-dashboard"]')
        ->and(welcomeTourText($steps[0]->title))->toBe('Dashboard');
});

it('skips manifest chapters whose destination is unavailable', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [];
    $settings->save();

    config()->set('capell-welcome-tour.manifest_steps', [[
        'key' => 'missing',
        'title' => 'Missing',
        'description' => 'Unavailable destination',
        'element' => '[data-tour-id="missing"]',
        'chapter' => 'missing',
        'route' => '/admin/route-that-does-not-exist',
    ]]);

    resolve(WelcomeTourStepRegistrar::class)->register();

    expect(CapellAdmin::getWelcomeTourSteps())->toBe([]);
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
    config()->set('capell-welcome-tour.checklist', [[
        'key' => 'create-page',
        'label' => 'Create your first page',
        'description' => 'Create content.',
        'url' => '/admin/pages/create',
        'complete_when' => 'table-has-rows:pages',
    ]]);

    $items = BuildWelcomeTourChecklistAction::run();

    expect($items)->toHaveCount(1)
        ->and($items[0]->key)->toBe('create-page')
        ->and($items[0]->complete)->toBeFalse();
});

it('builds checklist items from typed tri-state readiness resolvers', function (): void {
    app()->bind(WelcomeTourReadinessResolver::class, fn (): WelcomeTourReadinessResolver => new class implements WelcomeTourReadinessResolver
    {
        public function resolve(?Model $user = null, array $context = []): WelcomeTourReadinessData
        {
            return new WelcomeTourReadinessData(
                status: WelcomeTourReadinessStatus::Blocked,
                explanation: 'Restore evidence is stale.',
                recoveryUrl: '/admin/backups',
            );
        }
    });

    config()->set('capell-welcome-tour.checklist', [[
        'key' => 'restore-drill',
        'label' => 'Complete a restore drill',
        'description' => 'Restore into an isolated target.',
        'resolver' => WelcomeTourReadinessResolver::class,
        'url' => '/admin/backups',
    ]]);

    $items = BuildWelcomeTourChecklistAction::run();

    expect($items)->toHaveCount(1)
        ->and($items[0]->status)->toBe(WelcomeTourReadinessStatus::Blocked)
        ->and($items[0]->complete)->toBeFalse()
        ->and($items[0]->explanation)->toBe('Restore evidence is stale.');
});

it('rejects external recovery urls from readiness resolvers', function (): void {
    app()->bind(WelcomeTourReadinessResolver::class, fn (): WelcomeTourReadinessResolver => new class implements WelcomeTourReadinessResolver
    {
        public function resolve(?Model $user = null, array $context = []): WelcomeTourReadinessData
        {
            return new WelcomeTourReadinessData(
                status: WelcomeTourReadinessStatus::ActionRequired,
                explanation: 'Complete setup.',
                recoveryUrl: 'https://malicious.example/steal-session',
            );
        }
    });

    config()->set('capell-welcome-tour.checklist', [[
        'key' => 'complete-setup',
        'label' => 'Complete setup',
        'description' => 'Finish configuring the site.',
        'resolver' => WelcomeTourReadinessResolver::class,
    ]]);

    $items = BuildWelcomeTourChecklistAction::run();

    expect($items)->toHaveCount(1)
        ->and($items[0]->url)->toBeNull();
});

it("includes a user's manually completed checklist items", function (): void {
    config()->set('capell-welcome-tour.checklist', [[
        'key' => 'create-page',
        'label' => 'Create your first page',
        'description' => 'Create content.',
        'url' => '/admin/pages/create',
        'complete_when' => 'table-has-rows:pages',
    ]]);

    $user = auth()->user();
    throw_unless($user instanceof User, RuntimeException::class, 'Expected an authenticated test user.');

    SetWelcomeTourChecklistItemCompletionAction::run($user, 'create-page', true);

    $pageItem = collect(BuildWelcomeTourChecklistAction::run($user))
        ->firstWhere('key', 'create-page');
    throw_unless($pageItem instanceof WelcomeTourChecklistItemData, RuntimeException::class, 'Expected the create-page checklist item.');

    expect($pageItem->complete)->toBeTrue()
        ->and($pageItem->manuallyCompleted)->toBeTrue();
});

it('registers configured contextual tours for pages, media, and sites', function (): void {
    config()->set('capell-welcome-tour.contextual_tours', collect([
        'capell_admin_sites',
        'capell_admin_pages',
        'capell_admin_media',
    ])->mapWithKeys(fn (string $tourKey): array => [$tourKey => [[
        'key' => $tourKey . '.overview',
        'title' => 'Tour overview',
        'description' => 'Review this surface.',
        'element' => '.fi-page',
    ]]])->all());

    $configuredTours = config('capell-welcome-tour.contextual_tours');

    if (! is_array($configuredTours)) {
        throw new RuntimeException('Configured contextual welcome tours must be an array.');
    }

    resolve(ContextualWelcomeTourRegistry::class)->registerConfiguredTours($configuredTours);

    $registry = resolve(ContextualWelcomeTourRegistry::class);

    expect($registry->stepsFor('capell_admin_sites'))->toHaveCount(1)
        ->and($registry->stepsFor('capell_admin_pages'))->toHaveCount(1)
        ->and($registry->stepsFor('capell_admin_media'))->toHaveCount(1)
        ->and(welcomeTourText($registry->stepsFor('capell_admin_pages')[0]->title))->toBe('Tour overview');
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
        chapter: 'dashboard',
        route: '/admin',
    );

    session()->put('capell_welcome_tour.active', true);

    $tours = (new WelcomeTourDashboard)->tours();

    expect($tours)->toHaveCount(1)
        ->and($tours[0]->getId())->toBe('capell_admin_welcome.dashboard')
        ->and($tours[0]->getSteps())->toHaveCount(1)
        ->and($tours[0]->getSteps()[0]->getDispatchOnNext())->toBe([
            'name' => 'capell-welcome-tour::complete-chapter',
            'params' => ['chapterKey' => 'dashboard'],
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
        chapter: 'dashboard',
        route: '/admin',
    );

    session()->put('capell_welcome_tour.active', true);

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

it('does not use a scalar role to bypass team scoped tour restrictions', function (): void {
    $user = User::factory()->create();
    $user->setAttribute('role', 'admin');
    config(['permission.teams' => true]);
    resolve(PermissionRegistrar::class)->teams = true;

    try {
        expect(CanShowWelcomeTourStepAction::run(['roles' => ['admin']], $user))->toBeFalse();
    } finally {
        config(['permission.teams' => false]);
        resolve(PermissionRegistrar::class)->teams = false;
    }
});
