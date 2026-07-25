<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Enums;

enum WelcomeTourReadinessStatus: string
{
    case Complete = 'complete';
    case ActionRequired = 'action_required';
    case Blocked = 'blocked';
}
