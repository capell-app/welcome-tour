<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SetWelcomeTourChecklistItemCompletionAction
{
    use AsFake;
    use AsObject;

    public function handle(
        Model $user,
        string $itemKey,
        bool $completed,
        string $tourKey = 'capell_admin_welcome',
    ): void {
        AuthorizeWelcomeTourUserMutationAction::run($user);
        resolve(WelcomeTourStateStoreResolver::class)
            ->resolve()
            ->setChecklistItemCompleted($user, $itemKey, $completed, $tourKey);
    }
}
