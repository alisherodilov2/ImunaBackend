<?php

namespace App\Providers;

use App\Services\Api\AuthService;
use App\Services\Api\Contracts\AuthServiceInterface;
use App\Services\Api\V3\AdvertisementsService;
use App\Services\Api\V3\BranchService;
use App\Services\Api\V3\ClientService;
use App\Services\Api\V3\Contracts\AdvertisementsServiceInterface;
use App\Services\Api\V3\Contracts\BranchServiceInterface;
use App\Services\Api\V3\Contracts\ClientServiceInterface;
use App\Services\Api\V3\Contracts\CustomerServiceInterface;
use App\Services\Api\V3\Contracts\DepartmentServiceInterface;
use App\Services\Api\V3\Contracts\DoctorTemplateServiceInterface;
use App\Services\Api\V3\Contracts\ExpenseServiceInterface;
use App\Services\Api\V3\Contracts\ExpenseTypeServiceInterface;
use App\Services\Api\V3\Contracts\GraphServiceInterface;
use App\Services\Api\V3\Contracts\KlinkaServiceInterface;
use App\Services\Api\V3\Contracts\MasterServiceInterface;
use App\Services\Api\V3\Contracts\MaterialExpenseServiceInterface;
use App\Services\Api\V3\Contracts\MedicineTypeServiceInterface;
use App\Services\Api\V3\Contracts\OrderServiceInterface;
use App\Services\Api\V3\Contracts\PatientComplaintServiceInterface;
use App\Services\Api\V3\Contracts\PatientDiagnosisServiceInterface;
use App\Services\Api\V3\Contracts\PenaltyAmountServiceInterface;
use App\Services\Api\V3\Contracts\PharmacyProductServiceInterface;
use App\Services\Api\V3\Contracts\ProductCategoryServiceInterface;
use App\Services\Api\V3\Contracts\ProductOrderBackInterface;
use App\Services\Api\V3\Contracts\ProductOrderServiceInterface;
use App\Services\Api\V3\Contracts\ProductReceptionServiceInterface;
use App\Services\Api\V3\Contracts\ProductServiceInterface;
use App\Services\Api\V3\Contracts\ReferringDoctorServiceInterface;
use App\Services\Api\V3\Contracts\RepotServiceInterface;
use App\Services\Api\V3\Contracts\RoomServiceInterface;
use App\Services\Api\V3\Contracts\ServiceDataServiceInterface;
use App\Services\Api\V3\Contracts\ServicetypeServiceInterface;
use App\Services\Api\V3\Contracts\StatisticaServiceInterface;
use App\Services\Api\V3\Contracts\TemplateCategoryServiceInterface;
use App\Services\Api\V3\Contracts\TemplateServiceInterface;
use App\Services\Api\V3\Contracts\TgBotConnectServiceInterface;
use App\Services\Api\V3\Contracts\TgGroupServiceInterface;
use App\Services\Api\V3\Contracts\TreatmentServiceInterface;
use App\Services\Api\V3\CustomerService;
use App\Services\Api\V3\DepartmentService;
use App\Services\Api\V3\DoctorTemplateService;
use App\Services\Api\V3\ExpenseService;
use App\Services\Api\V3\ExpenseTypeService;
use App\Services\Api\V3\GraphService;
use App\Services\Api\V3\KlinkaService;
use App\Services\Api\V3\MasterService;
use App\Services\Api\V3\MaterialExpenseService;
use App\Services\Api\V3\MedicineTypeService;
use App\Services\Api\V3\OrderService;
use App\Services\Api\V3\PatientComplaintService;
use App\Services\Api\V3\PatientDiagnosisService;
use App\Services\Api\V3\PenaltyAmountService;
use App\Services\Api\V3\PharmacyProductService;
use App\Services\Api\V3\ProductCategoryService;
use App\Services\Api\V3\ProductOrderBackService;
use App\Services\Api\V3\ProductOrderService;
use App\Services\Api\V3\ProductReceptionService;
use App\Services\Api\V3\ProductService;
use App\Services\Api\V3\ReferringDoctorService;
use App\Services\Api\V3\RepotService;
use App\Services\Api\V3\RoomService;
use App\Services\Api\V3\ServiceDataService;
use App\Services\Api\V3\ServicetypeService;
use App\Services\Api\V3\StatisticaService;
use App\Services\Api\V3\TemplateCategoryService;
use App\Services\Api\V3\TemplateService;
use App\Services\Api\V3\TgBotConnectService;
use App\Services\Api\V3\TgGroupService;
use App\Services\Api\V3\TreatmentService;
use Carbon\Laravel\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

class ManualV3ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AuthServiceInterface::class, AuthService::class);
        $this->app->singleton(KlinkaServiceInterface::class, KlinkaService::class);
        $this->app->singleton(ServiceDataServiceInterface::class, ServiceDataService::class);
        $this->app->singleton(DepartmentServiceInterface::class, DepartmentService::class);
        $this->app->singleton(ServicetypeServiceInterface::class, ServicetypeService::class);
        $this->app->singleton(ClientServiceInterface::class, ClientService::class);
        $this->app->singleton(GraphServiceInterface::class, GraphService::class);
        $this->app->singleton(TemplateCategoryServiceInterface::class, TemplateCategoryService::class);
        $this->app->singleton(TemplateServiceInterface::class, TemplateService::class);
        $this->app->singleton(TreatmentServiceInterface::class, TreatmentService::class);
        $this->app->singleton(StatisticaServiceInterface::class, StatisticaService::class);
        $this->app->singleton(ReferringDoctorServiceInterface::class, ReferringDoctorService::class);
        $this->app->singleton(RepotServiceInterface::class, RepotService::class);
        $this->app->singleton(ExpenseTypeServiceInterface::class, ExpenseTypeService::class);
        $this->app->singleton(ExpenseServiceInterface::class, ExpenseService::class);
        $this->app->singleton(ProductCategoryServiceInterface::class, ProductCategoryService::class);
        $this->app->singleton(ProductServiceInterface::class, ProductService::class);
        $this->app->singleton(ProductReceptionServiceInterface::class, ProductReceptionService::class);
        $this->app->singleton(MaterialExpenseServiceInterface::class, MaterialExpenseService::class);
        $this->app->singleton(AdvertisementsServiceInterface::class, AdvertisementsService::class);
        $this->app->singleton(DoctorTemplateServiceInterface::class, DoctorTemplateService::class);
        $this->app->singleton(RoomServiceInterface::class, RoomService::class);
        $this->app->singleton(PharmacyProductServiceInterface::class, PharmacyProductService::class);
        $this->app->singleton(BranchServiceInterface::class, BranchService::class);
        $this->app->singleton(ProductOrderServiceInterface::class, ProductOrderService::class);
        $this->app->singleton(ProductOrderBackInterface::class, ProductOrderBackService::class);
        $this->app->singleton(PatientComplaintServiceInterface::class, PatientComplaintService::class);
        $this->app->singleton(PatientDiagnosisServiceInterface::class, PatientDiagnosisService::class);
        $this->app->singleton(MedicineTypeServiceInterface::class, MedicineTypeService::class);


        $this->app->singleton(TgGroupServiceInterface::class, TgGroupService::class);
        $this->app->singleton(MasterServiceInterface::class, MasterService::class);
        $this->app->singleton(OrderServiceInterface::class, OrderService::class);
        $this->app->singleton(TgBotConnectServiceInterface::class, TgBotConnectService::class);
        $this->app->singleton(CustomerServiceInterface::class, CustomerService::class);
        $this->app->singleton(PenaltyAmountServiceInterface::class, PenaltyAmountService::class);
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
