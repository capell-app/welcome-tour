<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\State;

use Capell\Admin\Actions\DismissHintAction;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Contracts\WelcomeTourStateStore;
use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

final class DatabaseWelcomeTourStateStore implements WelcomeTourStateStore
{
    public function state(Model $user, string $tourKey): WelcomeTourUserStateData
    {
        if (! WelcomeTourSchema::hasUserStateTable()) {
            return new WelcomeTourUserStateData([], null, null, false);
        }

        $row = DB::table('welcome_tour_user_states')
            ->where('user_type', $user->getMorphClass())
            ->where('user_id', $user->getKey())
            ->where('tour_key', $tourKey)
            ->first();

        return WelcomeTourUserStateData::fromDatabaseRow($row === null ? null : (array) $row);
    }

    public function recordStep(Model $user, string $stepKey, string $tourKey): void
    {
        if ($stepKey === '' || ! WelcomeTourSchema::hasUserStateTable()) {
            return;
        }

        DB::transaction(function () use ($user, $stepKey, $tourKey): void {
            $now = Date::now();
            $existing = $this->query($user, $tourKey)->lockForUpdate()->first();
            $decodedStepKeys = json_decode((string) ($existing->completed_step_keys ?? '[]'), true);
            $completedStepKeys = collect(is_array($decodedStepKeys) ? $decodedStepKeys : [])
                ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
                ->push($stepKey)
                ->unique()
                ->values()
                ->all();

            DB::table('welcome_tour_user_states')->updateOrInsert(
                $this->identity($user, $tourKey),
                [
                    'completed_step_keys' => json_encode($completedStepKeys, JSON_THROW_ON_ERROR),
                    'last_completed_step_key' => $stepKey,
                    'updated_at' => $now,
                    'created_at' => $existing->created_at ?? $now,
                ],
            );
        });
    }

    public function reset(Model $user, string $tourKey): void
    {
        if (! WelcomeTourSchema::hasUserStateTable()) {
            return;
        }

        DB::table('welcome_tour_user_states')->updateOrInsert($this->identity($user, $tourKey), [
            'completed_step_keys' => '[]',
            'last_completed_step_key' => null,
            'snoozed_until' => null,
            'dismissed_at' => null,
            'updated_at' => Date::now(),
        ]);

        $this->removeDismissedHint($user);
    }

    public function restartProgress(Model $user, string $tourKey): void
    {
        if (! WelcomeTourSchema::hasUserStateTable()) {
            return;
        }

        DB::table('welcome_tour_user_states')->updateOrInsert($this->identity($user, $tourKey), [
            'completed_step_keys' => '[]',
            'last_completed_step_key' => null,
            'snoozed_until' => null,
            'updated_at' => Date::now(),
        ]);
    }

    public function setChecklistItemCompleted(Model $user, string $itemKey, bool $completed, string $tourKey): void
    {
        if ($itemKey === '' || ! WelcomeTourSchema::hasChecklistStateColumns()) {
            return;
        }

        DB::transaction(function () use ($user, $itemKey, $completed, $tourKey): void {
            $now = Date::now();
            $existing = $this->query($user, $tourKey)->lockForUpdate()->first();
            $decodedItemKeys = json_decode((string) ($existing->completed_checklist_item_keys ?? '[]'), true);
            $completedItemKeys = collect(is_array($decodedItemKeys) ? $decodedItemKeys : [])
                ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
                ->when(
                    $completed,
                    fn (Collection $keys): Collection => $keys->push($itemKey),
                    fn (Collection $keys): Collection => $keys->reject(fn (string $key): bool => $key === $itemKey),
                )
                ->unique()
                ->values()
                ->all();

            DB::table('welcome_tour_user_states')->updateOrInsert(
                $this->identity($user, $tourKey),
                [
                    'completed_checklist_item_keys' => json_encode($completedItemKeys, JSON_THROW_ON_ERROR),
                    'updated_at' => $now,
                    'created_at' => $existing->created_at ?? $now,
                ],
            );
        });
    }

    public function setChecklistDismissed(Model $user, bool $dismissed, string $tourKey): void
    {
        if (! WelcomeTourSchema::hasChecklistStateColumns()) {
            return;
        }

        DB::table('welcome_tour_user_states')->updateOrInsert($this->identity($user, $tourKey), [
            'checklist_dismissed_at' => $dismissed ? Date::now() : null,
            'updated_at' => Date::now(),
        ]);
    }

    public function dismiss(Model $user, string $tourKey): void
    {
        if ($user instanceof AuthenticatableUser && WelcomeTourSchema::hasDismissedHintsColumn($user->getTable())) {
            DismissHintAction::run($user, CanShowWelcomeTourAction::DISMISSED_HINT_KEY);

            return;
        }

        if (WelcomeTourSchema::hasUserStateTable()) {
            DB::table('welcome_tour_user_states')->updateOrInsert($this->identity($user, $tourKey), [
                'dismissed_at' => Date::now(),
                'snoozed_until' => null,
                'updated_at' => Date::now(),
            ]);
        }
    }

    public function snooze(Model $user, int $hours, string $tourKey): void
    {
        if (! WelcomeTourSchema::hasUserStateTable()) {
            return;
        }

        DB::table('welcome_tour_user_states')->updateOrInsert($this->identity($user, $tourKey), [
            'snoozed_until' => Date::now()->addHours(max(1, $hours)),
            'updated_at' => Date::now(),
        ]);
    }

    public function hasAutoStarted(Model $user, string $tourKey): bool
    {
        return false;
    }

    public function markAutoStarted(Model $user, string $tourKey): void {}

    /** @return array{user_type: string, user_id: mixed, tour_key: string} */
    private function identity(Model $user, string $tourKey): array
    {
        return ['user_type' => $user->getMorphClass(), 'user_id' => $user->getKey(), 'tour_key' => $tourKey];
    }

    private function query(Model $user, string $tourKey): Builder
    {
        return DB::table('welcome_tour_user_states')->where($this->identity($user, $tourKey));
    }

    private function removeDismissedHint(Model $user): void
    {
        if (! WelcomeTourSchema::hasDismissedHintsColumn($user->getTable())) {
            return;
        }

        $rawDismissedHints = DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->value('dismissed_hints');
        $decodedDismissedHints = is_string($rawDismissedHints) ? json_decode($rawDismissedHints, true) : [];
        $dismissedHints = collect(is_array($decodedDismissedHints) ? $decodedDismissedHints : [])
            ->filter(fn (mixed $hint): bool => is_string($hint))
            ->reject(fn (string $hint): bool => $hint === CanShowWelcomeTourAction::DISMISSED_HINT_KEY)
            ->values()
            ->all();

        DB::table($user->getTable())->where($user->getKeyName(), $user->getKey())->update([
            'dismissed_hints' => json_encode($dismissedHints, JSON_THROW_ON_ERROR),
        ]);
    }
}
