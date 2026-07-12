<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\Admin\Support\SiteScope;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsObject;

final class AuthorizeWelcomeTourUserMutationAction
{
    use AsObject;

    public function handle(Model $user): void
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            throw new AuthorizationException('Welcome tour state changes require an authenticated actor.');
        }

        if (SiteScope::isGlobalActor($actor)) {
            return;
        }

        if ($actor instanceof Model
            && $actor->getMorphClass() === $user->getMorphClass()
            && (string) $actor->getKey() === (string) $user->getKey()) {
            return;
        }

        throw new AuthorizationException('You cannot change another user\'s welcome tour state.');
    }
}
