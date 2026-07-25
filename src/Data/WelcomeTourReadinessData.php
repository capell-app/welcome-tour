<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

use Capell\WelcomeTour\Enums\WelcomeTourReadinessStatus;

final readonly class WelcomeTourReadinessData
{
    public function __construct(
        public WelcomeTourReadinessStatus $status,
        public string $explanation,
        public ?string $recoveryUrl = null,
    ) {}
}
