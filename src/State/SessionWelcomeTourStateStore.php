<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\State;

use Capell\WelcomeTour\Contracts\WelcomeTourStateStore;
use Capell\WelcomeTour\Data\WelcomeTourUserStateData;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;

final class SessionWelcomeTourStateStore implements WelcomeTourStateStore
{
    private const string SESSION_KEY = 'capell_welcome_tour';

    public function __construct(private readonly Session $session) {}

    public function state(Model $user, string $tourKey): WelcomeTourUserStateData
    {
        $state = $this->session->get($this->key($tourKey), []);

        return WelcomeTourUserStateData::fromArray(is_array($state) ? $state : []);
    }

    public function recordStep(Model $user, string $stepKey, string $tourKey): void
    {
        if ($stepKey === '') {
            return;
        }

        $state = $this->arrayState($tourKey);
        $storedStepKeys = $state['completed_step_keys'] ?? [];
        $completedStepKeys = collect(is_array($storedStepKeys) ? $storedStepKeys : [])
            ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
            ->push($stepKey)
            ->unique()
            ->values()
            ->all();

        $this->put($tourKey, [
            ...$state,
            'completed_step_keys' => $completedStepKeys,
            'last_completed_step_key' => $stepKey,
        ]);
    }

    public function reset(Model $user, string $tourKey): void
    {
        $this->put($tourKey, [
            'completed_step_keys' => [],
            'last_completed_step_key' => null,
            'snoozed_until' => null,
            'dismissed' => false,
            'auto_started' => true,
        ]);
    }

    public function restartProgress(Model $user, string $tourKey): void
    {
        $this->put($tourKey, [
            ...$this->arrayState($tourKey),
            'completed_step_keys' => [],
            'last_completed_step_key' => null,
            'snoozed_until' => null,
        ]);
    }

    public function dismiss(Model $user, string $tourKey): void
    {
        $this->put($tourKey, [...$this->arrayState($tourKey), 'dismissed' => true]);
    }

    public function snooze(Model $user, int $hours, string $tourKey): void
    {
        $this->put($tourKey, [
            ...$this->arrayState($tourKey),
            'snoozed_until' => CarbonImmutable::now()->addHours(max(1, $hours))->toIso8601String(),
        ]);
    }

    public function hasAutoStarted(Model $user, string $tourKey): bool
    {
        return ($this->arrayState($tourKey)['auto_started'] ?? false) === true;
    }

    public function markAutoStarted(Model $user, string $tourKey): void
    {
        $this->put($tourKey, [...$this->arrayState($tourKey), 'auto_started' => true]);
    }

    /** @return array<string, mixed> */
    private function arrayState(string $tourKey): array
    {
        $state = $this->session->get($this->key($tourKey), []);

        return is_array($state) ? $state : [];
    }

    /** @param array<string, mixed> $state */
    private function put(string $tourKey, array $state): void
    {
        $this->session->put($this->key($tourKey), $state);
    }

    private function key(string $tourKey): string
    {
        return self::SESSION_KEY . '.' . $tourKey;
    }
}
