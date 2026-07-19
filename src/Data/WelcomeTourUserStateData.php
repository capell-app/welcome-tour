<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Data;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final readonly class WelcomeTourUserStateData
{
    /**
     * @param  list<string>  $completedStepKeys
     * @param  list<string>  $completedChecklistItemKeys
     */
    public function __construct(
        public array $completedStepKeys,
        public ?string $lastCompletedStepKey,
        public ?CarbonImmutable $snoozedUntil,
        public bool $dismissed,
        public array $completedChecklistItemKeys = [],
        public bool $checklistDismissed = false,
    ) {}

    /**
     * @param  array<string, mixed>|null  $row
     */
    public static function fromDatabaseRow(?array $row): self
    {
        if ($row === null) {
            return new self([], null, null, false);
        }

        $completedStepKeysJson = $row['completed_step_keys'] ?? '[]';
        $completedChecklistItemKeysJson = $row['completed_checklist_item_keys'] ?? '[]';
        $completedStepKeys = is_string($completedStepKeysJson) ? json_decode($completedStepKeysJson, true) : [];
        $completedChecklistItemKeys = is_string($completedChecklistItemKeysJson) ? json_decode($completedChecklistItemKeysJson, true) : [];

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
            completedChecklistItemKeys: self::stringList($completedChecklistItemKeys),
            checklistDismissed: ($row['checklist_dismissed_at'] ?? null) !== null,
        );
    }

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state): self
    {
        $completedStepKeys = $state['completed_step_keys'] ?? [];
        $completedChecklistItemKeys = $state['completed_checklist_item_keys'] ?? [];

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
            completedChecklistItemKeys: self::stringList($completedChecklistItemKeys),
            checklistDismissed: ($state['checklist_dismissed'] ?? false) === true,
        );
    }

    public function isSnoozed(?CarbonInterface $now = null): bool
    {
        if (! $this->snoozedUntil instanceof CarbonImmutable) {
            return false;
        }

        return $this->snoozedUntil->greaterThan($now ?? CarbonImmutable::now());
    }

    /** @return list<string> */
    private static function stringList(mixed $values): array
    {
        return array_values(collect(is_array($values) ? $values : [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->unique()
            ->values()
            ->all());
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
