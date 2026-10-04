# Extension and action examples

<!-- Maintained by scripts/generate-package-readmes.php -->

Use the action functions with records and Data objects supplied by your application.
They pass each argument to the package operation and return its result.

These adapters show container registration. Use the owning package registry when
a contract requires contributor discovery.

Contract adapters wrap an existing implementation. Call their registration function
from your service provider with that implementation; tagged contracts keep their declared tag.
Resolve the backend by its concrete class before registration so the replacement contract
does not resolve itself. Static contract metadata uses one backend class per adapter.

## Contract `Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver`

<!-- example: contract Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

final class WelcomeTourReadinessResolverAdapter implements \Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver
{
    public function __construct(private readonly \Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver $backend) {}

    #[\Override]
    public function resolve(?\Illuminate\Database\Eloquent\Model $user = null, array $context = []): \Capell\WelcomeTour\Data\WelcomeTourReadinessData
    {
        return $this->backend->resolve($user, $context);
    }
}

function registerWelcomeTourReadinessResolverAdapter(\Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver $backend): void
{
    app()->bind(WelcomeTourReadinessResolverAdapter::class, static fn (): WelcomeTourReadinessResolverAdapter => new WelcomeTourReadinessResolverAdapter($backend));
    app()->bind(\Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver::class, WelcomeTourReadinessResolverAdapter::class);
}
```

## Contract `Capell\WelcomeTour\Contracts\WelcomeTourStateStore`

<!-- example: contract Capell\WelcomeTour\Contracts\WelcomeTourStateStore -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

final class WelcomeTourStateStoreAdapter implements \Capell\WelcomeTour\Contracts\WelcomeTourStateStore
{
    public function __construct(private readonly \Capell\WelcomeTour\Contracts\WelcomeTourStateStore $backend) {}

    #[\Override]
    public function dismiss(\Illuminate\Database\Eloquent\Model $user, string $tourKey): void
    {
        $this->backend->dismiss($user, $tourKey);
    }

    #[\Override]
    public function enable(\Illuminate\Database\Eloquent\Model $user, string $tourKey): void
    {
        $this->backend->enable($user, $tourKey);
    }

    #[\Override]
    public function hasAutoStarted(\Illuminate\Database\Eloquent\Model $user, string $tourKey): bool
    {
        return $this->backend->hasAutoStarted($user, $tourKey);
    }

    #[\Override]
    public function markAutoStarted(\Illuminate\Database\Eloquent\Model $user, string $tourKey): void
    {
        $this->backend->markAutoStarted($user, $tourKey);
    }

    #[\Override]
    public function recordStep(\Illuminate\Database\Eloquent\Model $user, string $stepKey, string $tourKey): void
    {
        $this->backend->recordStep($user, $stepKey, $tourKey);
    }

    #[\Override]
    public function reset(\Illuminate\Database\Eloquent\Model $user, string $tourKey): void
    {
        $this->backend->reset($user, $tourKey);
    }

    #[\Override]
    public function restartProgress(\Illuminate\Database\Eloquent\Model $user, string $tourKey): void
    {
        $this->backend->restartProgress($user, $tourKey);
    }

    #[\Override]
    public function setChecklistDismissed(\Illuminate\Database\Eloquent\Model $user, bool $dismissed, string $tourKey): void
    {
        $this->backend->setChecklistDismissed($user, $dismissed, $tourKey);
    }

    #[\Override]
    public function setChecklistItemCompleted(\Illuminate\Database\Eloquent\Model $user, string $itemKey, bool $completed, string $tourKey): void
    {
        $this->backend->setChecklistItemCompleted($user, $itemKey, $completed, $tourKey);
    }

    #[\Override]
    public function snooze(\Illuminate\Database\Eloquent\Model $user, int $hours, string $tourKey): void
    {
        $this->backend->snooze($user, $hours, $tourKey);
    }

    #[\Override]
    public function state(\Illuminate\Database\Eloquent\Model $user, string $tourKey): \Capell\WelcomeTour\Data\WelcomeTourUserStateData
    {
        return $this->backend->state($user, $tourKey);
    }
}

function registerWelcomeTourStateStoreAdapter(\Capell\WelcomeTour\Contracts\WelcomeTourStateStore $backend): void
{
    app()->bind(WelcomeTourStateStoreAdapter::class, static fn (): WelcomeTourStateStoreAdapter => new WelcomeTourStateStoreAdapter($backend));
    app()->bind(\Capell\WelcomeTour\Contracts\WelcomeTourStateStore::class, WelcomeTourStateStoreAdapter::class);
}
```

