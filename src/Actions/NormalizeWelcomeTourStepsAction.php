<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Lorisleiva\Actions\Concerns\AsObject;

final class NormalizeWelcomeTourStepsAction
{
    use AsObject;

    /**
     * @param  array<array-key, mixed>  $steps
     * @return list<array<string, mixed>>
     */
    public function handle(array $steps): array
    {
        $normalized = [];
        foreach (array_values($steps) as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $key = $step['key'] ?? null;
            if (! is_string($key) || trim($key) === '') {
                $key = 'custom.' . substr(hash('sha256', json_encode([
                    $step['title'] ?? '', $step['description'] ?? '', $step['route'] ?? '@dashboard', $step['resource'] ?? '', $step['element'] ?? '',
                ], JSON_THROW_ON_ERROR)), 0, 20);
            }

            $normalized[] = [
                ...$step,
                'key' => $key,
                'sort' => is_numeric($step['sort'] ?? null) ? (int) $step['sort'] : ($index + 1) * 10,
            ];
        }

        return $normalized;
    }
}
