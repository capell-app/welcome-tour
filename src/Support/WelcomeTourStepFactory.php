<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Admin\Data\WelcomeTourStepData;
use JibayMcs\FilamentTour\Tour\Step;

final class WelcomeTourStepFactory
{
    public static function make(WelcomeTourStepData $step): Step
    {
        return Step::make($step->element)
            ->title($step->title)
            ->description($step->description)
            ->icon($step->icon)
            ->iconColor($step->iconColor);
    }
}
