<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class GetUserWelcomeTourStateAction
{
    use AsObject;

    public function handle(Model $user, string $tourKey = 'capell_admin_welcome'): WelcomeTourUserStateData
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
}
