<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class WelcomeTourUserStateData
{
    /**
     * @param  list<string>  $completedStepKeys
     */
    public function __construct(
        public array $completedStepKeys,
        public ?string $lastCompletedStepKey,
        public ?CarbonImmutable $snoozedUntil,
        public bool $dismissed,
    ) {}

    /**
     * @param  array<string, mixed>|null  $row
     */
    public static function fromDatabaseRow(?array $row): self
    {
        if ($row === null) {
            return new self([], null, null, false);
        }

        $completedStepKeys = json_decode((string) ($row['completed_step_keys'] ?? '[]'), true);

        return new self(
            completedStepKeys: array_values(collect(is_array($completedStepKeys) ? $completedStepKeys : [])
                ->filter(fn (mixed $stepKey): bool => is_string($stepKey) && $stepKey !== '')
                ->unique()
                ->values()
                ->all()),
            lastCompletedStepKey: is_string($row['last_completed_step_key'] ?? null)
                ? $row['last_completed_step_key']
                : null,
            snoozedUntil: self::carbonValue($row['snoozed_until'] ?? null),
            dismissed: ($row['dismissed_at'] ?? null) !== null,
        );
    }

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state): self
    {
        $completedStepKeys = $state['completed_step_keys'] ?? [];

        return new self(
            completedStepKeys: array_values(collect(is_array($completedStepKeys) ? $completedStepKeys : [])
                ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
                ->unique()
                ->values()
                ->all()),
            lastCompletedStepKey: is_string($state['last_completed_step_key'] ?? null)
                ? $state['last_completed_step_key']
                : null,
            snoozedUntil: self::carbonValue($state['snoozed_until'] ?? null),
            dismissed: ($state['dismissed'] ?? false) === true,
        );
    }

    public function isSnoozed(?CarbonInterface $now = null): bool
    {
        if (! $this->snoozedUntil instanceof CarbonImmutable) {
            return false;
        }

        return $this->snoozedUntil->greaterThan($now ?? CarbonImmutable::now());
    }

    private static function carbonValue(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof CarbonInterface) {
            return CarbonImmutable::instance($value);
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }
}
