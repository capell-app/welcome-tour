<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Events\WelcomeTourSnoozed;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class SnoozeUserWelcomeTourAction
{
    use AsFake;
    use AsObject;

    public function handle(Model $user, int $hours = 24, string $tourKey = 'capell_admin_welcome'): void
    {
        AuthorizeWelcomeTourUserMutationAction::run($user);

        $snoozedUntil = Date::now()->addHours(max(1, $hours));
        resolve(WelcomeTourStateStoreResolver::class)->resolve()->snooze($user, $hours, $tourKey);

        event(new WelcomeTourSnoozed($user, $tourKey, $snoozedUntil->toImmutable()));
    }
}
