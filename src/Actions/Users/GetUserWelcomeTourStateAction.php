<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class GetUserWelcomeTourStateAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, string $tourKey = 'capell_admin_welcome'): WelcomeTourUserStateData
    {
        return resolve(WelcomeTourStateStoreResolver::class)->resolve()->state($user, $tourKey);
    }
}
