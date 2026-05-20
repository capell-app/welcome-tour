<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Admin\Data\Schemas\UserSchemaContextData;
use Capell\Admin\Enums\UserSchemaHookEnum;
use Capell\Admin\Support\Bridges\AbstractUserResourceBridge;
use Capell\WelcomeTour\Actions\Users\CanShowWelcomeTourAction;
use Capell\WelcomeTour\Actions\Users\SetUserWelcomeTourPreferenceAction;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Override;

final class WelcomeTourUserResourceBridge extends AbstractUserResourceBridge
{
    /**
     * @return array<int, Component>
     */
    #[Override]
    public function extendComponentsForHook(Schema $schema, UserSchemaHookEnum $hook, UserSchemaContextData $context): array
    {
        if ($hook !== UserSchemaHookEnum::AfterIdentity) {
            return [];
        }

        if ($context->operation !== 'edit') {
            return [];
        }

        if (! SchemaFacade::hasTable('users') || ! SchemaFacade::hasColumn('users', 'dismissed_hints')) {
            return [];
        }

        return [
            Toggle::make('welcome_tour_enabled')
                ->label(__('capell-welcome-tour::welcome_tour.user_enabled'))
                ->helperText(__('capell-welcome-tour::welcome_tour.user_enabled_helper'))
                ->default(true)
                ->formatStateUsing(fn (?Model $record): bool => CanShowWelcomeTourAction::run($record))
                ->dehydrated(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    public function mutateDataBeforeCreate(array $data): array
    {
        unset($data['welcome_tour_enabled']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    #[Override]
    public function mutateDataBeforeSave(Model $record, array $data): array
    {
        if (! array_key_exists('welcome_tour_enabled', $data)) {
            return $data;
        }

        SetUserWelcomeTourPreferenceAction::run($record, (bool) $data['welcome_tour_enabled']);

        unset($data['welcome_tour_enabled']);

        return $data;
    }
}
