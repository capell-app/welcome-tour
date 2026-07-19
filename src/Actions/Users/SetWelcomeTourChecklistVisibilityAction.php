<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SetWelcomeTourChecklistVisibilityAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, bool $visible, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);
        resolve(WelcomeTourStateStoreResolver::class)
            ->resolve()
            ->setChecklistDismissed($user, ! $visible, $tourKey);
    }
}
