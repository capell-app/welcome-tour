# Steps And Settings

Welcome Tour replaces the default admin dashboard with `WelcomeTourDashboard`, registers the Filament tour plugin, and reads tour steps from settings or package config.

## Runtime Surface

| Surface              | Code                                   |
| -------------------- | -------------------------------------- |
| Enabled flag         | `capell-welcome-tour.enabled`          |
| Default steps        | `capell-welcome-tour.steps`            |
| Contextual tours     | `capell-welcome-tour.contextual_tours` |
| Settings group       | `welcome-tour`                         |
| Admin panel extender | `WelcomeTourPanelExtender`             |
| User resource bridge | `WelcomeTourUserResourceBridge`        |
| Dashboard page       | `WelcomeTourDashboard`                 |

## Add a Step in Config

```php
// config/capell-welcome-tour.php
'steps' => [
    [
        'key' => 'host-app.first-page',
        'title' => 'host-app::welcome_tour.first_page_title', // or literal owner-authored copy
        'description' => 'host-app::welcome_tour.first_page_description',
        'element' => '#first-page-button',
        'icon' => 'heroicon-o-document-plus',
        'icon_color' => 'primary',
        'sort' => 50,
        'visible' => true,
        'roles' => ['admin'],
        'user_created_within_days' => 14,
    ],
],
```

`WelcomeTourStepRegistrar` translates `title` and `description` when the values are real translation keys, accepts literal strings otherwise, and derives deterministic keys and editor ordering when a config row omits them. The editor generates a persistent key for each new row; explicit keys and sort values remain supported. Do not pre-escape descriptions in config; keep them plain text.

## Register a Step From Code

Use `WelcomeTourStepContributor::dashboardStep()` when the step belongs to another package and should only exist when that package is installed.

```php
use Capell\WelcomeTour\Support\WelcomeTourStepContributor;

WelcomeTourStepContributor::dashboardStep(
    key: 'demo-kit.example-site',
    title: static fn (): string => __('capell-demo-kit::welcome_tour.example_site_title'),
    description: static fn (): string => __('capell-demo-kit::welcome_tour.example_site_description'),
    element: '#demo-kit-example-site',
    icon: 'heroicon-o-sparkles',
    iconColor: 'success',
    sort: 80,
    visible: true,
);
```

Keep selectors stable. A missing selector means the step cannot anchor to the UI element.

## Contextual Page Tours

Dashboard steps introduce the main admin areas. Contextual tours guide editors once they are already on a specific Filament page or resource surface. The package ships default scoped tours for Sites, Pages, and Media under:

- `capell_admin_sites`
- `capell_admin_pages`
- `capell_admin_media`

Add `Capell\WelcomeTour\Filament\Concerns\HasContextualWelcomeTour` to the Filament page that should render a scoped tour, then set its `$welcomeTourKey` to one of those keys or a host/package-owned key.

```php
use Capell\WelcomeTour\Filament\Concerns\HasContextualWelcomeTour;
use Filament\Resources\Pages\ListRecords;

class ListPages extends ListRecords
{
    use HasContextualWelcomeTour;

    protected string $welcomeTourKey = 'capell_admin_pages';
}
```

Packages can contribute contextual steps without taking over the dashboard:

```php
use Capell\WelcomeTour\Support\WelcomeTourStepContributor;

WelcomeTourStepContributor::contextualStep(
    tourKey: 'capell_admin_pages',
    key: 'demo-kit.page-example',
    title: static fn (): string => __('capell-demo-kit::welcome_tour.page_example_title'),
    description: static fn (): string => __('capell-demo-kit::welcome_tour.page_example_description'),
    element: '#demo-kit-page-example',
    icon: 'heroicon-o-sparkles',
    iconColor: 'success',
    sort: 80,
);
```

Contextual tours use the same package-owned per-user state table as the dashboard tour, keyed by tour key, so progress and dismissal remain independent per page surface.

## Targeting

Configured steps can be limited by:

- `roles`: array or comma-separated role names.
- `user_created_within_days`: first-run window based on the authenticated user's `created_at`.

Code-side contributors can pass a `visible` closure to `WelcomeTourStepContributor::dashboardStep()` or `WelcomeTourStepContributor::contextualStep()` for package-specific targeting.

The package ships a generic getting started checklist and no dashboard guided steps. Applications and packages contribute their own steps. An empty guided sequence is a supported state, not an incomplete installation. Contextual tours remain separate and appear only on opted-in page surfaces.

## Checklist

The dashboard checklist is configured through `capell-welcome-tour.checklist`. Supported completion conditions are:

- `table-exists:{table}`
- `table-has-rows:{table}`

Checklist items are admin-only dashboard data and do not affect public frontend output.

## Analytics Events

Listen for these events to feed activation analytics:

- `Capell\WelcomeTour\Events\WelcomeTourStarted`
- `Capell\WelcomeTour\Events\WelcomeTourStepCompleted`
- `Capell\WelcomeTour\Events\WelcomeTourSnoozed`
- `Capell\WelcomeTour\Events\WelcomeTourCompleted`
- `Capell\WelcomeTour\Events\WelcomeTourRestarted`

## Per-User State

Welcome Tour stores dismissal in the host `users.dismissed_hints` column when that column exists. Hosts without that column use the package-owned `welcome_tour_user_states` table instead.

The package table also records completed step keys and snooze state. When a user leaves part-way through the tour, the dashboard resumes at the first incomplete step. The dashboard header includes Remind me later and Restart tour actions for the current user.

## Verification

```bash
vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml
```

## Operator controls and developer authoring

Extension settings lead with **Enabled**, the current actor's checklist and chapter summary, **Preview as me**, and **Restart my tour**. The summary uses the saved registry and applies the current actor's visibility and resource permissions. Save settings before previewing. Preview runs the existing tour renderer with isolated session state; completion, dismissal and snooze leave database preferences and progress unchanged. Restart clears only the current actor's main tour progress and snooze. Account eligibility remains a separate User form preference; enabling it no longer clears completed steps.

**Advanced developer** contains the optional step editor. Start with title, description and destination. Destination details accept `@dashboard`, an existing local path, or a resource/page class and page name. Resources take precedence. Unresolvable destinations and unsupported selectors fail form validation. New keys are generated once; dragging rows controls ordering unless a numeric sort override is supplied. A blank chapter groups steps by destination; explicitly grouped steps must share the same destination. Audience and Appearance are collapsed groups.

Use stable selectors composed of tags, IDs, classes and attributes, optionally joined by combinators. The live controller checks remaining targets on the rendered destination before opening the preview, reports missing or invalid targets, and does not mark them complete. Per-step progress is recorded for resume; chapter and step positions are visible in the overlay title.

Screenshot routes and the light/dark viewport matrix are specified in `screenshots.json`. Captures must be produced separately in an installed App; this change supplies Livewire and executable DOM tests, not browser evidence.
