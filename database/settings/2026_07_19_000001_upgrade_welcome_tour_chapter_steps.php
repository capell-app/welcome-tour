<?php

declare(strict_types=1);

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    private const string LEGACY_DEFAULT_STEPS_HASH = '77c487891af8d30e7b6bb064e3338859ee3f928437d8f6c23b9f616d9ea4152d';

    public function up(): void
    {
        if (! $this->migrator->exists('welcome-tour.steps')) {
            return;
        }

        $this->migrator->update(
            'welcome-tour.steps',
            static function (mixed $value): mixed {
                if (! is_array($value)) {
                    return $value;
                }

                $hash = hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));

                return $hash === self::LEGACY_DEFAULT_STEPS_HASH
                    ? config('capell-welcome-tour.steps', [])
                    : $value;
            },
        );
    }
};
