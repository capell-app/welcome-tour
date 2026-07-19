<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Support;

use Capell\Core\Support\Database\RuntimeSchemaState;

final class WelcomeTourSchema
{
    public static function hasDismissedHintsColumn(string $table = 'users'): bool
    {
        return self::hasTable($table) && self::hasColumn($table, 'dismissed_hints');
    }

    public static function hasUserStateTable(): bool
    {
        return self::hasTable('welcome_tour_user_states');
    }

    public static function hasChecklistStateColumns(): bool
    {
        return self::hasUserStateTable()
            && self::hasColumn('welcome_tour_user_states', 'completed_checklist_item_keys')
            && self::hasColumn('welcome_tour_user_states', 'checklist_dismissed_at');
    }

    public static function hasTable(string $table): bool
    {
        return resolve(RuntimeSchemaState::class)->hasTable($table);
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return resolve(RuntimeSchemaState::class)->hasColumn($table, $column);
    }
}
