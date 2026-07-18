<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Events\WelcomeTourRestarted;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class RestartWelcomeTourProgressAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);
        resolve(WelcomeTourStateStoreResolver::class)->resolve()->restartProgress($user, $tourKey);
        event(new WelcomeTourRestarted($user, $tourKey));
    }
}
