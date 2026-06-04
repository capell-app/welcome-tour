<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('welcome_tour_user_states')) {
            return;
        }

        Schema::create('welcome_tour_user_states', function (Blueprint $table): void {
            $table->id();
            $table->morphs('user');
            $table->string('tour_key')->default('capell_admin_welcome');
            $table->json('completed_step_keys')->nullable();
            $table->string('last_completed_step_key')->nullable();
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_type', 'user_id', 'tour_key'], 'welcome_tour_user_states_unique');
            $table->index(['tour_key', 'snoozed_until'], 'welcome_tour_user_states_tour_snoozed_index');
            $table->index(['tour_key', 'dismissed_at'], 'welcome_tour_user_states_tour_dismissed_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('welcome_tour_user_states');
    }
};
