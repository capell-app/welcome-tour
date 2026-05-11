<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Admin\Facades\CapellAdmin;
use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Throwable;

final class WelcomeTourStepRegistrar
{
    public function register(): void
    {
        foreach ($this->steps() as $step) {
            $key = $this->stringValue($step, 'key');

            if ($key === '') {
                continue;
            }

            CapellAdmin::registerWelcomeTourStep(
                key: $key,
                title: fn (): string => __($this->stringValue($step, 'title')),
                description: fn (): string => $this->safeDescription($step),
                element: $this->nullableStringValue($step, 'element'),
                icon: $this->nullableStringValue($step, 'icon'),
                iconColor: $this->nullableStringValue($step, 'icon_color'),
                sort: $this->integerValue($step, 'sort', 100),
                visible: $this->booleanValue($step, 'visible', true),
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
        return collect($steps)
            ->filter(fn (mixed $step): bool => is_array($step))
            ->values()
            ->all();
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
    private function booleanValue(array $step, string $key, bool $default): bool
    {
        $value = $step[$key] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value) === 'true';
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function safeDescription(array $step): string
    {
        return e((string) __($this->stringValue($step, 'description')));
    }
}
