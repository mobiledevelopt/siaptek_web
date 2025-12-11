<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Telescope::night();

        if ($this->app->isProduction()) {
            Telescope::night();  // Disable data collection except when needed
        }
        
        $this->hideSensitiveRequestDetails();
        if($this->app->isLocal() || $this->app->isProduction()){
            Telescope::filter(function (IncomingEntry $entry) {
                // Check if it's a request and matches a specific route
                
                if ($entry->type === 'request' && $entry->content['method'] === 'POST') {
                    $requestUri = $entry->content['uri'];
    
                    // Change '/your-specific-route' to the route you want to monitor
                    if (strpos($requestUri, '/api/checkin') !== false || strpos($requestUri, '/checkout') !== false || strpos($requestUri, '/daftar_hadir_apel') !== false) {
                        return true;
                    }
                }
    
                return false;
                // return $this->app->isLocal() || $this->app->isProduction();
            });
        }
        
        
        // $this->hideSensitiveRequestDetails();

        // $isLocal = $this->app->environment('local');

        // Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
        //     return $isLocal ||
        //           $entry->isReportableException() ||
        //           $entry->isFailedRequest() ||
        //           $entry->isFailedJob() ||
        //           $entry->isScheduledTask() ||
        //           $entry->hasMonitoredTag();
        // });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }
        if (app()->isProduction()) {
            Telescope::hideRequestParameters(['_token', 'password', 'password_confirmation']);
        }
        
        // Telescope::hideRequestParameters(['_token']);

        // Telescope::hideRequestHeaders([
        //     'cookie',
        //     'x-csrf-token',
        //     'x-xsrf-token',
        // ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            return in_array($user->email, [
                'admin@gmail.com',
            ]);
        });
    }
}
