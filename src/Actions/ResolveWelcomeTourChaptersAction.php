<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\Admin\Data\WelcomeTourStepData;
use Capell\WelcomeTour\Data\WelcomeTourChapterData;
use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveWelcomeTourChaptersAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  list<WelcomeTourStepData>  $steps
     * @return list<WelcomeTourChapterData>
     */
    public function handle(array $steps, WelcomeTourUserStateData $state): array
    {
        return array_values(collect($steps)
            ->map(function (WelcomeTourStepData $step): ?WelcomeTourStepData {
                $route = ResolveWelcomeTourDestinationAction::run($step->route);

                if (! is_string($step->chapter) || $step->chapter === '' || $route === null) {
                    return null;
                }

                return new WelcomeTourStepData(
                    key: $step->key,
                    title: $step->title,
                    description: $step->description,
                    element: $step->element,
                    icon: $step->icon,
                    iconColor: $step->iconColor,
                    sort: $step->sort,
                    visible: $step->visible,
                    chapter: $step->chapter,
                    route: $route,
                );
            })
            ->filter()
            ->groupBy(fn (WelcomeTourStepData $step): string => (string) $step->chapter)
            ->map(function ($chapterSteps, string $chapterKey): WelcomeTourChapterData {
                /** @var list<WelcomeTourStepData> $orderedSteps */
                $orderedSteps = $chapterSteps->values()->all();

                return new WelcomeTourChapterData(
                    key: $chapterKey,
                    route: (string) $orderedSteps[0]->route,
                    steps: $orderedSteps,
                );
            })
            ->reject(fn (WelcomeTourChapterData $chapter): bool => collect($chapter->steps)
                ->every(fn (WelcomeTourStepData $step): bool => in_array($step->key, $state->completedStepKeys, true)))
            ->values()
            ->all());
    }
}
