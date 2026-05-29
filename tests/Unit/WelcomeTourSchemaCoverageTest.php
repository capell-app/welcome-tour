<?php

declare(strict_types=1);

use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Health\WelcomeTourHealthCheck;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

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

/**
 * @return array<int, object>
 */
function welcomeTourCoverageChildComponents(object $component): array
{
    $reflectionProperty = new ReflectionProperty($component, 'childComponents');
    $childComponents = $reflectionProperty->getValue($component);

    return $childComponents['default'] ?? [];
}
