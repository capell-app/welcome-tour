<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Admin\Facades\CapellAdmin;
use Closure;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

final class WelcomeTourStepContributor
{
    public static function dashboardStep(
        string $key,
        string|Closure $title,
        string|Closure|HtmlString|View $description,
        ?string $element = null,
        ?string $icon = null,
        ?string $iconColor = null,
        int $sort = 100,
        bool|Closure $visible = true,
        ?string $chapter = 'dashboard',
        ?string $route = null,
    ): void {
        CapellAdmin::registerWelcomeTourStep(
            key: $key,
            title: $title,
            description: $description,
            element: $element,
            icon: $icon,
            iconColor: $iconColor,
            sort: $sort,
            visible: $visible,
            chapter: $chapter,
            route: $route,
        );
    }

    public static function contextualStep(
        string $tourKey,
        string $key,
        string|Closure $title,
        string|Closure|HtmlString|View $description,
        ?string $element = null,
        ?string $icon = null,
        ?string $iconColor = null,
        int $sort = 100,
        bool|Closure $visible = true,
    ): void {
        resolve(ContextualWelcomeTourRegistry::class)->registerStep(
            tourKey: $tourKey,
            key: $key,
            title: $title,
            description: $description,
            element: $element,
            icon: $icon,
            iconColor: $iconColor,
            sort: $sort,
            visible: $visible,
        );
    }
}
