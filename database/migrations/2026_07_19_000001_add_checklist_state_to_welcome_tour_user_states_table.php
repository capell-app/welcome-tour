<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('welcome_tour_user_states')) {
            return;
        }

        Schema::table('welcome_tour_user_states', function (Blueprint $table): void {
            if (! Schema::hasColumn('welcome_tour_user_states', 'completed_checklist_item_keys')) {
                $table->json('completed_checklist_item_keys')->nullable()->after('completed_step_keys');
            }

            if (! Schema::hasColumn('welcome_tour_user_states', 'checklist_dismissed_at')) {
                $table->timestamp('checklist_dismissed_at')->nullable()->after('dismissed_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('welcome_tour_user_states')) {
            return;
        }

        Schema::table('welcome_tour_user_states', function (Blueprint $table): void {
            $columns = array_values(array_filter(
                ['completed_checklist_item_keys', 'checklist_dismissed_at'],
                fn (string $column): bool => Schema::hasColumn('welcome_tour_user_states', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
