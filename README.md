# Welcome Tour

Optional Filament welcome tour for Capell Admin.

## At A Glance

- Package: `capell-app/welcome-tour`
- Namespace: `Capell\WelcomeTour\`
- Surfaces: Filament admin
- Service providers: `packages/welcome-tour/src/Providers/WelcomeTourServiceProvider.php`
- Capell dependencies: `capell-app/admin`
- Third-party dependencies: `jibaymcs/filament-tour`, `laravel/framework`, `lorisleiva/laravel-actions`, `spatie/laravel-package-tools`, `spatie/laravel-settings`

## Why It Helps Your Capell Workflow

- Adds optional guided onboarding for Capell Admin so new users can learn the panel from inside the product.
- Helps owners introduce editors to key admin workflows without maintaining a separate onboarding checklist.
- Gives developers configurable tour steps and settings while keeping the tour optional for host apps.

## Best Used With

- [Translation Manager](../translation-manager/README.md)
- [Diagnostics](../diagnostics/README.md)
- [Notes](../notes/README.md)

## What It Adds

- Optional Filament welcome tour for Capell Admin.
- Package settings for controlling tour availability.
- Package-owned per-user tour state, including dismissal, progress, and resume.
- Restart and remind-me-later controls for the current admin.
- Dashboard getting-started checklist backed by package configuration.
- Role and first-run targeting for configured steps.
- Lifecycle events for start, step completion, snooze, completion, and restart.
- A reusable `WelcomeTourStepContributor` helper for sibling packages.
- A user edit form bridge for per-user tour visibility when either the package state table or host `dismissed_hints` column is available.

## Code Map

| Area      | Path                                  | Purpose                                                           |
| --------- | ------------------------------------- | ----------------------------------------------------------------- |
| Actions   | `packages/welcome-tour/src/Actions`   | Domain operations. Test these directly where possible.            |
| Data      | `packages/welcome-tour/src/Data`      | Structured user tour state used across Actions and UI.            |
| Filament  | `packages/welcome-tour/src/Filament`  | Admin resources, pages, widgets, and settings UI.                 |
| Providers | `packages/welcome-tour/src/Providers` | Registration, extension hooks, routes, migrations, and resources. |
| Resources | `packages/welcome-tour/resources`     | Views, translations, assets, and package resources.               |
| Config    | `packages/welcome-tour/config`        | Package configuration and publishable config.                     |
| Database  | `packages/welcome-tour/database`      | Migrations, seeders, and settings migrations.                     |
| Tests     | `packages/welcome-tour/tests`         | Package-level Pest coverage.                                      |

## Admin Surface

- Pages: `WelcomeTourDashboard`.
- Settings: Extensions page modal surface for `welcome-tour`.
- Header actions: Restart tour and Remind me later.
- Dashboard widgets: `WelcomeTourChecklistWidget`.
- User edit form bridge: `welcome_tour_enabled`, when per-user tour state can be persisted.

## Data And Persistence

- Config: `packages/welcome-tour/config/capell-welcome-tour.php`.
- Settings: `WelcomeTourSettings` stores the global enabled flag and configured steps.
- User state: `welcome_tour_user_states` stores package-owned per-user progress, snooze, dismissal, and resume state.
- Legacy compatibility: hosts with `users.dismissed_hints` keep using that column for binary dismissal.
- Data objects live in `src/Data/`; use them for structured state at action boundaries.

## Extension Points

- Register package-owned or host-app tour steps with `CapellAdmin::registerWelcomeTourStep()`.
- Prefer `WelcomeTourStepContributor::dashboardStep()` from sibling packages so step contribution stays consistent.
- Keep configured/default step registration in the package service provider so dashboard rendering only reads registered steps.
- Register package settings through `SettingsSchemaRegistry`; Welcome Tour does not expose public frontend render hooks.

## Install And Setup

- Install with `composer require capell-app/welcome-tour` in the host Capell application.
- In this repository, verify package changes with `vendor/bin/pest`; do not use `php artisan`.
- In a disposable host app, publish and run the package settings migration before opening the extension settings modal.
- Run the package migration for `welcome_tour_user_states` when the host user model does not have `dismissed_hints`.

## Docs

- [docs index](docs/README.md)
- [overview.md](docs/overview.md)
- [screenshots.json](docs/screenshots.json)
- [steps-and-settings.md](docs/steps-and-settings.md)

## Testing

Run package tests from the repository root:

```bash
vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml
```

## Maintenance Notes

- Put behaviour changes in `src/Actions/`; UI classes should call actions instead of owning domain logic.
- Use package `Data` classes at boundaries instead of passing anonymous arrays between layers.
- Keep package settings out of the global Settings page; extension settings should live in the Extensions page modal surface.
