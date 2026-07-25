<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction;
use Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Capell\WelcomeTour\Data\WelcomeTourReadinessData;
use Capell\WelcomeTour\Enums\WelcomeTourReadinessStatus;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use InvalidArgumentException;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildWelcomeTourChecklistAction
{
    use AsFake;
    use AsObject;

    /**
     * @return list<WelcomeTourChecklistItemData>
     */
    public function handle(?Model $user = null): array
    {
        $items = config('capell-welcome-tour.checklist', []);

        if (! is_array($items)) {
            return [];
        }

        return array_values(collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->filter(fn (array $item): bool => $this->isVisible($item))
            ->map(fn (array $item): WelcomeTourChecklistItemData => $this->itemData($item, $user))
            ->values()
            ->all());
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function itemData(array $item, ?Model $user): WelcomeTourChecklistItemData
    {
        $key = $this->stringValue($item, 'key');
        $manuallyCompleted = $user instanceof Model
            && in_array($key, GetUserWelcomeTourStateAction::run($user)->completedChecklistItemKeys, true);
        $readiness = $this->resolveReadiness($item, $user);

        return new WelcomeTourChecklistItemData(
            key: $key,
            label: $this->translate($this->stringValue($item, 'label')),
            description: $this->translate($this->stringValue($item, 'description')),
            url: $readiness->recoveryUrl ?? ResolveWelcomeTourDestinationAction::run(
                $this->nullableStringValue($item, 'url'),
                $this->nullableStringValue($item, 'resource'),
                $this->nullableStringValue($item, 'resource_page'),
            ),
            complete: $manuallyCompleted || $readiness->status === WelcomeTourReadinessStatus::Complete,
            manuallyCompleted: $manuallyCompleted,
            status: $manuallyCompleted ? WelcomeTourReadinessStatus::Complete : $readiness->status,
            explanation: $this->translate($readiness->explanation),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveReadiness(array $item, ?Model $user): WelcomeTourReadinessData
    {
        $resolver = $this->nullableStringValue($item, 'resolver');

        if ($resolver !== null) {
            $resolved = resolve($resolver);

            throw_unless(
                $resolved instanceof WelcomeTourReadinessResolver,
                InvalidArgumentException::class,
                "Welcome Tour checklist resolver [{$resolver}] must implement " . WelcomeTourReadinessResolver::class . '.',
            );

            return $resolved->resolve($user, $item);
        }

        $complete = $this->isComplete($this->stringValue($item, 'complete_when'));

        return new WelcomeTourReadinessData(
            status: $complete
                ? WelcomeTourReadinessStatus::Complete
                : WelcomeTourReadinessStatus::ActionRequired,
            explanation: $this->stringValue($item, 'description'),
        );
    }

    private function isComplete(string $condition): bool
    {
        if ($condition === '') {
            return false;
        }

        [$type, $value] = array_pad(explode(':', $condition, 2), 2, '');

        return match ($type) {
            'table-has-rows' => $value !== ''
                && WelcomeTourSchema::hasTable($value)
                && DB::table($value)->exists(),
            'table-exists' => $value !== '' && WelcomeTourSchema::hasTable($value),
            'non-default-theme' => WelcomeTourSchema::hasTable('themes')
                && DB::table('themes')->where('default', false)->where('status', true)->exists(),
            default => false,
        };
    }

    /** @param array<string, mixed> $item */
    private function isVisible(array $item): bool
    {
        $resource = $item['resource'] ?? null;

        if (! is_string($resource) || $resource === '') {
            return true;
        }

        return class_exists($resource)
            && method_exists($resource, 'canAccess')
            && $resource::canAccess();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function stringValue(array $item, string $key): string
    {
        $value = $item[$key] ?? '';

        return is_string($value) ? $value : '';
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function nullableStringValue(array $item, string $key): ?string
    {
        $value = $this->stringValue($item, $key);

        return $value === '' ? null : $value;
    }

    private function translate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return Lang::has($value) ? (string) __($value) : $value;
    }
}