## Action `buildWelcomeTourChecklist`

<!-- example: action buildWelcomeTourChecklist -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runBuildWelcomeTourChecklist(?\Illuminate\Database\Eloquent\Model $user = null): array
{
    return \Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction::run($user);
}
```

## Action `canShowWelcomeTour`

<!-- example: action canShowWelcomeTour -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runCanShowWelcomeTour(?\Illuminate\Database\Eloquent\Model $user, string $tourKey = 'capell_admin_welcome'): bool
{
    return \Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction::run($user, $tourKey);
}
```

## Action `canShowWelcomeTourStep`

<!-- example: action canShowWelcomeTourStep -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runCanShowWelcomeTourStep(array $step, ?\Illuminate\Database\Eloquent\Model $user = null): bool
{
    return \Capell\WelcomeTour\Actions\CanShowWelcomeTourStepAction::run($step, $user);
}
```

## Action `getUserWelcomeTourState`

<!-- example: action getUserWelcomeTourState -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runGetUserWelcomeTourState(\Illuminate\Database\Eloquent\Model $user, string $tourKey = 'capell_admin_welcome'): \Capell\WelcomeTour\Data\WelcomeTourUserStateData
{
    return \Capell\WelcomeTour\Actions\Users\GetUserWelcomeTourStateAction::run($user, $tourKey);
}
```

## Action `recordWelcomeTourStep`

<!-- example: action recordWelcomeTourStep -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runRecordWelcomeTourStep(\Illuminate\Database\Eloquent\Model $user, string $stepKey, string $tourKey = 'capell_admin_welcome'): void
{
    \Capell\WelcomeTour\Actions\Users\RecordWelcomeTourStepAction::run($user, $stepKey, $tourKey);
}
```

## Action `resetUserWelcomeTour`

<!-- example: action resetUserWelcomeTour -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runResetUserWelcomeTour(\Illuminate\Database\Eloquent\Model $user, string $tourKey = 'capell_admin_welcome'): void
{
    \Capell\WelcomeTour\Actions\Users\ResetUserWelcomeTourAction::run($user, $tourKey);
}
```

## Action `resolveWelcomeTourEnabled`

<!-- example: action resolveWelcomeTourEnabled -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runResolveWelcomeTourEnabled(): bool
{
    return \Capell\WelcomeTour\Actions\ResolveWelcomeTourEnabledAction::run();
}
```

## Action `resolveWelcomeTourStepsForUser`

<!-- example: action resolveWelcomeTourStepsForUser -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runResolveWelcomeTourStepsForUser(\Illuminate\Database\Eloquent\Model $user, array $steps, string $tourKey = 'capell_admin_welcome'): array
{
    return \Capell\WelcomeTour\Actions\Users\ResolveWelcomeTourStepsForUserAction::run($user, $steps, $tourKey);
}
```

## Action `setUserWelcomeTourPreference`

<!-- example: action setUserWelcomeTourPreference -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runSetUserWelcomeTourPreference(\Illuminate\Database\Eloquent\Model $user, bool $enabled, string $tourKey = 'capell_admin_welcome'): void
{
    \Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction::run($user, $enabled, $tourKey);
}
```

## Action `snoozeUserWelcomeTour`

<!-- example: action snoozeUserWelcomeTour -->

```php
<?php
declare(strict_types=1);

namespace App\CapellExamples\WelcomeTour;

function runSnoozeUserWelcomeTour(\Illuminate\Database\Eloquent\Model $user, int $hours = 24, string $tourKey = 'capell_admin_welcome'): void
{
    \Capell\WelcomeTour\Actions\Users\SnoozeUserWelcomeTourAction::run($user, $hours, $tourKey);
}
```
