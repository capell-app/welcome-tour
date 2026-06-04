<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions\Users;

use Capell\WelcomeTour\Events\WelcomeTourStepCompleted;
use Capell\WelcomeTour\Support\WelcomeTourSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsObject;

final class RecordWelcomeTourStepAction
{
    use AsObject;

    public function handle(Model $user, string $stepKey, string $tourKey = 'capell_admin_welcome'): void
    {
        if ($stepKey === '' || ! WelcomeTourSchema::hasUserStateTable()) {
            return;
        }

        DB::transaction(function () use ($user, $stepKey, $tourKey): void {
            $now = Carbon::now();
            $existing = DB::table('welcome_tour_user_states')
                ->where('user_type', $user->getMorphClass())
                ->where('user_id', $user->getKey())
                ->where('tour_key', $tourKey)
                ->lockForUpdate()
                ->first();

            $completedStepKeys = json_decode((string) ($existing->completed_step_keys ?? '[]'), true);
            $completedStepKeys = collect(is_array($completedStepKeys) ? $completedStepKeys : [])
                ->filter(fn (mixed $completedStepKey): bool => is_string($completedStepKey) && $completedStepKey !== '')
                ->push($stepKey)
                ->unique()
                ->values()
                ->all();

            DB::table('welcome_tour_user_states')->updateOrInsert(
                [
                    'user_type' => $user->getMorphClass(),
                    'user_id' => $user->getKey(),
                    'tour_key' => $tourKey,
                ],
                [
                    'completed_step_keys' => json_encode($completedStepKeys, JSON_THROW_ON_ERROR),
                    'last_completed_step_key' => $stepKey,
                    'updated_at' => $now,
                    'created_at' => $existing->created_at ?? $now,
                ],
            );
        });

        event(new WelcomeTourStepCompleted($user, $tourKey, $stepKey));
    }
}
