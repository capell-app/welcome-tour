<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Lorisleiva\Actions\Concerns\AsObject;

final class BuildWelcomeTourChecklistAction
{
    use AsObject;

    /**
     * @return list<WelcomeTourChecklistItemData>
     */
    public function handle(): array
    {
        $items = config('capell-welcome-tour.checklist', []);

        if (! is_array($items)) {
            return [];
        }

        return array_values(collect($items)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): WelcomeTourChecklistItemData => $this->itemData($item))
            ->values()
            ->all());
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function itemData(array $item): WelcomeTourChecklistItemData
    {
        return new WelcomeTourChecklistItemData(
            key: $this->stringValue($item, 'key'),
            label: $this->translate($this->stringValue($item, 'label')),
            description: $this->translate($this->stringValue($item, 'description')),
            url: $this->internalAdminUrl($this->nullableStringValue($item, 'url')),
            complete: $this->isComplete($this->stringValue($item, 'complete_when')),
        );
    }

    private function internalAdminUrl(?string $url): ?string
    {
        if ($url === null || $url === '' || str_starts_with($url, '//')) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($host !== null && (! is_string($host) || ! is_string($appHost) || strcasecmp($host, $appHost) !== 0)) {
            return null;
        }

        $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
        $adminPath = '/' . trim((string) config('filament.panels.admin.path', 'admin'), '/');

        return $path === $adminPath || str_starts_with($path, $adminPath . '/') ? $url : null;
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
            default => false,
        };
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
