<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\WelcomeTour\Contracts\WelcomeTourStateStore;
use Capell\WelcomeTour\State\DatabaseWelcomeTourStateStore;
use Capell\WelcomeTour\State\SessionWelcomeTourStateStore;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;

final class WelcomeTourStateStoreResolver
{
    public function resolve(): WelcomeTourStateStore
    {
        if ($this->isPreview()) {
            return new SessionWelcomeTourStateStore(resolve(Session::class), 'capell_welcome_tour.preview_state');
        }

        return config('capell-welcome-tour.presentation_mode', false)
            ? resolve(SessionWelcomeTourStateStore::class)
            : resolve(DatabaseWelcomeTourStateStore::class);
    }

    public function isPreview(): bool
    {
        $user = auth()->user();

        return $user instanceof Model
            && session()->get('capell_welcome_tour.preview_user') === json_encode([$user->getMorphClass(), $user->getKey()], JSON_THROW_ON_ERROR);
    }
}
