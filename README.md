# Welcome Tour

<!-- prettier-ignore-start -->

## What This Plugin Adds

Welcome Tour is an **Available**, **Schema-owning** Capell package in the **Capell Foundation** product group. It ships as `capell-app/welcome-tour` and extends these surfaces: admin.

Configurable Filament onboarding tours and per-user welcome flow for the Capell admin panel.

After install, admins get package-owned management or reporting surfaces inside Capell.

Status details:

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/welcome-tour`
- Namespace: `Capell\WelcomeTour`
- Theme key: not applicable

## Why It Matters

**For developers:** The package gives developers package-owned service providers, Actions, Data objects, Filament classes, and Blade views instead of pushing this behaviour into core or application code.

**For teams:** Guided, in-product onboarding for Capell Admin - configurable multi-step tours that introduce new editors to sites, pages, media, and settings, with per-user dismiss and resume.

## Screens And Workflow

Screenshot contract: `docs/screenshots.json`.

- Admin dashboard rendered through WelcomeTourDashboard (admin, required).
- Welcome tour overlay showing the first configured onboarding step (admin, required).
- Welcome tour settings group with enabled toggle and step repeater (admin, required).
- User edit form with Show welcome tour toggle (admin, required).

## Technical Shape

- Service providers: `Capell\WelcomeTour\Providers\WelcomeTourServiceProvider`.
- Config files: `packages/welcome-tour/config/capell-welcome-tour.php`.
- Migrations: `packages/welcome-tour/database/migrations/2026_06_04_000001_create_welcome_tour_user_states_table.php`.
- Settings migrations: `packages/welcome-tour/database/settings/2026_05_10_190836_01_add_welcome_tour_settings.php`.
- Settings classes: `WelcomeTourSettings`.
- Filament classes: `HasContextualWelcomeTour`, `WelcomeTourPanelExtender`, `WelcomeTourDashboard`, `WelcomeTourSettingsPage`, `WelcomeTourSettingsSchema`, `WelcomeTourChecklistWidget`.
- Events: `WelcomeTourCompleted`, `WelcomeTourRestarted`, `WelcomeTourSnoozed`, `WelcomeTourStarted`, `WelcomeTourStepCompleted`.
- Actions: `BuildWelcomeTourChecklistAction`, `CanShowWelcomeTourStepAction`, `ResolveWelcomeTourEnabledAction`, `CanShowWelcomeTourAction`, `GetUserWelcomeTourStateAction`, `RecordWelcomeTourStepAction`, `ResetUserWelcomeTourAction`, `ResolveWelcomeTourStepsForUserAction`, `SetUserWelcomeTourPreferenceAction`, `SnoozeUserWelcomeTourAction`.
- Data objects: `WelcomeTourChecklistItemData`, `WelcomeTourUserStateData`.
- Manifest contributions: `dashboard-widget: Capell\WelcomeTour\Manifest\WelcomeTourChecklistWidgetContribution`, `setting: Capell\WelcomeTour\Manifest\WelcomeTourSettingsContribution`, `health-check: Capell\WelcomeTour\Manifest\WelcomeTourHealthContribution`.
- Health checks: `Capell\WelcomeTour\Health\WelcomeTourHealthCheck`.
- Blade views: `packages/welcome-tour/resources/views/filament/widgets/welcome-tour-checklist.blade.php`.

## Data Model

- Required tables: `welcome_tour_user_states`.
- Migration files: `2026_06_04_000001_create_welcome_tour_user_states_table.php`.
- Migration impact: run host migrations through the package install flow before opening package surfaces.
- Deletion/retention behaviour: per-user tour state is retained until the host user or package-owned state row is removed.

## Install Impact

- Admin navigation: adds package-owned Filament classes when registered.
- Permissions: none declared in `capell.json`.
- Public routes: none detected in package route files.
- Database changes: package migrations are declared.
- Settings: `Capell\WelcomeTour\Settings\WelcomeTourSettings`.
- Queues or schedules: none detected in standard package paths.
- Cache tags: `welcome-tour`.
- Commands: none declared.

## Common Pitfalls

- Run migrations before opening package resources or public routes.
- Configure package settings before testing production-like workflows.
- Keep `composer.json`, `composer.local.json`, `capell.json`, docs, screenshots, and tests aligned when the package surface changes.

## Troubleshooting

| Symptom | Likely cause | Check | Fix |
| --- | --- | --- | --- |
| Package surface is missing after install | Provider or manifest is not loaded | Confirm `capell.json`, package `composer.json`, and provider registration | Reinstall the package, refresh Composer autoload, and clear host caches |
| Admin screen or command fails on missing table | Package migrations have not run | Check the tables listed in `Data Model` | Run host migrations and rerun the focused package test |

## Quick Start

1. Install the package: `composer require capell-app/welcome-tour`.
2. Run host migrations and settings migrations through the installed Capell app.
3. Open the related Capell admin surface and verify Welcome Tour appears.

## Next Steps

- [Package docs](docs/README.md)
- [Overview](docs/overview.md)
- [Screenshot contract](docs/screenshots.json)
- [Marketplace assets](docs/assets/marketplace/)
- [Capell content language plan](../../docs/CONTENT_LANGUAGE_PLAN.md)
- [Capell documentation design system](../../docs/DESIGN_SYSTEM.md)
- [Capell and package ERD notes](../../docs/erd/capell-and-package-erds.md)
- Focused tests: `vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml`.

<!-- prettier-ignore-end -->
