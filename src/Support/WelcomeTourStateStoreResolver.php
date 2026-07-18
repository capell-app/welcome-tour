<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\WelcomeTour\Contracts\WelcomeTourStateStore;
use Capell\WelcomeTour\State\DatabaseWelcomeTourStateStore;
use Capell\WelcomeTour\State\SessionWelcomeTourStateStore;

final class WelcomeTourStateStoreResolver
{
    public function resolve(): WelcomeTourStateStore
    {
        return config('capell-welcome-tour.presentation_mode', false)
            ? resolve(SessionWelcomeTourStateStore::class)
            : resolve(DatabaseWelcomeTourStateStore::class);
    }
}
