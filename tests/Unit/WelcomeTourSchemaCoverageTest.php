<?php

declare(strict_types=1);

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;
use Capell\Core\Contracts\Extensions\RegistersExtensionFilamentWidget;
use Capell\Core\Contracts\Extensions\RegistersExtensionSetting;
use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Filament\Widgets\WelcomeTourChecklistFilamentWidget;
use Capell\WelcomeTour\Health\WelcomeTourHealthCheck;
use Capell\WelcomeTour\Manifest\WelcomeTourChecklistWidgetContribution;
use Capell\WelcomeTour\Manifest\WelcomeTourHealthContribution;
use Capell\WelcomeTour\Manifest\WelcomeTourSettingsContribution;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as SchemaFacade;

it('declares welcome tour settings metadata and schema structure', function (): void {
    $components = WelcomeTourSettingsSchema::make(Schema::make());
    $sectionComponents = welcomeTourCoverageChildComponents($components[0]);

    expect(WelcomeTourSettings::group())->toBe('welcome-tour')
        ->and(WelcomeTourSettings::schema())->toBe(WelcomeTourSettingsSchema::class)
        ->and(WelcomeTourHealthCheck::compatibleCapellApiVersion())->toBe('^1.0')
        ->and($components)->toHaveCount(2)
        ->and($components[0]->getHeading())->toBe('Welcome tour')
        ->and($sectionComponents)->toHaveCount(2)
        ->and($sectionComponents[0])->toBeInstanceOf(Toggle::class)
        ->and($sectionComponents[1])->toBeInstanceOf(Livewire::class)
        ->and($components[1]->isCollapsed())->toBeTrue()
        ->and(welcomeTourCoverageChildComponents($components[1])[0])->toBeInstanceOf(Repeater::class);
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
    $manifest = capell_json_file_array(__DIR__ . '/../../capell.json');
    $contributes = data_get($manifest, 'contributes');

    throw_unless(is_array($contributes), RuntimeException::class, 'Expected Welcome Tour manifest contributions.');

    expect(data_get($manifest, 'description'))->toBe('Configurable Filament onboarding tours and per-user welcome flow for the Capell admin panel.')
        ->and(data_get($manifest, 'database.migrations'))->toBeTrue()
        ->and(data_get($manifest, 'database.requiredTables'))->toBe(['welcome_tour_user_states'])
        ->and($contributes)->toContain([
            'type' => 'dashboard-widget',
            'class' => WelcomeTourChecklistWidgetContribution::class,
            'widgetClass' => WelcomeTourChecklistFilamentWidget::class,
            'dashboard' => 'main',
        ])
        ->and($contributes)->toContain([
            'type' => 'setting',
            'class' => WelcomeTourSettingsContribution::class,
            'settingsClasses' => [WelcomeTourSettings::class],
            'settingsGroups' => [WelcomeTourSettings::group()],
        ])
        ->and($contributes)->toContain([
            'type' => 'health-check',
            'class' => WelcomeTourHealthContribution::class,
            'checkClass' => WelcomeTourHealthCheck::class,
        ])
        ->and(data_get($manifest, 'capabilities'))->toContain('package-owned-tour-state')
        ->and(data_get($manifest, 'capabilities'))->toContain('tour-progress-resume')
        ->and(data_get($manifest, 'capabilities'))->toContain('restart-tour')
        ->and(data_get($manifest, 'capabilities'))->toContain('snooze-tour')
        ->and(data_get($manifest, 'capabilities'))->toContain('onboarding-checklist-widget')
        ->and(data_get($manifest, 'capabilities'))->toContain('tour-lifecycle-events')
        ->and(data_get($manifest, 'marketplace.summary'))->toBe('Guided, in-product onboarding for Capell Admin — configurable multi-step tours that introduce new editors to sites, pages, media, and settings, with per-user dismiss and resume.')
        ->and(data_get($manifest, 'marketplace.description'))->toBe('Configurable Filament onboarding tours and per-user welcome flow for the Capell admin panel.')
        ->and(data_get($manifest, 'marketplace.screenshots'))->toHaveCount(2)
        ->and(data_get($manifest, 'marketplace.screenshots.1.path'))->toBe('docs/screenshots/welcome-tour-dashboard.png')
        ->and(data_get($manifest, 'performance.cacheTags'))->toContain('welcome-tour')
        ->and(data_get($manifest, 'performance.cacheSafety.variesBy'))->toContain('user')
        ->and(class_implements(WelcomeTourChecklistWidgetContribution::class))->toContain(RegistersExtensionFilamentWidget::class)
        ->and(class_implements(WelcomeTourSettingsContribution::class))->toContain(RegistersExtensionSetting::class)
        ->and(class_implements(WelcomeTourHealthContribution::class))->toContain(ChecksExtensionHealth::class);
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
