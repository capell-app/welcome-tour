<?php

declare(strict_types=1);

use Capell\Admin\Facades\CapellAdmin;
use Capell\WelcomeTour\Actions\NormalizeWelcomeTourStepsAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourDestinationAction;
use Capell\WelcomeTour\Rules\WelcomeTourSelector;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Capell\WelcomeTour\Tests\Fixtures\WelcomeTourSettingsForm;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

beforeEach(function (): void {
    config()->set('capell-welcome-tour.presentation_mode', false);
    $this->actingAsAdmin();
    CapellAdmin::clearWelcomeTourSteps();
    Route::get('/tour-authoring', fn (): string => 'Tour');
});

it('derives keys and editor ordering while preserving explicit overrides and manifest precedence', function (): void {
    $rows = [
        ['title' => 'One', 'description' => 'Copy', 'route' => '/tour-authoring'],
        ['key' => 'override', 'title' => 'Two', 'description' => 'Copy', 'sort' => 5, 'route' => '/tour-authoring'],
    ];
    $normalized = NormalizeWelcomeTourStepsAction::run($rows);
    $reordered = NormalizeWelcomeTourStepsAction::run(array_reverse($rows));
    expect($normalized[0]['key'])->toStartWith('custom.')
        ->and($normalized[0]['key'])->toBe($reordered[1]['key'])
        ->and($normalized[0]['sort'])->toBe(10)
        ->and($reordered[1]['sort'])->toBe(20)
        ->and($normalized[1]['key'])->toBe('override')
        ->and($normalized[1]['sort'])->toBe(5);
    $settings = WelcomeTourSettings::instance();
    $settings->steps = $rows;
    $settings->save();
    config()->set('capell-welcome-tour.manifest_steps', [[...$rows[1], 'title' => 'Manifest']]);
    resolve(WelcomeTourStepRegistrar::class)->register();
    $steps = CapellAdmin::getWelcomeTourSteps();
    expect($steps)->toHaveCount(2)
        ->and($steps[0]->key)->toBe('override')
        ->and(value($steps[0]->title))->toBe('Manifest')
        ->and($steps[0]->chapter)->toBe($steps[1]->chapter);
});

it('rejects broken configured selectors before registration', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [['key' => 'broken', 'title' => 'Broken', 'description' => 'Copy', 'route' => '/tour-authoring', 'element' => '[broken']];
    $settings->save();
    resolve(WelcomeTourStepRegistrar::class)->register();
    expect(CapellAdmin::getWelcomeTourSteps())->toBe([]);
});

it('validates stable selectors', function (?string $selector, bool $valid): void {
    expect(WelcomeTourSelector::accepts($selector))->toBe($valid);
})->with([
    [null, true], ['', true], ['#new-page', true], ['.fi-page .fi-ta', true],
    ['[data-tour-id="pages"] > button.primary', true], ['[broken', false],
    ['#', false], ['.foo >', false], ['button{color:red}', false], ['   ', false],
]);

it('rejects unavailable or unsafe destinations', function (string $destination): void {
    expect(ResolveWelcomeTourDestinationAction::run($destination))->toBeNull();
})->with(['/missing-tour', 'https://elsewhere.invalid/tour-authoring', '//localhost/tour-authoring', 'javascript:/tour-authoring', '/\\evil.test/tour-authoring']);

it('accepts an available local destination and rejects unknown resources', function (): void {
    expect(ResolveWelcomeTourDestinationAction::run('/tour-authoring'))->toBe('/tour-authoring')
        ->and(ResolveWelcomeTourDestinationAction::run('/tour-authoring', 'UnknownResource'))->toBeNull();
});

it('renders ordinary controls before the collapsed developer schema', function (): void {
    Livewire::test(WelcomeTourSettingsForm::class)
        ->assertSeeInOrder(['Enabled', 'Getting started', 'Preview as me', 'Restart my tour', 'Advanced developer'])
        ->fillForm(['enabled' => true, 'steps' => [
            ['key' => 'custom.test', 'title' => 'Title', 'description' => 'Description', 'route' => '/missing-tour', 'element' => '[broken'],
        ]])->call('save')->assertHasFormErrors(['steps.0.route', 'steps.0.element']);
});

it('validates resource overrides and audience ages through the settings form', function (): void {
    Livewire::test(WelcomeTourSettingsForm::class)->fillForm(['enabled' => true, 'steps' => [
        ['key' => 'custom.test', 'title' => 'Title', 'description' => 'Description', 'route' => '/tour-authoring', 'resource' => 'UnknownResource', 'user_created_within_days' => -1],
    ]])->call('save')->assertHasFormErrors(['steps.0.resource', 'steps.0.user_created_within_days']);
});

it('accepts generated keys and optional technical overrides in the form', function (): void {
    Livewire::test(WelcomeTourSettingsForm::class)->fillForm(['enabled' => true, 'steps' => [
        ['key' => 'custom.test', 'title' => 'Title', 'description' => 'Description', 'route' => '/tour-authoring', 'element' => '#page'],
    ]])->call('save')->assertHasNoFormErrors();
});
