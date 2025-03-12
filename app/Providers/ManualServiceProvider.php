<?php

namespace App\Providers;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use App\Services\Api\AuthService;
use App\Services\Api\Contracts\AuthServiceInterface;
use App\Services\Api\V3\BranchService;
use App\Services\Api\V3\CategoryService;
use App\Services\Api\V3\Contracts\BranchServiceInterface;
use App\Services\Api\V3\Contracts\CategoryServiceInterface;
use App\Services\Api\V3\Contracts\CurrencyServiceInterface;
use App\Services\Api\V3\Contracts\CustomerServiceInterface;
use App\Services\Api\V3\Contracts\InAndOutPaymentServiceInterface;
use App\Services\Api\V3\Contracts\ManagerServiceInterface;
use App\Services\Api\V3\Contracts\ProductServiceInterface;
use App\Services\Api\V3\Contracts\UnityServiceInterface;
use App\Services\Api\V3\CurrencyService;
use App\Services\Api\V3\CustomerService;
use App\Services\Api\V3\InAndOutPaymentService;
use App\Services\Api\V3\ManagerService;
use App\Services\Api\V3\ProductService;
use App\Services\Api\V3\UnityService;
use Carbon\Laravel\ServiceProvider;

class ManualServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(ManagerServiceInterface::class, ManagerService::class);
        $this->app->singleton(UnityServiceInterface::class, UnityService::class);
        $this->app->singleton(BranchServiceInterface::class, BranchService::class);
        $this->app->singleton(CurrencyServiceInterface::class, CurrencyService::class);
        $this->app->singleton(CategoryServiceInterface::class, CategoryService::class);
        $this->app->singleton(ProductServiceInterface::class, ProductService::class);
        $this->app->singleton(CustomerServiceInterface::class, CustomerService::class);
        $this->app->singleton(InAndOutPaymentServiceInterface::class, InAndOutPaymentService::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
        Paginator::useBootstrap();
    }
}
