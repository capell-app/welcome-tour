<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

use Capell\WelcomeTour\Enums\WelcomeTourReadinessStatus;

final readonly class WelcomeTourChecklistItemData
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public ?string $url,
        public bool $complete,
        public bool $manuallyCompleted = false,
        public WelcomeTourReadinessStatus $status = WelcomeTourReadinessStatus::ActionRequired,
        public string $explanation = '',
    ) {}
}
