<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('starts overlay previews through the disposable fixture and a stable dashboard target', function (): void {
    $screenshots = json_decode(
        File::get(__DIR__ . '/../../docs/screenshots.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    $entries = is_array($screenshots) ? ($screenshots['entries'] ?? null) : null;
    throw_unless(is_array($entries), RuntimeException::class, 'Expected the welcome-tour screenshot manifest to contain entries.');

    $entry = collect($entries)->firstWhere('id', 'welcome-tour-overlay');

    expect($entry)->toMatchArray([
        'url' => '/screenshot-fixtures/welcome-tour/overlay',
        'beforeWait' => [
            [
                'type' => 'waitFor',
                'selector' => '.fi-header-heading',
            ],
        ],
        'waitFor' => '.driver-popover',
    ]);
});
