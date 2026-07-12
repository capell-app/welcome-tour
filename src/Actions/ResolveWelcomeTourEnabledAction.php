<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\WelcomeTour\Settings\WelcomeTourSettings;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static bool run()
 */
final class ResolveWelcomeTourEnabledAction
{
    use AsObject;

    public function handle(): bool
    {
        try {
            return resolve(WelcomeTourSettings::class)->enabled;
        } catch (Throwable) {
            return (bool) config('capell-welcome-tour.enabled', true);
        }
    }
}
