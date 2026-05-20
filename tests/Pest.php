<?php

declare(strict_types=1);

use Capell\WelcomeTour\Tests\WelcomeTourTestCase;

pest()->extend(WelcomeTourTestCase::class)->in('Feature', 'Unit');
