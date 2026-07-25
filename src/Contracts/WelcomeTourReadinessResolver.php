<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Contracts;

use Capell\WelcomeTour\Data\WelcomeTourReadinessData;
use Illuminate\Database\Eloquent\Model;

interface WelcomeTourReadinessResolver
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function resolve(?Model $user = null, array $context = []): WelcomeTourReadinessData;
}
