# Welcome Tour

<!-- prettier-ignore-start -->

## What This Plugin Adds

Welcome Tour is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/welcome-tour` and extends these surfaces: admin.

Welcome Tour adds a contextual admin checklist with per-user step state, preferences, snoozing, and reset behavior. The checklist is exposed as a Filament dashboard widget.

Administrators see relevant setup steps on the dashboard and can complete, snooze, or reset their own tour state.

Evidence: [`src/Filament/Widgets/WelcomeTourChecklistFilamentWidget.php`](src/Filament/Widgets/WelcomeTourChecklistFilamentWidget.php), [`src/Actions/BuildWelcomeTourChecklistAction.php`](src/Actions/BuildWelcomeTourChecklistAction.php), [`src/Support/ContextualWelcomeTourRegistry.php`](src/Support/ContextualWelcomeTourRegistry.php), [`tests/Feature/WelcomeTourTest.php`](tests/Feature/WelcomeTourTest.php), [`src/Filament/Extenders/WelcomeTourPanelExtender.php`](src/Filament/Extenders/WelcomeTourPanelExtender.php).

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/welcome-tour`
- Namespace: `Capell\WelcomeTour`
- Theme key: not applicable

## Why It Matters

**For developers:** A contextual registry and focused Actions keep checklist composition separate from the Filament widget and persisted user state.

**For teams:** Teams can give each administrator a repeatable setup path without forcing every user through the same uninterrupted sequence.

Evidence: [`src/Support/ContextualWelcomeTourRegistry.php`](src/Support/ContextualWelcomeTourRegistry.php), [`src/Actions/BuildWelcomeTourChecklistAction.php`](src/Actions/BuildWelcomeTourChecklistAction.php), [`tests/Unit/WelcomeTourSchemaCoverageTest.php`](tests/Unit/WelcomeTourSchemaCoverageTest.php), [`src/Filament/Widgets/WelcomeTourChecklistFilamentWidget.php`](src/Filament/Widgets/WelcomeTourChecklistFilamentWidget.php), [`tests/Feature/WelcomeTourTest.php`](tests/Feature/WelcomeTourTest.php), [`tests/Feature/WelcomeTourSettingsTest.php`](tests/Feature/WelcomeTourSettingsTest.php).

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

![Capell admin dashboard with the Welcome Tour onboarding checklist](docs/screenshots/welcome-tour-dashboard.png)

![Welcome tour overlay showing the first configured onboarding step](docs/screenshots/welcome-tour-overlay.png)

Desktop, tablet, and mobile variants remain defined in the screenshot contract; this list groups them by workflow.

- Capell admin dashboard with the Welcome Tour onboarding checklist (admin, required evidence).
- Welcome tour overlay showing the first configured onboarding step (admin, required evidence).
- Welcome Tour settings with ordinary onboarding controls and collapsed developer details (admin, required evidence).
- User edit form with Show welcome tour toggle (admin, required evidence).
- Capell admin dashboard with the Welcome Tour onboarding checklist with admin sidebar menu open (admin, supplementary evidence).
- Welcome Tour grouped developer authoring and destination validation (admin, required evidence).

## Technical Shape

### Service providers

- `Capell\WelcomeTour\Providers\WelcomeTourServiceProvider`

### Config files

- `packages/welcome-tour/config/capell-welcome-tour.php`

### Migrations

- `packages/welcome-tour/database/migrations/2026_06_04_000001_create_welcome_tour_user_states_table.php`
- `packages/welcome-tour/database/migrations/2026_07_19_000001_add_checklist_state_to_welcome_tour_user_states_table.php`

### Settings migrations

- `packages/welcome-tour/database/settings/2026_05_10_190836_01_add_welcome_tour_settings.php`
- `packages/welcome-tour/database/settings/2026_07_19_000001_upgrade_welcome_tour_chapter_steps.php`

### Settings classes

- `WelcomeTourSettings`

### Filament classes

- `HasContextualWelcomeTour`
- `WelcomeTourPanelExtender`
- `WelcomeTourDashboard`
- `WelcomeTourSettingsSchema`
- `WelcomeTourChecklistFilamentWidget`

### Livewire components

- `WelcomeTourOrchestrator`
- `WelcomeTourSettingsControls`

### Extension contracts

- `WelcomeTourReadinessResolver`
- `WelcomeTourStateStore`

### Events

- `WelcomeTourCompleted`
- `WelcomeTourRestarted`
- `WelcomeTourSnoozed`
- `WelcomeTourStarted`
- `WelcomeTourStepCompleted`

