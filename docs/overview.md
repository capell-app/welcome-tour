# Welcome Tour Overview

`capell-app/welcome-tour` keeps guided admin onboarding outside the core admin package.

## Responsibilities

- Registers `jibaymcs/filament-tour` only when this package is installed.
- Provides `WelcomeTourDashboard`, a dashboard subclass that uses the Filament tour trait.
- Registers default tour steps from configurable translation keys.
- Adds package settings for enabling the tour and editing the step list.
- Adds package-owned per-user tour state for dismissal, snooze, progress, resume, and restart.
- Adds a dashboard getting-started checklist for the first activation tasks.
- Emits lifecycle events for start, step completion, snooze, completion, and restart.
- Adds a user resource bridge for per-user tour visibility.

## Installation Audit

- Composer package: `capell-app/welcome-tour`
- Hard dependencies: `capell-app/admin`
- Third-party dependency: `jibaymcs/filament-tour`
- Database impact: package settings migration for the `welcome-tour` settings group, plus `welcome_tour_user_states` for hosts without `users.dismissed_hints`
- Public frontend impact: none

In the isolated harness, the package installed successfully and replaced the baseline dashboard route with `Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard` at `/admin`.

The verified pass added and captured `Capell\WelcomeTour\Filament\Pages\WelcomeTourSettingsPage` at `/admin/extensions/welcome-tour/settings`. The shared Settings page only renders first-party groups, so Welcome Tour needs its own extension settings page.

## Admin Surfaces

- Dashboard route: `/admin`, rendered by `WelcomeTourDashboard`
- Welcome tour overlay on the dashboard when enabled and visible for the current user
- Restart tour and Remind me later dashboard actions
- Getting-started dashboard checklist widget
- Settings page: `/admin/extensions/welcome-tour/settings`, with enabled toggle and editable step repeater
- User edit form extension: Show welcome tour toggle when per-user tour state can be persisted

## Configuration

The default step configuration lives in `config/capell-welcome-tour.php`. Each step supports:

- `key`
- `title`
- `description`
- `element`
- `icon`
- `icon_color`
- `sort`
- `visible`
- `roles`
- `user_created_within_days`

Use translation keys for `title` and `description` when the text should be site-localized, or literal strings for one-off owner-authored copy. Descriptions are passed to the Filament tour package as plain translated text; do not pre-escape copy in configuration or code-side contributions. The default menu, header-tool, Sites, Pages, and Media steps target admin selectors so the shipped tour demonstrates anchored tooltips across the core admin areas.

`roles` accepts an array or comma-separated list of role names. `user_created_within_days` limits a step to first-run users created within that many days.

## User State

Welcome Tour first honours the host `users.dismissed_hints` column for legacy binary dismissal. When that column is absent, it falls back to `welcome_tour_user_states`, keyed by user morph type/id and tour key.

The package state table stores:

- completed step keys
- last completed step key
- snoozed-until timestamp
- dismissed timestamp

This lets the dashboard resume at the first incomplete step, lets admins snooze the tour until tomorrow, and lets admins restart the tour without requiring host user schema changes.

## Checklist And Events

The `WelcomeTourChecklistWidget` reads `capell-welcome-tour.checklist` and marks tasks complete from setup conditions such as `table-has-rows:sites`. It is registered on the main dashboard.

The package dispatches these Laravel events for analytics integrations:

- `WelcomeTourStarted`
- `WelcomeTourStepCompleted`
- `WelcomeTourSnoozed`
- `WelcomeTourCompleted`
- `WelcomeTourRestarted`

## Screenshot Coverage

See [screenshots.json](screenshots.json) for the screenshot contract. The verified capture covers the dashboard, first-step overlay, extension settings page, and user-level toggle.

## Disposable Harness Notes

- Install only the core Capell stack and `capell-app/welcome-tour` for screenshots.
- Remove `capell-app/login-audit`, `tapp/filament-authentication-log`, and the direct `rappasoft/laravel-authentication-log` dependency from copied baseline harnesses. The authentication-log listener can fire on admin login even when Login Audit is not the target package.
- If the copied harness user model imports `AuthenticationLoggable`, remove that trait in the disposable app after removing the authentication-log dependency.
- Publish and run `2026_05_10_190836_01_add_welcome_tour_settings.php` before capturing settings. The settings page can render only after the `welcome-tour.enabled` and `welcome-tour.steps` settings exist.
- The user edit toggle is expected when either `welcome_tour_user_states` or host `users.dismissed_hints` is available.

## Verification

- `vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml`
- `php artisan route:list | rg 'welcome|settings|extension|Welcome|Settings|Extension'` in the disposable harness
- Browser capture at `/admin`, `/admin/extensions/welcome-tour/settings`, and `/admin/users/1/edit`
