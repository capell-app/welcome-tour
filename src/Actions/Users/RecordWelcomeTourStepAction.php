<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Events\WelcomeTourStepCompleted;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class RecordWelcomeTourStepAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, string $stepKey, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);

        if ($stepKey === '') {
            return;
        }

        resolve(WelcomeTourStateStoreResolver::class)->resolve()->recordStep($user, $stepKey, $tourKey);

        event(new WelcomeTourStepCompleted($user, $tourKey, $stepKey));
    }
}
