<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Tests\Fixtures;

use Capell\WelcomeTour\Filament\Settings\WelcomeTourSettingsSchema;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

/** @property-read Schema $form */
final class WelcomeTourSettingsForm extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components(WelcomeTourSettingsSchema::make($schema));
    }

    public function save(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
