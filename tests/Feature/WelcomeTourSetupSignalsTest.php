<?php

declare(strict_types=1);

use Capell\Core\Enums\ExtensionStatusEnum;
use Capell\Core\Models\CapellExtension;
use Capell\Core\Models\Site;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Data\WelcomeTourChecklistItemData;

beforeEach(function (): void {
    $this->actingAsAdmin();
});

function shippedChecklistItem(string $key): WelcomeTourChecklistItemData
{
    $item = collect(BuildWelcomeTourChecklistAction::run())->first(fn (WelcomeTourChecklistItemData $candidate): bool => $candidate->key === $key);

    throw_unless($item instanceof WelcomeTourChecklistItemData, RuntimeException::class, "Expected the shipped [{$key}] checklist item.");

    return $item;
}

function recordExtension(string $composerName, string $installedAt): void
{
    CapellExtension::query()->create([
        'composer_name' => $composerName,
        'name' => $composerName,
        'status' => ExtensionStatusEnum::Enabled,
        'installed_at' => $installedAt,
        'is_paid_marketplace_extension' => false,
    ]);
}

it('keeps the site item open on a fresh install even though the installer created a site', function (): void {
    Site::factory()->create();

    expect(shippedChecklistItem('create-site')->complete)->toBeFalse();
});

it('keeps the site item open when a change was made without a signed-in person', function (): void {
    $site = Site::factory()->create();
    auth()->logout();

    $site->update(['name' => 'Renamed by a script']);

    $this->actingAsAdmin();

    expect(shippedChecklistItem('create-site')->complete)->toBeFalse();
});

it('completes the site item once a signed-in person saves the site', function (): void {
    $site = Site::factory()->create();

    $site->update(['name' => 'Hartley Dental']);

    expect(shippedChecklistItem('create-site')->complete)->toBeTrue();
});

it('keeps the extend item open until an extension is added after the installation finished', function (): void {
    recordExtension('capell-app/core', '2026-11-01 10:00:00');
    recordExtension('capell-app/welcome-tour', '2026-11-01 09:59:00');
    recordExtension('vendor/installed-by-a-suite', '2026-11-01 09:59:30');

    expect(shippedChecklistItem('extend')->complete)->toBeFalse();
});

it('does not complete the extend item for a default package added after the installation', function (): void {
    recordExtension('capell-app/core', '2026-11-01 10:00:00');
    recordExtension('capell-app/welcome-tour', '2026-11-02 10:00:00');

    expect(shippedChecklistItem('extend')->complete)->toBeFalse();
});

it('completes the extend item when an extension is enabled after the installation finished', function (): void {
    recordExtension('capell-app/core', '2026-11-01 10:00:00');
    recordExtension('vendor/forms-extension', '2026-11-02 10:00:00');

    expect(shippedChecklistItem('extend')->complete)->toBeTrue();
});

it('keeps the extend item open before the installation has finished', function (): void {
    recordExtension('vendor/forms-extension', '2026-11-02 10:00:00');

    expect(shippedChecklistItem('extend')->complete)->toBeFalse();
});
