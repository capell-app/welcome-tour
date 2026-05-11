<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Filament\Settings;

use Capell\Admin\Filament\Components\Forms\IconPicker;
use Capell\Admin\Filament\Contracts\HasSchema;
use Capell\Admin\Filament\Support\HelperText;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class WelcomeTourSettingsSchema implements HasSchema
{
    public static function make(Schema $schema): array
    {
        return [
            Section::make(__('capell-welcome-tour::welcome_tour.settings_section'))
                ->columnSpanFull()
                ->schema([
                    Grid::make(2)
                        ->schema([
                            HelperText::apply(
                                Toggle::make('enabled')
                                    ->label(__('capell-welcome-tour::welcome_tour.enabled')),
                                'capell-welcome-tour::welcome_tour.enabled_helper',
                            ),
                        ]),
                    HelperText::apply(
                        Repeater::make('steps')
                            ->label(__('capell-welcome-tour::welcome_tour.steps'))
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        TextInput::make('key')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_key'))
                                            ->required(),
                                        TextInput::make('sort')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_sort'))
                                            ->numeric()
                                            ->default(100)
                                            ->required(),
                                        TextInput::make('title')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_title'))
                                            ->required(),
                                        TextInput::make('description')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_description'))
                                            ->required(),
                                        TextInput::make('element')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_element'))
                                            ->nullable(),
                                        IconPicker::make('icon')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_icon'))
                                            ->nullable(),
                                        TextInput::make('icon_color')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_icon_color'))
                                            ->nullable(),
                                        Toggle::make('visible')
                                            ->label(__('capell-welcome-tour::welcome_tour.step_visible'))
                                            ->default(true),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->columnSpanFull(),
                        'capell-welcome-tour::welcome_tour.steps_helper',
                    ),
                ]),
        ];
    }
}
