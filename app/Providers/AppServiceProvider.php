<?php

namespace App\Providers;

use App\Support\LocalNetworkHost;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('local') && ! $this->app->runningInConsole()) {
            $this->configureLocalNetworkAccess();
        }

        \Illuminate\Support\Facades\View::composer('layouts.app', function (\Illuminate\View\View $view): void {
            $user = auth()->user();
            if ($user === null || $user->isAuditor()) {
                $view->with('notificationBellEnabled', false);
                $view->with('unreadNotificationCount', 0);

                return;
            }

            $view->with('notificationBellEnabled', true);
            $view->with('unreadNotificationCount', $user->unreadNotifications()->count());
        });
    }

    private function configureLocalNetworkAccess(): void
    {
        $request = $this->app->make('request');

        if (! $request->hasHeader('Host')) {
            return;
        }

        $host = $request->getHost();
        $root = $request->getSchemeAndHttpHost();

        if ($root !== '') {
            URL::forceRootUrl($root);
        }

        if (! LocalNetworkHost::isFlexibleLocalHost($host)) {
            return;
        }

        config([
            'sanctum.stateful' => array_values(array_unique(array_merge(
                config('sanctum.stateful', []),
                LocalNetworkHost::sanctumHostVariants($request)
            ))),
        ]);
    }
}
