<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;

final class SetUserWelcomeTourPreferenceAction
{
    use AsObject;

    public function handle(Model $user, bool $enabled): void
    {
        if (! Schema::hasTable($user->getTable()) || ! Schema::hasColumn($user->getTable(), 'dismissed_hints')) {
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

        return collect(is_array($dismissedHints) ? $dismissedHints : [])
            ->filter(fn (mixed $hint): bool => is_string($hint) && $hint !== '')
            ->values()
            ->all();
    }
}
