<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\Tests\Support\ScreenshotManifest;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Capell\WelcomeTour\Livewire\WelcomeTourOrchestrator;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Dom\HTMLDocument;
use Filament\Pages\Dashboard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAsAdmin();
    CapellAdmin::clearWelcomeTourSteps();
    $fixture = getenv('CAPELL_SCREENSHOT_FIXTURE');
    $path = getenv('CAPELL_SCREENSHOT_APP_PATH');
    putenv('CAPELL_SCREENSHOT_FIXTURE=record-state');
    putenv('CAPELL_SCREENSHOT_APP_PATH=' . base_path());
    $this->beforeApplicationDestroyed(static function () use ($fixture, $path): void {
        putenv($fixture === false ? 'CAPELL_SCREENSHOT_FIXTURE' : 'CAPELL_SCREENSHOT_FIXTURE=' . $fixture);
        putenv($path === false ? 'CAPELL_SCREENSHOT_APP_PATH' : 'CAPELL_SCREENSHOT_APP_PATH=' . $path);
    });
    require __DIR__ . '/../../workbench/routes/screenshot-fixtures.php';
});

it('starts the same real dashboard chapter for every overlay viewport without changing saved progress', function (): void {
    $user = $this->authenticatedUser();
    RecordWelcomeTourStepAction::run($user, 'saved-step');
    SetUserWelcomeTourPreferenceAction::run($user, false);
    $before = GetUserWelcomeTourStateAction::run($user);

    $this->get(ScreenshotManifest::captureUrl(__DIR__ . '/../../docs/screenshots.json', 'welcome-tour-overlay'))->assertRedirect('/admin');
    expect(resolve(WelcomeTourStateStoreResolver::class)->isPreview())->toBeTrue();
    $this->get('/admin')->assertOk()
        ->assertSee('data-update-uri', false)
        ->assertSee('class="fi-header-heading"', false)
        ->assertSee('capell_admin_welcome.screenshot-dashboard', false);
    $originalRequest = request();
    try {
        app()->instance('request', Request::create('/admin'));
        $data = (new WelcomeTourOrchestrator)->render()->getData();
        expect($data['tourIdToOpen'])->toBe('capell_admin_welcome.screenshot-dashboard')
            ->and($data['targetSelectors'])->toBe(['.fi-header-heading']);
    } finally {
        app()->instance('request', $originalRequest);
    }

    $this->get('/screenshot-fixtures/welcome-tour/user-toggle')->assertRedirect(welcomeTourScreenshotEditUrl($user));
    expect(GetUserWelcomeTourStateAction::run($user))->toEqual($before)
        ->and(session()->has('capell_welcome_tour.active'))->toBeFalse()
        ->and(resolve(WelcomeTourStateStoreResolver::class)->isPreview())->toBeFalse();

    foreach (['welcome-tour-overlay', 'welcome-tour-overlay-tablet', 'welcome-tour-overlay-mobile'] as $id) {
        expect(ScreenshotManifest::entry(__DIR__ . '/../../docs/screenshots.json', $id)['url'])
            ->toBe('/screenshot-fixtures/welcome-tour/overlay');
    }
});

it('targets an element rendered by a host using the standard dashboard', function (): void {
    $selector = config('capell-welcome-tour.manifest_steps.0.element');
    throw_unless(is_string($selector), RuntimeException::class, 'The screenshot tour must declare a target.');

    $dashboard = Livewire::test(Dashboard::class);
    $document = HTMLDocument::createFromString('<!DOCTYPE html><html><body>' . $dashboard->html() . '</body></html>');

    expect($document->querySelector($selector))->not->toBeNull();
});

it('renders a cold user-toggle destination with its Livewire bootstrap and real field', function (): void {
    $url = welcomeTourScreenshotEditUrl($this->authenticatedUser());
    $this->get(ScreenshotManifest::captureUrl(__DIR__ . '/../../docs/screenshots.json', 'welcome-tour-user-toggle'))->assertRedirect($url);
    $this->get($url)->assertOk()
        ->assertSee('welcome_tour_enabled"', false)
        ->assertSee('data-update-uri', false);
});

it('refuses to prepare tour state outside an explicitly selected disposable application', function (): void {
    $fixture = getenv('CAPELL_SCREENSHOT_FIXTURE');
    putenv('CAPELL_SCREENSHOT_FIXTURE');
    try {
        require __DIR__ . '/../../workbench/routes/screenshot-fixtures.php';
        $this->get('/screenshot-fixtures/welcome-tour/overlay')->assertForbidden();
    } finally {
        putenv($fixture === false ? 'CAPELL_SCREENSHOT_FIXTURE' : 'CAPELL_SCREENSHOT_FIXTURE=' . $fixture);
    }
});

function welcomeTourScreenshotEditUrl(Model $user): string
{
    $key = $user->getRouteKey();
    throw_unless(is_int($key) || is_string($key), RuntimeException::class, 'The screenshot user must have a scalar route key.');

    return '/admin/users/' . $key . '/edit';
}
