<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Settings;

use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\WelcomeTour\Actions\ResolveWelcomeTourDestinationAction;
use Capell\WelcomeTour\Livewire\WelcomeTourSettingsControls;
use Capell\WelcomeTour\Rules\WelcomeTourSelector;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WelcomeTourSettingsSchema implements HasSchema
{
    /** @return list<Section> */
    public static function make(Schema $schema): array
    {
        return [
            Section::make(__('capell-welcome-tour::welcome_tour.settings_section'))
                ->columnSpanFull()
                ->schema([
                    Toggle::make('enabled')
                        ->label(__('capell-welcome-tour::welcome_tour.enabled'))
                        ->helperText(__('capell-welcome-tour::welcome_tour.enabled_helper')),
                    Livewire::make(WelcomeTourSettingsControls::class)->key('welcome-tour-controls'),
                ]),
            Section::make(__('capell-welcome-tour::welcome_tour.advanced_developer'))
                ->description(__('capell-welcome-tour::welcome_tour.steps_helper'))
                ->collapsed()
                ->columnSpanFull()
                ->schema([
                    Repeater::make('steps')
                        ->label(__('capell-welcome-tour::welcome_tour.steps'))
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->schema([
                            TextInput::make('title')->label(__('capell-welcome-tour::welcome_tour.step_title'))->required(),
                            TextInput::make('description')->label(__('capell-welcome-tour::welcome_tour.step_description'))->required(),
                            Section::make(__('capell-welcome-tour::welcome_tour.destination'))
                                ->schema([
                                    TextInput::make('route')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_route'))
                                        ->helperText(__('capell-welcome-tour::welcome_tour.destination_helper'))
                                        ->default('@dashboard')
                                        ->requiredWithout('resource')
                                        ->rules([self::destinationRule(...)]),
                                    TextInput::make('resource')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_resource'))
                                        ->rules([self::destinationRule(...)]),
                                    TextInput::make('resource_page')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_resource_page'))
                                        ->rules([self::destinationRule(...)]),
                                    TextInput::make('element')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_element'))
                                        ->helperText(__('capell-welcome-tour::welcome_tour.selector_helper'))
                                        ->rules([new WelcomeTourSelector]),
                                    TextInput::make('chapter')->label(__('capell-welcome-tour::welcome_tour.step_chapter')),
                                ]),
                            Section::make(__('capell-welcome-tour::welcome_tour.audience'))
                                ->collapsed()
                                ->schema([
                                    Toggle::make('visible')->label(__('capell-welcome-tour::welcome_tour.step_visible'))->default(true),
                                    TextInput::make('roles')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_roles'))
                                        ->helperText(__('capell-welcome-tour::welcome_tour.step_roles_helper')),
                                    TextInput::make('user_created_within_days')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_user_created_within_days'))
                                        ->integer()->minValue(1),
                                ]),
                            Section::make(__('capell-welcome-tour::welcome_tour.appearance'))
                                ->collapsed()
                                ->schema([
                                    IconPicker::make('icon')->label(__('capell-welcome-tour::welcome_tour.step_icon')),
                                    TextInput::make('icon_color')->label(__('capell-welcome-tour::welcome_tour.step_icon_color')),
                                ]),
                            Section::make(__('capell-welcome-tour::welcome_tour.identity_order'))
                                ->collapsed()
                                ->schema([
                                    TextInput::make('key')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_key'))
                                        ->helperText(__('capell-welcome-tour::welcome_tour.key_helper'))
                                        ->default(fn (): string => 'custom.' . Str::uuid())
                                        ->required()->distinct(),
                                    TextInput::make('sort')
                                        ->label(__('capell-welcome-tour::welcome_tour.step_sort'))
                                        ->helperText(__('capell-welcome-tour::welcome_tour.sort_helper'))
                                        ->integer(),
                                ]),
                        ])
                        ->defaultItems(0)->reorderable()->collapsible()->columnSpanFull(),
                ]),
        ];
    }

    private static function destinationRule(Get $get): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get): void {
            $route = $get('route');
            $resource = $get('resource');
            $page = $get('resource_page');
            if (ResolveWelcomeTourDestinationAction::run(
                is_string($route) ? $route : null,
                is_string($resource) ? $resource : null,
                is_string($page) ? $page : null,
            ) === null) {
                $fail(__('capell-welcome-tour::welcome_tour.invalid_destination'));
            }
        };
    }
}
