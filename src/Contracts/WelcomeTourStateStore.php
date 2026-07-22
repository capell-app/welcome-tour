<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Contracts;

use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Illuminate\Database\Eloquent\Model;

interface WelcomeTourStateStore
{
    public function state(Model $user, string $tourKey): WelcomeTourUserStateData;

    public function recordStep(Model $user, string $stepKey, string $tourKey): void;

    public function reset(Model $user, string $tourKey): void;

    public function restartProgress(Model $user, string $tourKey): void;

    public function setChecklistItemCompleted(Model $user, string $itemKey, bool $completed, string $tourKey): void;

    public function setChecklistDismissed(Model $user, bool $dismissed, string $tourKey): void;

    public function dismiss(Model $user, string $tourKey): void;

    public function snooze(Model $user, int $hours, string $tourKey): void;

    public function hasAutoStarted(Model $user, string $tourKey): bool;

    public function markAutoStarted(Model $user, string $tourKey): void;
}
