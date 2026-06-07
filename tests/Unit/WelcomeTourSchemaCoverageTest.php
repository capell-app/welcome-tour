<?php

declare(strict_types=1);

use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Health\WelcomeTourHealthCheck;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as SchemaFacade;

it('declares welcome tour settings metadata and schema structure', function (): void {
    $components = WelcomeTourSettingsSchema::make(Schema::make());
    $sectionComponents = welcomeTourCoverageChildComponents($components[0]);

    expect(WelcomeTourSettings::group())->toBe('welcome-tour')
        ->and(WelcomeTourSettings::schema())->toBe(WelcomeTourSettingsSchema::class)
        ->and(WelcomeTourHealthCheck::compatibleCapellApiVersion())->toBe('^4.0')
        ->and($components)->toHaveCount(1)
        ->and($components[0])->toBeInstanceOf(Section::class)
        ->and($sectionComponents)->toHaveCount(2)
        ->and($sectionComponents[0])->toBeInstanceOf(Grid::class)
        ->and($sectionComponents[1])->toBeInstanceOf(Repeater::class);
});

it('reports real welcome tour health diagnostics', function (): void {
    expect(WelcomeTourHealthCheck::runDiagnostics())->toHaveCount(4)
        ->and(WelcomeTourHealthCheck::passed())->toBeTrue();
});

it('fails welcome tour health when per-user dismissal storage is missing', function (): void {
    SchemaFacade::drop('welcome_tour_user_states');
    SchemaFacade::table('users', function (Blueprint $table): void {
        $table->dropColumn('dismissed_hints');
    });

    $check = new WelcomeTourHealthCheck;

    expect($check->userDismissalStorageCheck()->passed)->toBeFalse()
        ->and(WelcomeTourHealthCheck::passed())->toBeFalse();
});

it('declares benefit-led welcome tour marketplace copy', function (): void {
    $manifest = json_decode(
        (string) file_get_contents(__DIR__ . '/../../capell.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($manifest['description'])->toBe('Configurable Filament onboarding tours and per-user welcome flow for the Capell admin panel.')
        ->and($manifest['database']['migrations'])->toBeTrue()
        ->and($manifest['database']['requiredTables'])->toBe(['welcome_tour_user_states'])
        ->and($manifest['capabilities'])->toContain('package-owned-tour-state')
        ->and($manifest['capabilities'])->toContain('tour-progress-resume')
        ->and($manifest['capabilities'])->toContain('restart-tour')
        ->and($manifest['capabilities'])->toContain('snooze-tour')
        ->and($manifest['capabilities'])->toContain('onboarding-checklist-widget')
        ->and($manifest['capabilities'])->toContain('tour-lifecycle-events')
        ->and($manifest['marketplace']['summary'])->toBe('Guided, in-product onboarding for Capell Admin — configurable multi-step tours that introduce new editors to sites, pages, media, and settings, with per-user dismiss and resume.')
        ->and($manifest['marketplace']['description'])->toBe('Configurable Filament onboarding tours and per-user welcome flow for the Capell admin panel.')
        ->and($manifest['marketplace']['screenshots'])->toHaveCount(1);
});

/**
 * @return array<int, object>
 */
function welcomeTourCoverageChildComponents(object $component): array
{
    $reflectionProperty = new ReflectionProperty($component, 'childComponents');
    $childComponents = $reflectionProperty->getValue($component);

    return $childComponents['default'] ?? [];
}
