<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\Admin\Data\WelcomeTourStepData;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class ResolveWelcomeTourStepsForUserAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  list<WelcomeTourStepData>  $steps
     * @return list<WelcomeTourStepData>
     */
    public function handle(Model $user, array $steps, string $tourKey = 'capell_admin_welcome'): array
    {
        $state = GetUserWelcomeTourStateAction::run($user, $tourKey);

        if ($state->completedStepKeys === []) {
            return $steps;
        }

        return array_values(collect($steps)
            ->reject(fn (WelcomeTourStepData $step): bool => in_array($step->key, $state->completedStepKeys, true))
            ->values()
            ->all());
    }
}
