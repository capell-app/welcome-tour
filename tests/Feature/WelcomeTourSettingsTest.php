<?php

declare(strict_types=1);

use Capell\Core\Support\Settings\SettingsSchemaRegistry;
use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Capell\WelcomeTour\Providers\WelcomeTourServiceProvider;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;

it('registers welcome tour settings for the extension settings page', function (): void {
    $registry = resolve(SettingsSchemaRegistry::class);

    expect($registry->getSettingsClass('welcome-tour'))->toBe(WelcomeTourSettings::class)
        ->and($registry->getSchema('welcome-tour', 'WelcomeTourSettingsSchema'))->toBe(WelcomeTourSettingsSchema::class)
        ->and($registry->getMetadata('welcome-tour')?->packageName)->toBe(WelcomeTourServiceProvider::$packageName);
});

it('stores configurable tour steps with translation keys', function (): void {
    $settings = WelcomeTourSettings::instance();
    $settings->steps = [
        [
            'key' => 'custom.introduction',
            'title' => 'capell-welcome-tour::welcome_tour.introduction_title',
            'description' => 'capell-welcome-tour::welcome_tour.introduction_description',
            'element' => '',
            'icon' => 'heroicon-o-sparkles',
            'icon_color' => 'primary',
            'sort' => '10',
            'visible' => '1',
        ],
    ];
    $settings->save();

    /** @var WelcomeTourSettings $fresh */
    $fresh = WelcomeTourSettings::instance()->refresh();

    expect($fresh->steps)->toHaveCount(1)
        ->and($fresh->steps[0]['title'])->toBe('capell-welcome-tour::welcome_tour.introduction_title');
});

it('seeds default settings from package configuration', function (): void {
    $settings = WelcomeTourSettings::instance();

    expect($settings->enabled)->toBeTrue()
        ->and($settings->steps)->toHaveCount(4)
        ->and($settings->steps[0]['key'])->toBe('capell-welcome-tour.introduction');
});
