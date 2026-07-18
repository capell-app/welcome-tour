<?php

declare(strict_types=1);

use Capell\Admin\Data\WelcomeTourStepData;
use Capell\Admin\Facades\CapellAdmin;
use Capell\Tests\Fixtures\Models\User;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourChaptersAction;
use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Capell\WelcomeTour\State\SessionWelcomeTourStateStore;
use Capell\WelcomeTour\Support\WelcomeTourStepFactory;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;

it('groups ordered visible steps into navigating chapters and maps core metadata', function (): void {
    $steps = [
        new WelcomeTourStepData('dashboard', 'Dashboard', 'Overview', '.fi-page', 'heroicon-o-home', 'primary', 10, true, 'dashboard', '/admin'),
        new WelcomeTourStepData('pages', 'Pages', 'Content', '.fi-ta', 'heroicon-o-document', 'info', 20, true, 'pages', '/admin/pages'),
    ];

    $chapters = ResolveWelcomeTourChaptersAction::run(
        $steps,
        new WelcomeTourUserStateData([], null, null, false),
    );
    $mapped = WelcomeTourStepFactory::make($steps[1]);

    expect($chapters)->toHaveCount(2)
        ->and($chapters[0]->key)->toBe('dashboard')
        ->and($chapters[1]->route)->toBe('/admin/pages')
        ->and($mapped->getElement())->toBe('.fi-ta')
        ->and($mapped->getTitle())->toBe('Pages')
        ->and($mapped->getDescription())->toBe('Content')
        ->and($mapped->getIcon())->toBe('heroicon-o-document')
        ->and($mapped->getIconColor())->toBe('info');
});

it('skips completed and permission denied chapters as whole units', function (): void {
    CapellAdmin::clearWelcomeTourSteps();
    CapellAdmin::registerWelcomeTourStep('pages', 'Pages', 'Content', chapter: 'pages', route: '/admin/pages');
    CapellAdmin::registerWelcomeTourStep('themes', 'Themes', 'Look', visible: false, chapter: 'themes', route: '/admin/themes');

    $chapters = ResolveWelcomeTourChaptersAction::run(
        CapellAdmin::getWelcomeTourSteps(),
        new WelcomeTourUserStateData(['pages'], 'pages', null, false),
    );

    expect($chapters)->toBe([]);
});

it('isolates presentation progress and auto start between browser sessions for the same user', function (): void {
    $user = User::factory()->create();
    $first = presentationStore('first-session');
    $second = presentationStore('second-session');

    expect($first->hasAutoStarted($user, 'welcome'))->toBeFalse()
        ->and($second->hasAutoStarted($user, 'welcome'))->toBeFalse();

    $first->markAutoStarted($user, 'welcome');
    $first->recordStep($user, 'dashboard', 'welcome');
    $first->dismiss($user, 'welcome');

    expect($first->hasAutoStarted($user, 'welcome'))->toBeTrue()
        ->and($first->state($user, 'welcome')->completedStepKeys)->toBe(['dashboard'])
        ->and($first->state($user, 'welcome')->dismissed)->toBeTrue()
        ->and($second->hasAutoStarted($user, 'welcome'))->toBeFalse()
        ->and($second->state($user, 'welcome')->completedStepKeys)->toBe([])
        ->and($second->state($user, 'welcome')->dismissed)->toBeFalse();
});

function presentationStore(string $sessionId): SessionWelcomeTourStateStore
{
    $session = new Store($sessionId, new ArraySessionHandler(120));
    $session->setId($sessionId);
    $session->start();

    return new SessionWelcomeTourStateStore($session);
}
