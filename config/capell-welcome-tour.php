<?php

declare(strict_types=1);
use Capell\Admin\Filament\Pages\ExtensionsPage;
use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Admin\Filament\Resources\Sites\SiteResource;
use Capell\Admin\Filament\Resources\Themes\ThemeResource;

return [
    'enabled' => true,

    'presentation_mode' => false,

    'steps' => [],

    'manifest_steps' => [],

    'contextual_tours' => [],

    'checklist' => [
        [
            'key' => 'create-site',
            'label' => 'capell-welcome-tour::welcome_tour.checklist_create_site',
            'description' => 'capell-welcome-tour::welcome_tour.checklist_create_site_description',
            'resource' => SiteResource::class,
            'complete_when' => 'table-has-rows:sites',
        ],
        [
            'key' => 'create-page',
            'label' => 'capell-welcome-tour::welcome_tour.checklist_create_page',
            'description' => 'capell-welcome-tour::welcome_tour.checklist_create_page_description',
            'resource' => PageResource::class,
            'resource_page' => 'create',
            'complete_when' => 'table-has-rows:pages',
        ],
        [
            'key' => 'pick-theme',
            'label' => 'capell-welcome-tour::welcome_tour.checklist_pick_theme',
            'description' => 'capell-welcome-tour::welcome_tour.checklist_pick_theme_description',
            'resource' => ThemeResource::class,
            'complete_when' => 'non-default-theme',
        ],
        [
            'key' => 'extend',
            'label' => 'capell-welcome-tour::welcome_tour.checklist_extend',
            'description' => 'capell-welcome-tour::welcome_tour.checklist_extend_description',
            'resource' => ExtensionsPage::class,
            'complete_when' => '',
        ],
    ],
];
