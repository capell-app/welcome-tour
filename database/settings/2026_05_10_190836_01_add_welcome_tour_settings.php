<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('welcome-tour.enabled')) {
            $this->migrator->add('welcome-tour.enabled', true);
        }

        if (! $this->migrator->exists('welcome-tour.steps')) {
            $this->migrator->add('welcome-tour.steps', config('capell-welcome-tour.steps', []));
        }
    }
};
