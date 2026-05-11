<?php

declare(strict_types=1);

return [
    'enabled' => true,

    'steps' => [
        [
            'key' => 'capell-welcome-tour.introduction',
            'title' => 'capell-welcome-tour::welcome_tour.introduction_title',
            'description' => 'capell-welcome-tour::welcome_tour.introduction_description',
            'element' => null,
            'icon' => 'heroicon-o-sparkles',
            'icon_color' => 'primary',
            'sort' => 10,
            'visible' => true,
        ],
        [
            'key' => 'capell-welcome-tour.menu',
            'title' => 'capell-welcome-tour::welcome_tour.menu_title',
            'description' => 'capell-welcome-tour::welcome_tour.menu_description',
            'element' => null,
            'icon' => 'heroicon-o-bars-3',
            'icon_color' => 'gray',
            'sort' => 20,
            'visible' => true,
        ],
        [
            'key' => 'capell-welcome-tour.header-tools',
            'title' => 'capell-welcome-tour::welcome_tour.header_tools_title',
            'description' => 'capell-welcome-tour::welcome_tour.header_tools_description',
            'element' => null,
            'icon' => 'heroicon-o-wrench-screwdriver',
            'icon_color' => 'gray',
            'sort' => 30,
            'visible' => true,
        ],
        [
            'key' => 'capell-welcome-tour.dashboard',
            'title' => 'capell-welcome-tour::welcome_tour.dashboard_title',
            'description' => 'capell-welcome-tour::welcome_tour.dashboard_description',
            'element' => null,
            'icon' => 'heroicon-o-home',
            'icon_color' => 'success',
            'sort' => 40,
            'visible' => true,
        ],
    ],
];
