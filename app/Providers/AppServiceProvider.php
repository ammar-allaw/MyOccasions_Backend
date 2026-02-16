<?php

namespace App\Providers;

use App\Repositories\Service\ServiceRepository;
use App\Repositories\Service\ServiceRepositoryInterface;
use App\Repositories\ServiceProvider\ServiceProviderRepository;
use App\Repositories\ServiceProvider\ServiceProviderRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Services\Service\ServiceServiceInterface;
use App\Services\ServiceProvider\ServiceProviderService;
use App\Services\ServiceProvider\ServiceProviderServiceInterface;
use App\Services\Service\ServiceService;
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ServiceProviderRepositoryInterface::class, ServiceProviderRepository::class);
        $this->app->bind(ServiceProviderServiceInterface::class, ServiceProviderService::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(ServiceServiceInterface::class, ServiceService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
