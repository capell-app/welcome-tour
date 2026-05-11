<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class CanShowWelcomeTourAction
{
    use AsObject;

    public const DISMISSED_HINT_KEY = 'capell-welcome-tour.welcome-tour';

    public function handle(?Model $user): bool
    {
        if (! $this->isGloballyEnabled()) {
            return false;
        }

        if (! $user instanceof Model) {
            return false;
        }

        if (! Schema::hasTable($user->getTable()) || ! Schema::hasColumn($user->getTable(), 'dismissed_hints')) {
            return true;
        }

        return ! in_array(self::DISMISSED_HINT_KEY, $this->dismissedHints($user), true);
    }

    /**
     * @return list<string>
     */
    private function dismissedHints(Model $user): array
    {
        $raw = DB::table($user->getTable())
            ->where($user->getKeyName(), $user->getKey())
            ->value('dismissed_hints');

        $dismissedHints = is_string($raw) ? json_decode($raw, true) : [];

        return collect(is_array($dismissedHints) ? $dismissedHints : [])
            ->filter(fn (mixed $hint): bool => is_string($hint) && $hint !== '')
            ->values()
            ->all();
    }

    private function isGloballyEnabled(): bool
    {
        try {
            return resolve(WelcomeTourSettings::class)->enabled;
        } catch (Throwable) {
            return config('capell-welcome-tour.enabled', true);
        }
    }
}
