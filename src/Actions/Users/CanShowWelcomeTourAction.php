<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class CanShowWelcomeTourAction
{
    use AsFake;
    use AsObject;

    public const string DISMISSED_HINT_KEY = 'welcome.tour.v1';

    public function handle(?Model $user, string $tourKey = 'capell_admin_welcome'): bool
    {
        if (! ResolveWelcomeTourEnabledAction::run()) {
            return false;
        }

        if (! $user instanceof Model) {
            return false;
        }

        $state = GetUserWelcomeTourStateAction::run($user, $tourKey);

        if ($state->dismissed || $state->isSnoozed()) {
            return false;
        }

        if ($tourKey !== 'capell_admin_welcome'
            || resolve(WelcomeTourStateStoreResolver::class)->isPreview()
            || config('capell-welcome-tour.presentation_mode', false)) {
            return true;
        }

        if (! WelcomeTourSchema::hasDismissedHintsColumn($user->getTable())) {
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

        return array_values(collect(is_array($dismissedHints) ? $dismissedHints : [])
            ->filter(fn (mixed $hint): bool => is_string($hint) && $hint !== '')
            ->values()
            ->all());
    }
}
