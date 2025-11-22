<?php

namespace App\Providers;

use App\Services\Notifications\MockNotificationService;
use App\Services\Notifications\NotificationServiceInterface;
use App\Services\Verification\ConfirmationServiceInterface;
use App\Services\Verification\MockConfirmationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
          $this->app->bind(
        ConfirmationServiceInterface::class,
        MockConfirmationService::class
    );

    $this->app->bind(
        NotificationServiceInterface::class,
        MockNotificationService::class
    );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
