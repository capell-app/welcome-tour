<?php

declare(strict_types=1);

use Capell\Admin\Filament\Resources\Pages\PageResource;
use Capell\Tests\Fixtures\Models\User;
use Capell\WelcomeTour\Actions\BuildWelcomeTourChecklistAction;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourDestinationAction;
use Capell\WelcomeTour\Contracts\WelcomeTourReadinessResolver;
use Capell\WelcomeTour\Data\WelcomeTourReadinessData;
use Capell\WelcomeTour\Enums\WelcomeTourReadinessStatus;
use Capell\WelcomeTour\Filament\Widgets\WelcomeTourChecklistFilamentWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
    Gate::before(static fn (User $user, string $ability): ?bool => match ($ability) {
        'viewAny' => (bool) config('checklist-test.view', true),
        'create' => (bool) config('checklist-test.create', false),
        default => null,
    });
    config()->set('capell-welcome-tour.checklist', [config('capell-welcome-tour.checklist.1')]);
});

it('offers the page list to a viewing editor and keeps direct creation denied', function (): void {
    expect(PageResource::canAccess())->toBeTrue()->and(PageResource::canCreate())->toBeFalse();
    $items = BuildWelcomeTourChecklistAction::run(auth()->user());
    expect($items)->toHaveCount(1)
        ->and($items[0]->url)->toBe(PageResource::getUrl('index', isAbsolute: false, panel: 'admin'))
        ->and($items[0]->label)->toBe(__('capell-welcome-tour::welcome_tour.checklist_manage_pages'))
        ->and($items[0]->description)->toBe(__('capell-welcome-tour::welcome_tour.checklist_manage_pages_description'));
    $widget = new WelcomeTourChecklistFilamentWidget;
    expect($widget->items()[0]->url)->toBe($items[0]->url);
    $index = PageResource::getPages()['index']->getPage();
    Livewire::test($index)->assertSuccessful();
    $page = PageResource::getPages()['create']->getPage();
    Livewire::test($page)->assertForbidden();
});

it('checks create permission for configured and resolver urls as well as resource destinations', function (): void {
    $create = PageResource::getUrl('create', isAbsolute: false, panel: 'admin');
    expect(ResolveWelcomeTourDestinationAction::run($create))->toBeNull();
});

it('retains the create task for authorised creators', function (): void {
    config()->set('checklist-test.create', true);
    $items = BuildWelcomeTourChecklistAction::run(auth()->user());
    expect($items[0]->url)->toBe(PageResource::getUrl('create', isAbsolute: false, panel: 'admin'))
        ->and($items[0]->label)->toBe(__('capell-welcome-tour::welcome_tour.checklist_create_page'));
});

it('omits denied resolver recovery links and resources the editor cannot view', function (): void {
    app()->instance('checklist-permission-resolver', new class implements WelcomeTourReadinessResolver
    {
        #[Override]
        public function resolve(?Model $user = null, array $context = []): WelcomeTourReadinessData
        {
            return new WelcomeTourReadinessData(
                status: WelcomeTourReadinessStatus::ActionRequired,
                explanation: '',
                recoveryUrl: PageResource::getUrl('create', isAbsolute: false, panel: 'admin'),
            );
        }
    });
    config()->set('capell-welcome-tour.checklist.0.resolver', 'checklist-permission-resolver');
    expect(BuildWelcomeTourChecklistAction::run(auth()->user())[0]->url)->toBeNull();
    config()->set('checklist-test.view', false);
    expect(BuildWelcomeTourChecklistAction::run(auth()->user()))->toBe([]);
});
