<?php

declare(strict_types=1);

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Enums\UserSchemaHookEnum;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Tests\Fixtures\Models\User;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction;
use Capell\WelcomeTour\Livewire\WelcomeTourOrchestrator;
use Capell\WelcomeTour\Livewire\WelcomeTourSettingsControls;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\State\DatabaseWelcomeTourStateStore;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Capell\WelcomeTour\Support\WelcomeTourUserResourceBridge;
use Capell\WelcomeTour\Tests\Fixtures\WelcomeTourSettingsForm;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

it('keeps contextual dismissal separate from dashboard eligibility', function (): void {
    config()->set('capell-welcome-tour.presentation_mode', false);
    $this->actingAsAdmin();
    $user = $this->authenticatedUser();
    SetUserWelcomeTourPreferenceAction::run($user, false, 'pages');

    expect(CanShowWelcomeTourAction::run($user, 'pages'))->toBeFalse()
        ->and(CanShowWelcomeTourAction::run($user))->toBeTrue()
        ->and(CanShowWelcomeTourAction::run($user, 'media'))->toBeTrue();
});

beforeEach(function (): void {
    config()->set('capell-welcome-tour.presentation_mode', false);
    $this->actingAsAdmin();
    CapellAdmin::clearWelcomeTourSteps();
});

it('renders an empty guided tour with the shipped checklist and personal controls', function (): void {
    Livewire::test(WelcomeTourSettingsControls::class)
        ->assertSee('Getting started')
        ->assertSee('No guided steps are registered')
        ->assertSee('Preview as me')
        ->assertSee('Restart my tour')
        ->assertDontSee('CSS selector')
        ->call('preview')->assertHasErrors('tour');
});

it('renders only permitted chapter titles and escapes authored text', function (): void {
    CapellAdmin::registerWelcomeTourStep('one', '<script>unsafe</script>', 'Copy', chapter: 'one', route: '/admin');
    CapellAdmin::registerWelcomeTourStep('secret', 'Secret chapter', 'Secret', visible: false, chapter: 'secret', route: '/admin');
    Livewire::test(WelcomeTourSettingsControls::class)
        ->assertSee('<script>unsafe</script>')
        ->assertDontSeeHtml('<script>unsafe</script>')
        ->assertDontSee('Secret chapter');
});

it('previews with the existing registry and leaves saved progress dismissal and snooze unchanged', function (): void {
    $user = $this->authenticatedUser();
    Route::get('/tour-test', fn (): string => 'Tour');
    CapellAdmin::registerWelcomeTourStep('one', 'One', 'Copy', chapter: 'one', route: '/tour-test');
    RecordWelcomeTourStepAction::run($user, 'saved');
    SetUserWelcomeTourPreferenceAction::run($user, false);
    SnoozeUserWelcomeTourAction::run($user, 24);
    $store = resolve(DatabaseWelcomeTourStateStore::class);
    $before = $store->state($user, 'capell_admin_welcome');

    Livewire::test(WelcomeTourSettingsControls::class)->call('preview')->assertRedirect('/tour-test');
    expect(resolve(WelcomeTourStateStoreResolver::class)->isPreview())->toBeTrue();
    Livewire::test(WelcomeTourOrchestrator::class)->call('recordStep', 'one')->call('dismiss');

    expect($store->state($user, 'capell_admin_welcome'))->toEqual($before)
        ->and(CanShowWelcomeTourAction::run($user))->toBeFalse()
        ->and(session()->has('capell_welcome_tour.preview_user'))->toBeFalse();
});

it('restarts only the current users dashboard progress', function (): void {
    $user = $this->authenticatedUser();
    $other = User::factory()->create();
    Route::get('/tour-test', fn (): string => 'Tour');
    CapellAdmin::registerWelcomeTourStep('one', 'One', 'Copy', chapter: 'one', route: '/tour-test');
    foreach ([[$user, 'capell_admin_welcome'], [$user, 'media'], [$other, 'capell_admin_welcome']] as [$owner, $key]) {
        RecordWelcomeTourStepAction::run($owner, 'one', $key);
    }
    Livewire::test(WelcomeTourSettingsControls::class)->call('restart')->assertRedirect('/tour-test');
    expect(GetUserWelcomeTourStateAction::run($user)->completedStepKeys)->toBe([])
        ->and(GetUserWelcomeTourStateAction::run($user, 'media')->completedStepKeys)->toBe(['one'])
        ->and(GetUserWelcomeTourStateAction::run($other)->completedStepKeys)->toBe(['one']);
});

it('rechecks extension management permission on control requests', function (): void {
    $this->actingAsUser();
    Livewire::test(WelcomeTourSettingsControls::class)->assertForbidden();
});

it('does not start a disabled tour or an unresolved registered destination', function (): void {
    CapellAdmin::registerWelcomeTourStep('one', 'One', 'Copy', chapter: 'one', route: '/missing-tour');
    Livewire::test(WelcomeTourSettingsControls::class)->call('preview')->assertHasErrors('tour');
    $settings = WelcomeTourSettings::instance();
    $settings->enabled = false;
    $settings->save();
    Livewire::test(WelcomeTourSettingsControls::class)->call('restart')->assertHasErrors('tour');
    expect(session()->has('capell_welcome_tour.active'))->toBeFalse();
});

it('enables account eligibility without restarting completed steps or checklist tasks', function (): void {
    $user = $this->authenticatedUser();
    RecordWelcomeTourStepAction::run($user, 'done');
    SetUserWelcomeTourPreferenceAction::run($user, false);
    SetUserWelcomeTourPreferenceAction::run($user, true);
    expect(CanShowWelcomeTourAction::run($user))->toBeTrue()
        ->and(GetUserWelcomeTourStateAction::run($user)->completedStepKeys)->toBe(['done']);
});

it('reports unavailable targets without marking progress or dismissal', function (): void {
    session()->put('capell_welcome_tour.active', true);
    Livewire::test(WelcomeTourOrchestrator::class)->call('targetUnavailable');
    expect(GetUserWelcomeTourStateAction::run($this->authenticatedUser())->completedStepKeys)->toBe([])
        ->and(CanShowWelcomeTourAction::run($this->authenticatedUser()))->toBeTrue()
        ->and(session()->has('capell_welcome_tour.active'))->toBeFalse();
});

it('denies a control action after the actors management access changes', function (): void {
    $component = Livewire::test(WelcomeTourSettingsControls::class);
    $this->actingAsUser();
    $component->call('restart')->assertForbidden();
});

it('keeps the account preference helper explicit and preserves progress through the user bridge', function (): void {
    $user = $this->authenticatedUser();
    $bridge = new WelcomeTourUserResourceBridge;
    $fields = $bridge->extendComponentsForHook(
        Schema::make(),
        UserSchemaHookEnum::AfterIdentity,
        UserSchemaContextData::forEdit($user, [], 'default'),
    );
    $toggle = $fields[0];
    throw_unless($toggle instanceof Toggle, RuntimeException::class, 'Expected the account preference toggle.');
    $schema = Schema::make(new WelcomeTourSettingsForm)
        ->model($user)->statePath('data')->components([$toggle]);
    $schema->fill(['welcome_tour_enabled' => true]);
    expect($schema->toHtml())->toContain('eligible tour steps', 'Restart my tour');
    RecordWelcomeTourStepAction::run($user, 'done');
    $data = $bridge->mutateDataBeforeSave($user, ['name' => 'Editor', 'welcome_tour_enabled' => true]);
    expect($data)->toBe(['name' => 'Editor'])
        ->and(GetUserWelcomeTourStateAction::run($user)->completedStepKeys)->toBe(['done']);
});
