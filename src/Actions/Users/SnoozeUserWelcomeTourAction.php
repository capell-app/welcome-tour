<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Events\WelcomeTourSnoozed;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class SnoozeUserWelcomeTourAction
{
    use AsObject;

    public function handle(Model $user, int $hours = 24, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);

        if (! WelcomeTourSchema::hasUserStateTable()) {
            return;
        }

        $snoozedUntil = Date::now()->addHours(max(1, $hours));

        DB::table('welcome_tour_user_states')->updateOrInsert(
            [
                'user_type' => $user->getMorphClass(),
                'user_id' => $user->getKey(),
                'tour_key' => $tourKey,
            ],
            [
                'snoozed_until' => $snoozedUntil,
                'updated_at' => Date::now(),
            ],
        );

        event(new WelcomeTourSnoozed($user, $tourKey, $snoozedUntil->toImmutable()));
    }
}
