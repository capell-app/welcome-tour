<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Settings;

use Capell\Admin\Contracts\SettingsSchemaContract;
use Capell\Core\Contracts\SettingsContract;
use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Spatie\LaravelSettings\Settings;

class WelcomeTourSettings extends Settings implements SettingsContract, SettingsSchemaContract
{
    public bool $enabled = true;

    public array $steps = [];

    public static function group(): string
    {
        return 'welcome-tour';
    }

    public static function schema(): string
    {
        return WelcomeTourSettingsSchema::class;
    }

    public static function instance(): self
    {
        return resolve(self::class);
    }
}
