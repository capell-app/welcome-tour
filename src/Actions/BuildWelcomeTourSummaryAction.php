<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\Admin\Facades\CapellAdmin;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Data\WelcomeTourSummaryData;
use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildWelcomeTourSummaryAction
{
    use AsObject;

    public function handle(): WelcomeTourSummaryData
    {
        $user = auth()->user();
        $user = $user instanceof Model ? $user : null;

        return new WelcomeTourSummaryData(
            eligible: CanShowWelcomeTourAction::run($user),
            checklist: $user instanceof Model ? BuildWelcomeTourChecklistAction::run($user) : [],
            chapters: $user instanceof Model ? ResolveWelcomeTourChaptersAction::run(
                CapellAdmin::getWelcomeTourSteps(),
                new WelcomeTourUserStateData([], null, null, false),
            ) : [],
        );
    }
}
