<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

final readonly class WelcomeTourSummaryData
{
    /**
     * @param  list<WelcomeTourChecklistItemData>  $checklist
     * @param  list<WelcomeTourChapterData>  $chapters
     */
    public function __construct(
        public bool $eligible,
        public array $checklist,
        public array $chapters,
    ) {}
}
