<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SetUserWelcomeTourPreferenceAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, bool $enabled, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);

        $store = resolve(WelcomeTourStateStoreResolver::class)->resolve();

        if ($enabled) {
            $store->reset($user, $tourKey);

            return;
        }

        $store->dismiss($user, $tourKey);
    }
}
