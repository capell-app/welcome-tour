<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

use Capell\Admin\Data\WelcomeTourStepData;

final readonly class WelcomeTourChapterData
{
    /** @param list<WelcomeTourStepData> $steps */
    public function __construct(
        public string $key,
        public string $route,
        public array $steps,
    ) {}
}
