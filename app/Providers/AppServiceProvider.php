<?php

namespace App\Providers;

use App\Events\SendGlobalNotification;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    
        Gate::define('viewTelescope', function (User $user) {
            return in_array($user->email, [
                'admin@gmail.com',
            ]);
            // if($user->role_id == 1){
            //     return true;
            // }
            // return false;
        });
    
        Gate::define('viewPulse', function (User $user) {
            if($user->role_id == 1){
                return true;
            }
            return false;
        });
        
        Queue::before(function (JobProcessing $event) {
            // $event->connectionName
            // $event->job
            // $event->job->payload()
        });

        Queue::after(function (JobProcessed $event) {
            // dd($event->job->resolveName());
            // $event->connectionName
            // $event->job
            // $event->job->payload()
        });
        
    }
}
