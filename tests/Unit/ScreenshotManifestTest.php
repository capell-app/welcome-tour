<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('loads the lazy checklist before starting the required overlay capture', function (): void {
    $screenshots = json_decode(
        File::get(__DIR__ . '/../../docs/screenshots.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );

    $entry = collect($screenshots['entries'])->firstWhere('id', 'welcome-tour-overlay');

    expect($entry)->toMatchArray([
        'beforeWait' => [
            [
                'type' => 'scrollIntoView',
                'selector' => '[wire\\:name="capell.welcome-tour.filament.widgets.welcome-tour-checklist-filament-widget"]',
            ],
            [
                'type' => 'waitFor',
                'selector' => 'button[wire\\:click="startTour"]',
            ],
            [
                'type' => 'click',
                'selector' => 'button[wire\\:click="startTour"]',
            ],
        ],
        'waitFor' => '.driver-popover',
    ]);
});
