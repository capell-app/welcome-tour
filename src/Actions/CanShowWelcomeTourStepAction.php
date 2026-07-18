<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

/**
 * @method static bool run(array<string, mixed> $step, ?Model $user = null)
 */
final class CanShowWelcomeTourStepAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  array<string, mixed>  $step
     */
    public function handle(array $step, ?Model $user = null): bool
    {
        if (! $this->booleanValue($step, 'visible', true)) {
            return false;
        }

        return $this->matchesRoles($step, $user)
            && $this->matchesFirstRunWindow($step, $user)
            && $this->canAccessResource($step);
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function matchesRoles(array $step, ?Model $user): bool
    {
        $roles = $this->stringListValue($step, 'roles');

        if ($roles === []) {
            return true;
        }

        if (! $user instanceof Model) {
            return false;
        }

        if (method_exists($user, 'hasRole')) {
            try {
                foreach ($roles as $role) {
                    if ((bool) $user->hasRole($role)) {
                        return true;
                    }
                }
            } catch (Throwable) {
                //
            }
        }

        $role = $user->getAttribute('role');

        return is_string($role) && in_array($role, $roles, true);
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function matchesFirstRunWindow(array $step, ?Model $user): bool
    {
        $days = $this->integerValue($step, 'user_created_within_days', 0);

        if ($days <= 0) {
            return true;
        }

        if (! $user instanceof Model) {
            return false;
        }

        $createdAt = $user->getAttribute('created_at');

        return $createdAt instanceof CarbonInterface
            && $createdAt->greaterThanOrEqualTo(now()->subDays($days));
    }

    /** @param array<string, mixed> $step */
    private function canAccessResource(array $step): bool
    {
        $resource = $step['resource'] ?? null;

        if (! is_string($resource) || $resource === '') {
            return true;
        }

        return class_exists($resource)
            && method_exists($resource, 'canAccess')
            && $resource::canAccess();
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function booleanValue(array $step, string $key, bool $default): bool
    {
        $value = $step[$key] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
            return strtolower($value) === 'true';
        }

        return $default;
    }

    /**
     * @param  array<string, mixed>  $step
     */
    private function integerValue(array $step, string $key, int $default): int
    {
        $value = $step[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $step
     * @return list<string>
     */
    private function stringListValue(array $step, string $key): array
    {
        $value = $step[$key] ?? [];

        if (is_string($value)) {
            $value = array_map(trim(...), explode(',', $value));
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(collect($value)
            ->filter(fn (mixed $entry): bool => is_string($entry) && $entry !== '')
            ->values()
            ->all());
    }
}
