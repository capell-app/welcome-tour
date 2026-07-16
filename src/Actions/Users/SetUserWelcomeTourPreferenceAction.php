<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SetUserWelcomeTourPreferenceAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, bool $enabled, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);

        if (! Schema::hasTable($user->getTable()) || ! Schema::hasColumn($user->getTable(), 'dismissed_hints')) {
            $this->storePreferenceInPackageState($user, $enabled, $tourKey);

            return;
        }

        DB::transaction(function () use ($user, $enabled): void {
            $dismissedHints = collect($this->dismissedHints($user))
                ->reject(fn (string $hint): bool => $hint === CanShowWelcomeTourAction::DISMISSED_HINT_KEY)
                ->reject(fn (string $hint): bool => $hint === 'capell-admin.welcome-tour');

            if (! $enabled) {
                $dismissedHints->push(CanShowWelcomeTourAction::DISMISSED_HINT_KEY);
            }

            DB::table($user->getTable())
                ->where($user->getKeyName(), $user->getKey())
                ->update([
                    'dismissed_hints' => json_encode($dismissedHints->unique()->values()->all(), JSON_THROW_ON_ERROR),
                ]);
        });
    }

    private function storePreferenceInPackageState(Model $user, bool $enabled, string $tourKey): void
    {
        if (! Schema::hasTable('welcome_tour_user_states')) {
            return;
        }

        DB::table('welcome_tour_user_states')->updateOrInsert(
            [
                'user_type' => $user->getMorphClass(),
                'user_id' => $user->getKey(),
                'tour_key' => $tourKey,
            ],
            [
                'dismissed_at' => $enabled ? null : Date::now(),
                'snoozed_until' => null,
                'updated_at' => Date::now(),
            ],
        );
    }

    /**
     * @return list<string>
     */
    private function dismissedHints(Model $user): array
    {
        $raw = DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->lockForUpdate()
            ->value('dismissed_hints');

        $dismissedHints = is_string($raw) ? json_decode($raw, true) : [];

        return array_values(collect(is_array($dismissedHints) ? $dismissedHints : [])
            ->filter(fn (mixed $hint): bool => is_string($hint) && $hint !== '')
            ->values()
            ->all());
    }
}
