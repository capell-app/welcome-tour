<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Providers;

use Capell\Core\Support\Migration\MigrationRootRegistry;
use Illuminate\Support\ServiceProvider;
use Override;

final class MigrationMetadataServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {

        $this->callAfterResolving(MigrationRootRegistry::class, static function (MigrationRootRegistry $registry): void {
            $registry->register('capell-app/welcome-tour', __DIR__ . '/../../database/migrations', [
                '2026_06_04_000001_create_welcome_tour_user_states_table' => 'welcome_tour_user_states',
            ]);
        });
    }
}
