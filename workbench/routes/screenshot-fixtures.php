<?php

declare(strict_types=1);

use Capell\WelcomeTour\Actions\PrepareWelcomeTourScreenshotAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

$screenshotApplication = getenv('CAPELL_SCREENSHOT_APP_PATH');
$disposable = app()->environment(['local', 'testing'])
    && getenv('CAPELL_SCREENSHOT_FIXTURE') === 'record-state'
    && is_string($screenshotApplication)
    && realpath($screenshotApplication) === realpath(base_path());

if ($disposable) {
    config()->set('capell-welcome-tour.presentation_mode', false);
    // Register on every request, including the destination and Livewire updates.
    // The normal dashboard heading exists before lazy widgets are mounted.
    config()->set('capell-welcome-tour.manifest_steps', [[
        'key' => 'screenshot-dashboard',
        'title' => 'capell-welcome-tour::welcome_tour.callout_heading',
        'description' => 'capell-welcome-tour::welcome_tour.callout_description',
        'element' => '.fi-header-heading',
        'route' => '@dashboard',
        'chapter' => 'screenshot-dashboard',
        'sort' => -10000,
    ]]);
}

Route::middleware(['web', 'auth'])->get(
    '/screenshot-fixtures/welcome-tour/{state}',
    static fn (string $state): RedirectResponse => redirect(resolve(PrepareWelcomeTourScreenshotAction::class)->handle($state)),
);
