<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Actions\BuildWelcomeTourSummaryAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourDestinationAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction;
use Capell\WelcomeTour\Support\WelcomeTourStateStoreResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsObject;

final class StartWelcomeTourForCurrentUserAction
{
    use AsObject;

    public function handle(bool $preview = false): string
    {
        $user = auth()->user();
        abort_unless($user instanceof Model, 403);
        AuthorizeWelcomeTourUserMutationAction::run($user);
        $summary = BuildWelcomeTourSummaryAction::run();

        if (! ResolveWelcomeTourEnabledAction::run() || $summary->chapters === []) {
            throw ValidationException::withMessages(['tour' => __('capell-welcome-tour::welcome_tour.no_steps')]);
        }

        foreach ($summary->chapters as $chapter) {
            if (ResolveWelcomeTourDestinationAction::run($chapter->route) === null
                || collect($chapter->steps)->contains(fn ($step): bool => $step->route !== $chapter->route)) {
                throw ValidationException::withMessages(['tour' => __('capell-welcome-tour::welcome_tour.invalid_destination')]);
            }
        }

        session()->forget(['capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
        if ($preview) {
            session()->put('capell_welcome_tour.preview_user', json_encode([$user->getMorphClass(), $user->getKey()], JSON_THROW_ON_ERROR));
            resolve(WelcomeTourStateStoreResolver::class)->resolve()->reset($user, 'capell_admin_welcome');
        } else {
            RestartWelcomeTourProgressAction::run($user);
        }

        session()->put('capell_welcome_tour.active', true);

        return $summary->chapters[0]->route;
    }
}
