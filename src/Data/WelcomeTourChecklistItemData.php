<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

final readonly class WelcomeTourChecklistItemData
{
    public function __construct(
        public string $key,
        public string $label,
        public string $description,
        public ?string $url,
        public bool $complete,
        public bool $manuallyCompleted = false,
    ) {}
}
