<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Events\WelcomeTourRestarted;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResetUserWelcomeTourAction
{
    use AsObject;

    public function handle(Model $user, string $tourKey = 'capell_admin_welcome'): void
    {
        SetUserWelcomeTourPreferenceAction::run($user, enabled: true, tourKey: $tourKey);

        if (! WelcomeTourSchema::hasUserStateTable()) {
            event(new WelcomeTourRestarted($user, $tourKey));

            return;
        }

        DB::table('welcome_tour_user_states')->updateOrInsert(
            [
                'user_type' => $user->getMorphClass(),
                'user_id' => $user->getKey(),
                'tour_key' => $tourKey,
            ],
            [
                'completed_step_keys' => json_encode([], JSON_THROW_ON_ERROR),
                'last_completed_step_key' => null,
                'snoozed_until' => null,
                'dismissed_at' => null,
                'updated_at' => Date::now(),
            ],
        );

        event(new WelcomeTourRestarted($user, $tourKey));
    }
}
