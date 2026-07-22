<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Admin\Facades\CapellAdmin;
use Capell\WelcomeTour\Actions\CanShowWelcomeTourStepAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Throwable;

final class WelcomeTourStepRegistrar
{
    public function register(): void
    {
        if (! ResolveWelcomeTourEnabledAction::run()) {
            return;
        }

        foreach ($this->steps() as $step) {
            $key = $this->stringValue($step, 'key');

            if ($key === '') {
                continue;
            }

            $resource = $this->nullableStringValue($step, 'resource');
            $route = $this->nullableStringValue($step, 'route');

            CapellAdmin::registerWelcomeTourStep(
                key: $key,
                title: fn (): string => $this->translate($this->stringValue($step, 'title')),
                description: fn (): string => $this->translate($this->stringValue($step, 'description')),
                element: $this->nullableStringValue($step, 'element'),
                icon: $this->nullableStringValue($step, 'icon'),
                iconColor: $this->nullableStringValue($step, 'icon_color'),
                sort: $this->integerValue($step, 'sort', 100),
                visible: fn (): bool => $this->isVisible($step),
                chapter: $this->nullableStringValue($step, 'chapter') ?? 'dashboard',
                route: $this->nullableStringValue($step, 'route'),
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function steps(): array
    {
        try {
            return $this->normalizeSteps(resolve(WelcomeTourSettings::class)->steps);
        } catch (Throwable) {
            //
        }

        $configuredSteps = config('capell-welcome-tour.steps', []);

        return is_array($configuredSteps) ? $this->normalizeSteps($configuredSteps) : [];
    }

    /**
     * @param  array<int|string, mixed>  $steps
     * @return list<array<string, mixed>>
     */
    private function normalizeSteps(array $steps): array
    {
        return array_values(collect($steps)
            ->filter(fn (mixed $step): bool => is_array($step))
            ->values()
            ->all());
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

    private function translate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return Lang::has($value) ? (string) __($value) : $value;
    }
}
