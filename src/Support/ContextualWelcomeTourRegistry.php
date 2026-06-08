<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Admin\Data\WelcomeTourStepData;
use Capell\WelcomeTour\Actions\CanShowWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

final class ContextualWelcomeTourRegistry
{
    /** @var array<string, array<string, WelcomeTourStepData>> */
    private array $stepsByTour = [];

    /**
     * @param  array<int|string, mixed>  $configuredTours
     */
    public function registerConfiguredTours(array $configuredTours): void
    {
        if (! ResolveWelcomeTourEnabledAction::run()) {
            return;
        }

        foreach ($configuredTours as $tourKey => $steps) {
            if (! is_string($tourKey)) {
                continue;
            }

            if (! is_array($steps)) {
                continue;
            }

            foreach ($steps as $step) {
                if (! is_array($step)) {
                    continue;
                }

                $this->registerConfiguredStep($tourKey, $step);
            }
        }
    }

    public function registerStep(
        string $tourKey,
        string $key,
        string|Closure $title,
        string|Closure|HtmlString|View $description,
        ?string $element = null,
        ?string $icon = null,
        ?string $iconColor = null,
        int $sort = 100,
        bool|Closure $visible = true,
    ): void {
        if ($tourKey === '' || $key === '') {
            return;
        }

        $this->stepsByTour[$tourKey][$key] = new WelcomeTourStepData(
            key: $key,
            title: $title,
            description: $description,
            element: $element,
            icon: $icon,
            iconColor: $iconColor,
            sort: $sort,
            visible: $visible,
        );
    }

    /**
     * @return list<WelcomeTourStepData>
     */
    public function stepsFor(string $tourKey): array
    {
        if ($tourKey === '') {
            return [];
        }

        return array_values(collect($this->stepsByTour[$tourKey] ?? [])
            ->filter(fn (WelcomeTourStepData $step): bool => $step->isVisible())
            ->sortBy([
                ['sort', 'asc'],
                ['key', 'asc'],
            ])
            ->values()
            ->all());
    }

    public function clear(): void
    {
        $this->stepsByTour = [];
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function registerConfiguredStep(string $tourKey, array $step): void
    {
        $key = $this->stringValue($step, 'key');

        if ($key === '') {
            return;
        }

        $this->registerStep(
            tourKey: $tourKey,
            key: $key,
            title: fn (): string => $this->translate($this->stringValue($step, 'title')),
            description: fn (): string => $this->translate($this->stringValue($step, 'description')),
            element: $this->nullableStringValue($step, 'element'),
            icon: $this->nullableStringValue($step, 'icon'),
            iconColor: $this->nullableStringValue($step, 'icon_color'),
            sort: $this->integerValue($step, 'sort', 100),
            visible: fn (): bool => $this->isVisible($step),
        );
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function isVisible(array $step): bool
    {
        $user = auth()->user();

        return CanShowWelcomeTourStepAction::run(
            $step,
            $user instanceof Model ? $user : null,
        );
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function stringValue(array $step, string $key): string
    {
        $value = $step[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function nullableStringValue(array $step, string $key): ?string
    {
        $value = $this->stringValue($step, $key);

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function integerValue(array $step, string $key, int $default): int
    {
        $value = $step[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    private function translate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return Lang::has($value) ? (string) __($value) : $value;
    }
}
