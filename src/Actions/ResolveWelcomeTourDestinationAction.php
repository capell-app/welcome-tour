<?php

declare(strict_types=1);

namespace Capell\WelcomeTour\Actions;

use Capell\Admin\Support\AdminPanelEntrypoint;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Lorisleiva\Actions\Concerns\AsFake;
use Lorisleiva\Actions\Concerns\AsObject;
use Throwable;

final class ResolveWelcomeTourDestinationAction
{
    use AsFake;
    use AsObject;

    /**
     * @param  class-string|null  $surface
     */
    public function handle(?string $destination, ?string $surface = null, ?string $page = null): ?string
    {
        $resolvedDestination = is_string($surface) && $surface !== ''
            ? $this->surfaceDestination($surface, $page)
            : $this->configuredDestination($destination);

        if ($resolvedDestination === null || ! $this->routeExists($resolvedDestination)) {
            return null;
        }

        return $resolvedDestination;
    }

    /**
     * @param  class-string|null  $surface
     */
    private function surfaceDestination(?string $surface, ?string $page): ?string
    {
        if (! is_string($surface) || $surface === '' || ! class_exists($surface)) {
            return null;
        }

        try {
            if (is_subclass_of($surface, Resource::class)) {
                return $this->path($surface::getUrl($page ?: 'index', isAbsolute: false, panel: 'admin'));
            }

            if (is_subclass_of($surface, Page::class)) {
                return $this->path($surface::getUrl(isAbsolute: false, panel: 'admin'));
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    private function configuredDestination(?string $destination): ?string
    {
        if ($destination === '@dashboard') {
            $path = trim(AdminPanelEntrypoint::path(), '/');

            return $path === '' ? '/' : '/' . $path;
        }

        return $this->path($destination);
    }

    private function path(?string $destination): ?string
    {
        if (! is_string($destination) || trim($destination) === '') {
            return null;
        }

        if (str_starts_with($destination, '//')
            || str_contains($destination, '\\')
            || preg_match('/[\x00-\x20]/', $destination) === 1
            || ! in_array(parse_url($destination, PHP_URL_SCHEME), [null, 'http', 'https'], true)) {
            return null;
        }

        $host = parse_url($destination, PHP_URL_HOST);
        $appUrl = config('app.url');
        $appHost = is_string($appUrl) ? parse_url($appUrl, PHP_URL_HOST) : null;

        if (is_string($host) && (! is_string($appHost) || strcasecmp($host, $appHost) !== 0)) {
            return null;
        }

        $path = parse_url($destination, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        return '/' . ltrim($path, '/');
    }

    private function routeExists(string $destination): bool
    {
        try {
            $route = resolve(Router::class)->getRoutes()->match(Request::create($destination, 'GET'));

            return ! $route->isFallback;
        } catch (Throwable) {
            return false;
        }
    }
}
