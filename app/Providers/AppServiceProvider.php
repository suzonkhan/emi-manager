<?php

namespace App\Providers;

use App\Repositories\Customer\CustomerRepository;
use App\Repositories\Customer\CustomerRepositoryInterface;
use App\Repositories\Token\TokenRepository;
use App\Repositories\Token\TokenRepositoryInterface;
use App\Repositories\TokenAssignment\TokenAssignmentRepository;
use App\Repositories\TokenAssignment\TokenAssignmentRepositoryInterface;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // User Repository
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(\App\Services\UserService::class);

        // Token Repository
        $this->app->bind(TokenRepositoryInterface::class, TokenRepository::class);

        // Token Assignment Repository
        $this->app->bind(TokenAssignmentRepositoryInterface::class, TokenAssignmentRepository::class);

        // Customer Repository
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
