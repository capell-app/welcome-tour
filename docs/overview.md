# Welcome Tour Overview

`capell-app/welcome-tour` keeps guided admin onboarding outside the core admin package.

## Responsibilities

- Registers `jibaymcs/filament-tour` only when this package is installed.
- Provides `WelcomeTourDashboard`, a dashboard subclass that uses the Filament tour trait.
- Registers default tour steps from configurable translation keys.
- Adds package settings for enabling the tour and editing the step list.
- Adds a user resource bridge for per-user tour visibility.

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
