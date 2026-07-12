<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Manifest;

use Capell\Core\Contracts\Extensions\ChecksExtensionHealth;

final class WelcomeTourHealthContribution implements ChecksExtensionHealth
{
    public static function compatibleCapellApiVersion(): string
    {
        return '^0.0';
    }
}
