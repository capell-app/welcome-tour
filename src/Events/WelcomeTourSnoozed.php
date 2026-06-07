<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Events;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

final readonly class WelcomeTourSnoozed
{
    public function __construct(
        public Model $user,
        public string $tourKey,
        public CarbonImmutable $snoozedUntil,
    ) {}
}
