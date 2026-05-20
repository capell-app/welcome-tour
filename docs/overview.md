# Welcome Tour Overview

`capell-app/welcome-tour` keeps guided admin onboarding outside the core admin package.

## Responsibilities

- Registers `jibaymcs/filament-tour` only when this package is installed.
- Provides `WelcomeTourDashboard`, a dashboard subclass that uses the Filament tour trait.
- Registers default tour steps from configurable translation keys.
- Adds package settings for enabling the tour and editing the step list.
- Adds a user resource bridge for per-user tour visibility.

## Installation Audit

- Composer package: `capell-app/welcome-tour`
- Hard dependencies: `capell-app/admin`
- Third-party dependency: `jibaymcs/filament-tour`
- Database impact: package settings migration for the `welcome-tour` settings group
- Public frontend impact: none

In the isolated harness, the package installed successfully and replaced the baseline dashboard route with `Capell\WelcomeTour\Filament\Pages\WelcomeTourDashboard` at `/admin`.

The verified pass added and captured `Capell\WelcomeTour\Filament\Pages\WelcomeTourSettingsPage` at `/admin/extensions/welcome-tour/settings`. The shared Settings page only renders first-party groups, so Welcome Tour needs its own extension settings page.

## Admin Surfaces

- Dashboard route: `/admin`, rendered by `WelcomeTourDashboard`
- Welcome tour overlay on the dashboard when enabled and visible for the current user
- Settings page: `/admin/extensions/welcome-tour/settings`, with enabled toggle and editable step repeater
- User edit form extension: Show welcome tour toggle when the host users table has `dismissed_hints`

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

Use translation keys for `title` and `description` when the text should be site-localized. Descriptions are rendered as escaped text, not trusted HTML. Default steps do not target admin selectors; set `element` only after verifying the target remains stable and accessible.

## Screenshot Coverage

See [screenshots.json](screenshots.json) for the screenshot contract. The verified capture covers the dashboard, first-step overlay, extension settings page, and user-level toggle.

## Disposable Harness Notes

- Install only the core Capell stack and `capell-app/welcome-tour` for screenshots.
- Remove `capell-app/login-audit`, `tapp/filament-authentication-log`, and the direct `rappasoft/laravel-authentication-log` dependency from copied baseline harnesses. The authentication-log listener can fire on admin login even when Login Audit is not the target package.
- If the copied harness user model imports `AuthenticationLoggable`, remove that trait in the disposable app after removing the authentication-log dependency.
- Publish and run `2026_05_10_190836_01_add_welcome_tour_settings.php` before capturing settings. The settings page can render only after the `welcome-tour.enabled` and `welcome-tour.steps` settings exist.
- The user edit toggle is expected when the host users table has `dismissed_hints`; otherwise that screenshot remains optional for hosts without the bridge column.

## Verification

- `vendor/bin/pest packages/welcome-tour/tests --configuration=phpunit.xml`
- `php artisan route:list | rg 'welcome|settings|extension|Welcome|Settings|Extension'` in the disposable harness
- Browser capture at `/admin`, `/admin/extensions/welcome-tour/settings`, and `/admin/users/1/edit`
