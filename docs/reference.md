# Welcome Tour reference

<!-- prettier-ignore-start -->

Developer reference for `capell-app/welcome-tour`. Start with the [README](../README.md) for what the extension does and how to install it.

## Package Details

Welcome Tour is an **Available**, **Schema-owning** Capell package in the **Capell Essentials** product group. It ships as `capell-app/welcome-tour` and extends these surfaces: admin.

- Status: Available
- Tier: free
- Bundle: foundation
- Composer package: `capell-app/welcome-tour`
- Namespace: `Capell\WelcomeTour`
- Theme key: not applicable

## Technical Shape

### Service providers

- `Capell\WelcomeTour\Providers\MigrationMetadataServiceProvider`
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
- `PrepareWelcomeTourScreenshotAction`
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

<!-- prettier-ignore-end -->
