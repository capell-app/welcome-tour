<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction;
use Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard;
use Capell\WelcomeTour\Livewire\WelcomeTourOrchestrator;
use Illuminate\Http\Request;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('capell-welcome-tour.presentation_mode', false);
    $this->actingAsAdmin();
    CapellAdmin::clearWelcomeTourSteps();
    session()->put('capell_welcome_tour.active', true);
});

it('keeps the current chapter on subsequent Livewire update requests', function (): void {
    CapellAdmin::registerWelcomeTourStep('one', 'One', 'Copy', chapter: 'one', route: '/tour-test');
    $originalRequest = $this->app->make('request');
    try {
        $this->app->instance('request', Request::create('/tour-test'));
        $component = new WelcomeTourOrchestrator;
        expect($component->render()->getData()['tourIdToOpen'])->toBe('capell_admin_welcome.one');
        $this->app->instance('request', Request::create('/livewire/update', 'POST'));
        expect($component->render()->getData()['tourIdToOpen'])->toBe('capell_admin_welcome.one');
    } finally {
        $this->app->instance('request', $originalRequest);
    }
});

it('resumes a partial chapter with its original step and chapter progress', function (): void {
    CapellAdmin::registerWelcomeTourStep('one', 'One', 'Copy', sort: 10, chapter: 'first', route: '/tour-test');
    CapellAdmin::registerWelcomeTourStep('two', 'Two', 'Copy', sort: 20, chapter: 'second', route: '/tour-next');
    CapellAdmin::registerWelcomeTourStep('three', 'Three', 'Copy', sort: 30, chapter: 'second', route: '/tour-next');
    RecordWelcomeTourStepAction::run($this->authenticatedUser(), 'one');
    RecordWelcomeTourStepAction::run($this->authenticatedUser(), 'two');
    $tours = (new WelcomeTourDashboard)->tours();
    expect($tours)->toHaveCount(1)
        ->and($tours[0]->getSteps())->toHaveCount(1)
        ->and($tours[0]->getSteps()[0]->getTitle())->toContain('Chapter 2 of 2', 'Step 2 of 2', 'Three');
});

it('records only registered steps and snoozes without dismissing through Livewire', function (): void {
    CapellAdmin::registerWelcomeTourStep('one', 'One', 'Copy', sort: 10, chapter: 'first', route: '/tour-test');
    Livewire::test(WelcomeTourOrchestrator::class)->call('recordStep', 'forged')->call('recordStep', 'one')->call('snooze');
    $state = GetUserWelcomeTourStateAction::run($this->authenticatedUser());
    expect($state->completedStepKeys)->toBe(['one'])
        ->and($state->isSnoozed())->toBeTrue()
        ->and($state->dismissed)->toBeFalse()
        ->and(session()->has('capell_welcome_tour.active'))->toBeFalse();
});
