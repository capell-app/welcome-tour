# Changelog

All notable changes to `capell-app/welcome-tour` will be documented in this file.

## Unreleased

### 2026-06-04

- Added package-owned `welcome_tour_user_states` persistence for hosts without `users.dismissed_hints`.
- Added per-step progress recording, resume-at-next-step behavior, and a dashboard Restart tour action.
- Added a Remind me later action backed by package-owned snooze state.
- Added a dashboard getting-started checklist widget.
- Added role and first-run targeting for configured steps.
- Added lifecycle events for activation analytics, including snooze.
- Added `WelcomeTourStepContributor` for sibling package step contribution.
- Added contextual page-tour registration, default scoped Sites/Pages/Media steps, and an opt-in Filament trait for resource/page tours beyond the dashboard.
- Allowed configured step copy to be literal text or translation keys.
- Registered configured tour steps at package boot instead of during dashboard render.
- Added anchored default menu and topbar tour steps.
- Removed pre-escaping from configured step descriptions and documented the single plain-text rule.
- Populated manifest actions, capabilities, migration metadata, and marketplace screenshots.

### 2026-06-03

- Rewrote the package and marketplace descriptions around configurable in-product onboarding and per-user welcome flow.
- Replaced the API-version-only health check with diagnostics for the Filament tour plugin, dashboard registration, settings metadata, and per-user dismissal storage.
- Added tests for the health diagnostics and manifest copy expectations.

- Prepared package metadata and documentation for ongoing Capell 0.0.x package work.
