<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Events;

use Illuminate\Database\Eloquent\Model;

final readonly class WelcomeTourStarted
{
    public function __construct(
        public Model $user,
        public string $tourKey,
    ) {}
}