### Actions

- `BuildWelcomeTourChecklistAction`
- `BuildWelcomeTourSummaryAction`
- `CanShowWelcomeTourStepAction`
- `NormalizeWelcomeTourStepsAction`
- `ResolveWelcomeTourChaptersAction`
- `ResolveWelcomeTourDestinationAction`
- `ResolveWelcomeTourEnabledAction`
- `AuthorizeWelcomeTourUserMutationAction`
- `CanShowWelcomeTourAction`
- `GetUserWelcomeTourStateAction`
- `RecordWelcomeTourStepAction`
- `ResetUserWelcomeTourAction`
- `ResolveWelcomeTourStepsForUserAction`
- `RestartWelcomeTourProgressAction`
- `SetUserWelcomeTourPreferenceAction`
- `SetWelcomeTourChecklistItemCompletionAction`
- `SetWelcomeTourChecklistVisibilityAction`
- `SnoozeUserWelcomeTourAction`
- `StartWelcomeTourForCurrentUserAction`

### Data objects

- `WelcomeTourChapterData`
- `WelcomeTourChecklistItemData`
- `WelcomeTourReadinessData`
- `WelcomeTourSummaryData`
- `WelcomeTourUserStateData`

### Manifest action API

- `buildWelcomeTourChecklist: Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction`
- `canShowWelcomeTour: Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction`
- `canShowWelcomeTourStep: Capell\WelcomeTour\Actions\CanShowWelcomeTourStepAction`
- `getUserWelcomeTourState: Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction`
- `recordWelcomeTourStep: Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction`
- `resetUserWelcomeTour: Capell\WelcomeTour\Actions\Users\ResetUserWelcomeTourAction`
- `resolveWelcomeTourEnabled: Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction`
- `resolveWelcomeTourStepsForUser: Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction`
- `setUserWelcomeTourPreference: Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction`
- `snoozeUserWelcomeTour: Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction`

### Manifest contributions

- `dashboard-widget: Capell\WelcomeTour\Manifest\WelcomeTourChecklistWidgetContribution`
- `health-check: Capell\WelcomeTour\Manifest\WelcomeTourHealthContribution`
- `setting: Capell\WelcomeTour\Manifest\WelcomeTourSettingsContribution`

### Health checks

- `Capell\WelcomeTour\Health\WelcomeTourHealthCheck`

### Blade views

- `packages/welcome-tour/resources/views/filament/actions/replay-tour.blade.php`
- `packages/welcome-tour/resources/views/filament/settings/controls.blade.php`
- `packages/welcome-tour/resources/views/filament/widgets/welcome-tour-checklist.blade.php`
- `packages/welcome-tour/resources/views/livewire/welcome-tour-orchestrator.blade.php`

### Cache tags

- `welcome-tour`


## Data Model

- Required tables: `welcome_tour_user_states`.
- Core record references in migrations: `users via user_id`.
- Migration files: `2026_06_04_000001_create_welcome_tour_user_states_table.php`, `2026_07_19_000001_add_checklist_state_to_welcome_tour_user_states_table.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: Docs gap: migrations and manifest contributions do not prove a cascade, pruning command, or timed retention policy.

## Install Impact

- Required packages: `capell-app/admin`.
- Admin navigation: no admin page or resource contribution is declared.
- Admin/editor extensions: `dashboard-widget: WelcomeTourChecklistWidgetContribution`.
- Permissions: no package permission declarations or Shield gates detected; host access rules still apply.
- Public routes: none declared.
- Database changes: package migrations are declared.
- Config: `config/capell-welcome-tour.php`.
- Settings: `Capell\WelcomeTour\Settings\WelcomeTourSettings`.
- Queues or schedules: none declared.
- Cache tags: `welcome-tour`.
- Commands: none declared.

## Common Pitfalls

- Keep required Capell packages on compatible v4 releases: `capell-app/admin`.
- Run migrations before opening package resources or public routes.
- Review package configuration before production-like verification: `config/capell-welcome-tour.php`, `Capell\WelcomeTour\Settings\WelcomeTourSettings`.
- Custom write integrations must preserve invalidation for `welcome-tour` cache tags.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |

## Quick Start

1. Install the package: `composer require capell-app/welcome-tour`.
2. Open a verified package admin surface and confirm Welcome Tour is available.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Admin guide](docs/admin-guide.md)
- Configuration files: [`config/capell-welcome-tour.php`](config/capell-welcome-tour.php).
- [Troubleshooting](#troubleshooting)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Focused tests: `vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
