<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\Admin\Support\AdminPanelEntrypoint;
use Capell\WelcomeTour\Actions\Users\StartWelcomeTourForCurrentUserAction;
use Capell\WelcomeTour\Support\WelcomeTourStepRegistrar;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class PrepareWelcomeTourScreenshotAction
{
    public function handle(string $state): string
    {
        $screenshotApplication = getenv('CAPELL_SCREENSHOT_APP_PATH');
        abort_unless(app()->environment(['local', 'testing'])
            && getenv('CAPELL_SCREENSHOT_FIXTURE') === 'record-state'
            && is_string($screenshotApplication)
            && realpath($screenshotApplication) === realpath(base_path()), 403);
        abort_unless(in_array($state, ['dashboard', 'overlay', 'settings', 'user-toggle'], true), 404);
        $user = auth()->user();
        abort_unless($user instanceof Model, 403);

        session()->forget(['capell_welcome_tour.active', 'capell_welcome_tour.preview_user', 'capell_welcome_tour.preview_state']);
        session()->put('capell_welcome_tour.show_checklist', true);
        $admin = '/' . trim(AdminPanelEntrypoint::path(), '/');

        if ($state === 'overlay') {
            resolve(WelcomeTourStepRegistrar::class)->register();

            return StartWelcomeTourForCurrentUserAction::run(preview: true);
        }

        if ($state === 'user-toggle') {
            $key = $user->getRouteKey();
            throw_unless(is_int($key) || is_string($key), RuntimeException::class, 'The screenshot user must have a scalar route key.');

            return $admin . '/users/' . $key . '/edit';
        }

        return $state === 'settings'
            ? $admin . '/extensions?manage=capell-app%2Fwelcome-tour&surface=welcome-tour'
            : $admin;
    }
}
