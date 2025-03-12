<?php

use App\Http\Controllers\Api\V3\AdvertisementsController;
use App\Http\Controllers\Api\V3\BranchController;
use App\Http\Controllers\Api\V3\ClientController;
use App\Http\Controllers\Api\V3\CustomerController;
use App\Http\Controllers\Api\V3\DepartmentController;
use App\Http\Controllers\Api\V3\DoctorTemplateController;
use App\Http\Controllers\Api\V3\ExpenseController;
use App\Http\Controllers\Api\V3\ExpenseTypeController;
use App\Http\Controllers\Api\V3\GraphController;
use App\Http\Controllers\Api\V3\KlinkaController;
use App\Http\Controllers\Api\V3\MasterController;
use App\Http\Controllers\Api\V3\MaterialExpenseController;
use App\Http\Controllers\Api\V3\MedicineTypeController;
use App\Http\Controllers\Api\V3\OrderController;
use App\Http\Controllers\Api\V3\PatientComplaintController;
use App\Http\Controllers\Api\V3\PatientDiagnosisController;
use App\Http\Controllers\Api\V3\PenaltyAmountController;
use App\Http\Controllers\Api\V3\PharmacyProductController;
use App\Http\Controllers\Api\V3\ProductCategoryController;
use App\Http\Controllers\Api\V3\ProductController;
use App\Http\Controllers\Api\V3\ProductOrderBackController;
use App\Http\Controllers\Api\V3\ProductOrderController;
use App\Http\Controllers\Api\V3\ProductReceptionController;
use App\Http\Controllers\Api\V3\ReferringDoctorController;
use App\Http\Controllers\Api\V3\RepotController;
use App\Http\Controllers\Api\V3\RoomController;
use App\Http\Controllers\Api\V3\ServiceDataController;
use App\Http\Controllers\Api\V3\ServicetypeController;
use App\Http\Controllers\Api\V3\StatisticaController;
use App\Http\Controllers\Api\V3\TemplateCategoryController;
use App\Http\Controllers\Api\V3\TemplateController;
use App\Http\Controllers\Api\V3\TgBotConnectController;
use App\Http\Controllers\Api\V3\TgGroupController;
use App\Http\Controllers\Api\V3\TreatmentController;
use App\Http\Controllers\Api\V3\UserController;
use App\Http\Controllers\auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/certificate-download', [ClientController::class, 'certificateDownload']);
Route::get('/file', [AuthController::class, 'file']);
Route::post('/supper-admin-login', [AuthController::class, 'supperAdminlogin']);
Route::post('/file-upload', [ReferringDoctorController::class, 'fileAdd']);
Route::group(['middleware' => 'auth:sanctum'], function () {
    Route::group(['prefix' => 'klinka'], function () {
        Route::get('', [KlinkaController::class, 'index']);
        Route::post('', [KlinkaController::class, 'store']);
        Route::post('/excel', [KlinkaController::class, 'storeExcel']);
        Route::get('/{id}', [KlinkaController::class, 'showResource']);
        Route::post('/{id}', [KlinkaController::class, 'update']);
        Route::delete('/{id}', [KlinkaController::class, 'delete']);
    });
    Route::post('/director-setting', [KlinkaController::class, 'directorSetting']);
    Route::group(['prefix' => 'service'], function () {
        Route::get('', [ServiceDataController::class, 'index']);
        Route::get('/template/{id}', [ServiceDataController::class, 'showLaboratoryTemplate']);
        Route::post('/template/{id}', [ServiceDataController::class, 'laboratoryTemplate']);
        Route::post('', [ServiceDataController::class, 'store']);
        Route::post('/excel', [ServiceDataController::class, 'storeExcel']);
        Route::get('/{id}', [ServiceDataController::class, 'show']);
        Route::put('/{id}', [ServiceDataController::class, 'update']);
        Route::delete('/{id}', [ServiceDataController::class, 'delete']);
    });
    Route::group(['prefix' => 'statistica'], function () {
        Route::get('', [StatisticaController::class, 'index']);
        Route::get('/home', [StatisticaController::class, 'statisticaHome']);
        Route::get('/counterparty', [StatisticaController::class, 'statisticaCounterparty']);
    });
    Route::get('/doctor', [klinkaController::class, 'doctor']);
    Route::group(['prefix' => 'repot'], function () {
        Route::get('', [RepotController::class, 'index']);
        Route::get('/excel', [RepotController::class, 'excelRepot']);
        Route::get('/counterparty', [RepotController::class, 'counterparty']);
        Route::get('/doctor', [RepotController::class, 'doctor']);
        Route::get('/doctor/{id}', [RepotController::class, 'doctorShow']);
        Route::get('/doctor-service/{id}', [RepotController::class, 'doctorShowService']);
        Route::get('/daily', [RepotController::class, 'dailyRepot']);
        Route::get('/daily/{id}', [RepotController::class, 'dailyRepotShow']);
        Route::post('/daily', [RepotController::class, 'dailyRepotUpdate']);
        Route::get('/counterparty/{id}', [RepotController::class, 'counterpartyShow']);
        Route::get('/counterparty-client/{id}', [RepotController::class, 'counterpartyClientShow']);
    });
    Route::group(['prefix' => 'client'], function () {
        Route::get('', [ClientController::class, 'index']);
        Route::get('/laboratory/sms-send/{id}', [ClientController::class, 'smsSend']);
        Route::get('/alert-soket/{id}', [ClientController::class, 'alertSoket']);
        Route::get('/laboratory', [ClientController::class, 'laboratoryClient']);
        Route::get('/laboratory/table', [ClientController::class, 'laboratoryTable']);
        Route::delete('/laboratory/file-delete/{id}', [ClientController::class, 'fileDelete']);
        Route::post('/laboratory/file/{id}', [ClientController::class, 'laboratoryTemplateResultFiles']);
        Route::post('/laboratory/file/update/{id}', [ClientController::class, 'laboratoryTemplateResultFilesUpdate']);
        Route::post('/laboratory/save/{id}', [ClientController::class, 'laboratoryClientSave']);
        Route::post('/laboratory-table/save', [ClientController::class, 'laboratoryTableSave']);
        Route::get('/laboratory/{id}', [ClientController::class, 'laboratoryClientShow']);
        Route::get('/bloodtest', [ClientController::class, 'bloodtest']);
        Route::get('/bloodtest/accept/{id}', [ClientController::class, 'bloodtestAccept']);
        Route::post('/certificate', [ClientController::class, 'certificate']);
        Route::get('/service-print', [ClientController::class, 'servicePrintChek']);
        Route::get('/reception', [ClientController::class, 'receptionFilter']);
        Route::get('/statsianar', [ClientController::class, 'statsianar']);
        Route::get('/doctor-statsianar', [ClientController::class, 'doctorStatsianar']);
        Route::get('/doctor-client-all', [ClientController::class, 'doctorClientAll']);
        Route::post('/statsianar-finish/{id}', [ClientController::class, 'statsionarFinish']);
        Route::get('/all', [ClientController::class, 'clientAllData']);
        Route::get('/counterparty-all-client', [ClientController::class, 'counterpartyAllClient']);
        Route::get('/doctor-room', [ClientController::class, 'doctorRoom']);
        Route::get('/autocomplate', [ClientController::class, 'autocomplate']);
        Route::post('/doctor-result/{id}', [ClientController::class, 'doctorResult']);
        Route::post('/', [ClientController::class, 'register']);
        Route::post('/excel', [ClientController::class, 'storeExcel']);
        Route::get('/{id}', [ClientController::class, 'showResource']);
        Route::put('/{id}', [ClientController::class, 'update']);
        Route::delete('/{id}', [ClientController::class, 'delete']);
        Route::delete('/director-delete/{id}', [ClientController::class, 'dierktorDelete']);
    });
    Route::group(['prefix' => 'graph'], function () {
        Route::get('', [GraphController::class, 'index']);
        Route::get('/shelf-number-limit/{id}', [GraphController::class, 'shelfNumberLimit']);
        Route::get('/treatment-show', [GraphController::class, 'graphArchiveShow']);
        Route::get('/treatment', [GraphController::class, 'treatment']);
        Route::get('/at-home-treatment', [GraphController::class, 'atHomeTreatment']);
        Route::post('/treatment/{id}', [GraphController::class, 'treatmentUpdate']);
        Route::get('/graph-client', [GraphController::class, 'graphClient']);
        Route::post('/item-delete', [GraphController::class, 'graphItemDelete']);
        Route::get('/working-date-check', [GraphController::class, 'workingDateCheck']);
        Route::post('', [GraphController::class, 'store']);
        Route::post('/excel', [GraphController::class, 'storeExcel']);
        Route::get('/{id}', [GraphController::class, 'showResource']);
        Route::put('/{id}', [GraphController::class, 'update']);
        Route::delete('/{id}', [GraphController::class, 'delete']);
    });
    Route::group(['prefix' => 'department'], function () {
        Route::get('', [DepartmentController::class, 'index']);
        Route::get('monitor', [DepartmentController::class, 'monitor']);
        Route::get('/queue-number-limit/{id}', [DepartmentController::class, 'queueNumberLimit']);
        Route::post('', [DepartmentController::class, 'store']);
        Route::post('/excel', [DepartmentController::class, 'storeExcel']);
        Route::get('/{id}', [DepartmentController::class, 'showResource']);
        Route::post('/{id}', [DepartmentController::class, 'update']);
        Route::delete('/{id}', [DepartmentController::class, 'delete']);
    });
    Route::group(['prefix' => 'medicine-type'], function () {
        Route::get('', [MedicineTypeController::class, 'index']);
        Route::post('', [MedicineTypeController::class, 'store']);
        Route::get('/item-all', [MedicineTypeController::class, 'itemAll']);
        Route::post('/item', [MedicineTypeController::class, 'itemStore']);
        Route::post('/item-excel', [MedicineTypeController::class, 'itemStoreExcel']);
        Route::post('/item/{id}', [MedicineTypeController::class, 'itemUpdate']);
        Route::delete('/item/{id}', [MedicineTypeController::class, 'itemDelete']);
        Route::post('/excel', [MedicineTypeController::class, 'storeExcel']);
        Route::get('/{id}', [MedicineTypeController::class, 'showResource']);
        Route::post('/{id}', [MedicineTypeController::class, 'update']);
        Route::delete('/{id}', [MedicineTypeController::class, 'delete']);
    });
    Route::group(['prefix' => 'patient-complaint'], function () {
        Route::get('', [PatientComplaintController::class, 'index']);
        Route::post('', [PatientComplaintController::class, 'store']);
        Route::post('/{id}', [PatientComplaintController::class, 'update']);
        Route::delete('/{id}', [PatientComplaintController::class, 'delete']);
    });
    Route::group(['prefix' => 'patient-diagnosis'], function () {
        Route::get('', [PatientDiagnosisController::class, 'index']);
        Route::post('', [PatientDiagnosisController::class, 'store']);
        Route::post('/{id}', [PatientDiagnosisController::class, 'update']);
        Route::delete('/{id}', [PatientDiagnosisController::class, 'delete']);
    });
    Route::group(['prefix' => 'referring-doctor'], function () {
        Route::get('/service/{id}', [ReferringDoctorController::class, 'serviceShow']);
        Route::post('/service/{id}', [ReferringDoctorController::class, 'serviceUpdate']);
        Route::get('/show/{id}', [ReferringDoctorController::class, 'show']);
        Route::get('/change-archive', [ReferringDoctorController::class, 'showReferringDoctorChangeArchive']);
        Route::get('/treatment', [ReferringDoctorController::class, 'treatment']);
        Route::get('/item-pay-show', [ReferringDoctorController::class, 'doctorPayShow']);
        Route::post('/item-pay/{id}', [ReferringDoctorController::class, 'doctorPay']);
        Route::post('/pay', [ReferringDoctorController::class, 'referringDoctorPay']);
        Route::get('/balance', [ReferringDoctorController::class, 'referringDoctorBalance']);
        Route::get('', [ReferringDoctorController::class, 'index']);
        Route::post('', [ReferringDoctorController::class, 'store']);
        Route::post('/excel', [ReferringDoctorController::class, 'storeExcel']);
        Route::get('/{id}', [ReferringDoctorController::class, 'showResource']);
        Route::put('/{id}', [ReferringDoctorController::class, 'update']);
        Route::delete('/{id}', [ReferringDoctorController::class, 'delete']);
    });
    Route::group(['prefix' => 'service-type'], function () {
        Route::get('', [ServicetypeController::class, 'index']);
        Route::post('', [ServicetypeController::class, 'store']);
        Route::post('/excel', [ServicetypeController::class, 'storeExcel']);
        Route::get('/{id}', [ServicetypeController::class, 'showResource']);
        Route::put('/{id}', [ServicetypeController::class, 'update']);
        Route::delete('/{id}', [ServicetypeController::class, 'delete']);
    });
    Route::group(['prefix' => 'users'], function () {
        Route::get('', [UserController::class, 'index']);
        Route::post('', [UserController::class, 'store']);
        Route::post('/excel', [UserController::class, 'storeExcel']);
        Route::get('/{id}', [UserController::class, 'showResource']);
        Route::post('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'delete']);
    });
    Route::group(['prefix' => 'template-category'], function () {
        Route::get('', [TemplateCategoryController::class, 'index']);
        Route::post('', [TemplateCategoryController::class, 'store']);
        Route::post('/excel', [TemplateCategoryController::class, 'storeExcel']);
        Route::get('/{id}', [TemplateCategoryController::class, 'showResource']);
        Route::put('/{id}', [TemplateCategoryController::class, 'update']);
        Route::delete('/{id}', [TemplateCategoryController::class, 'delete']);
    });
    Route::group(['prefix' => 'product'], function () {
        Route::get('', [ProductController::class, 'index']);
        Route::get('/repot', [ProductController::class, 'repot']);
        Route::get('/repot-storage-order', [ProductController::class, 'reportProductAmbulatorAndTreatment']);
        Route::get('/repot/show', [ProductController::class, 'repotShow']);
        Route::post('', [ProductController::class, 'store']);
        Route::post('/excel', [ProductController::class, 'storeExcel']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'delete']);
    });
    Route::group(['prefix' => 'product-order'], function () {
        Route::get('', [ProductOrderController::class, 'index']);
        Route::get('/auto-fill/{id}', [ProductOrderController::class, 'autofill']);
        Route::post('/send-deliver/{id}', [ProductOrderController::class, 'sendDeliver']);
        Route::get('/repot', [ProductOrderController::class, 'repot']);
        Route::get('/repot/show', [ProductOrderController::class, 'repotShow']);
        Route::post('', [ProductOrderController::class, 'store']);
        Route::post('/excel', [ProductOrderController::class, 'storeExcel']);
        Route::get('/{id}', [ProductOrderController::class, 'show']);
        Route::post('/{id}', [ProductOrderController::class, 'update']);
        Route::delete('/{id}', [ProductOrderController::class, 'delete']);
    });
    Route::group(['prefix' => 'product-category'], function () {
        Route::get('', [ProductCategoryController::class, 'index']);
        Route::post('', [ProductCategoryController::class, 'store']);
        Route::post('/excel', [ProductCategoryController::class, 'storeExcel']);
        Route::get('/{id}', [ProductCategoryController::class, 'showResource']);
        Route::put('/{id}', [ProductCategoryController::class, 'update']);
        Route::delete('/{id}', [ProductCategoryController::class, 'delete']);
    });
    Route::group(['prefix' => 'product-reception'], function () {
        Route::get('', [ProductReceptionController::class, 'index']);
        Route::post('', [ProductReceptionController::class, 'store']);
        Route::post('/excel', [ProductReceptionController::class, 'storeExcel']);
        Route::delete('/item/{id}/{parntId}', [ProductReceptionController::class, 'itemDelete']);
        Route::get('/{id}', [ProductReceptionController::class, 'show']);
        Route::put('/{id}', [ProductReceptionController::class, 'update']);
        Route::delete('/{id}', [ProductReceptionController::class, 'delete']);
    });
    Route::group(['prefix' => 'pharmacy-product'], function () {
        Route::get('', [PharmacyProductController::class, 'index']);
        Route::get('/qr-code-scan', [PharmacyProductController::class, 'qrCodeScan']);
        Route::post('', [PharmacyProductController::class, 'store']);
        Route::post('/excel', [PharmacyProductController::class, 'storeExcel']);
        Route::get('/{id}', [PharmacyProductController::class, 'show']);
        Route::put('/{id}', [PharmacyProductController::class, 'update']);
        Route::delete('/{id}', [PharmacyProductController::class, 'delete']);
    });
    Route::group(['prefix' => 'treatment'], function () {
        Route::get('', [TreatmentController::class, 'index']);
        Route::post('', [TreatmentController::class, 'store']);
        Route::post('/excel', [TreatmentController::class, 'storeExcel']);
        Route::get('/{id}', [TreatmentController::class, 'showResource']);
        Route::post('/{id}', [TreatmentController::class, 'update']);
        Route::delete('/{id}', [TreatmentController::class, 'delete']);
    });
    Route::group(['prefix' => 'product-order-back'], function () {
        Route::get('', [ProductOrderBackController::class, 'index']);
        Route::post('', [ProductOrderBackController::class, 'store']);
        Route::post('/excel', [ProductOrderBackController::class, 'storeExcel']);
        Route::get('/{id}', [ProductOrderBackController::class, 'showResource']);
        Route::post('/{id}', [ProductOrderBackController::class, 'update']);
        Route::delete('/{id}', [ProductOrderBackController::class, 'delete']);
    });
    Route::group(['prefix' => 'branch'], function () {
        Route::get('', [BranchController::class, 'index']);
        Route::get('/remaining-branches', [BranchController::class, 'remainingBranches']);
        Route::post('', [BranchController::class, 'store']);
        Route::post('/excel', [BranchController::class, 'storeExcel']);
        Route::get('/{id}', [BranchController::class, 'showResource']);
        Route::post('/{id}', [BranchController::class, 'update']);
        Route::delete('/{id}', [BranchController::class, 'delete']);
    });
    Route::group(['prefix' => 'room'], function () {
        Route::get('', [RoomController::class, 'index']);
        Route::get('/empty', [RoomController::class, 'emptyRoom']);
        Route::post('', [RoomController::class, 'store']);
        Route::post('/excel', [RoomController::class, 'storeExcel']);
        Route::get('/{id}', [RoomController::class, 'showResource']);
        Route::post('/{id}', [RoomController::class, 'update']);
        Route::delete('/{id}', [RoomController::class, 'delete']);
    });
    Route::group(['prefix' => 'doctor-template'], function () {
        Route::get('', [DoctorTemplateController::class, 'index']);
        Route::post('', [DoctorTemplateController::class, 'store']);
        Route::post('/excel', [DoctorTemplateController::class, 'storeExcel']);
        Route::get('/{id}', [DoctorTemplateController::class, 'showResource']);
        Route::post('/{id}', [DoctorTemplateController::class, 'update']);
        Route::delete('/{id}', [DoctorTemplateController::class, 'delete']);
    });
    Route::group(['prefix' => 'advertisements'], function () {
        Route::get('', [AdvertisementsController::class, 'index']);
        Route::post('', [AdvertisementsController::class, 'store']);
        Route::post('/excel', [AdvertisementsController::class, 'storeExcel']);
        Route::get('/{id}', [AdvertisementsController::class, 'showResource']);
        Route::put('/{id}', [AdvertisementsController::class, 'update']);
        Route::delete('/{id}', [AdvertisementsController::class, 'delete']);
    });
    Route::group(['prefix' => 'material-expense'], function () {
        Route::get('', [MaterialExpenseController::class, 'index']);
        Route::get('/repot', [MaterialExpenseController::class, 'repot']);
        Route::get('/repot/show', [MaterialExpenseController::class, 'repotShow']);
        Route::post('', [MaterialExpenseController::class, 'store']);
        Route::post('/excel', [MaterialExpenseController::class, 'storeExcel']);
        Route::get('/{id}', [MaterialExpenseController::class, 'showResource']);
        Route::put('/{id}', [MaterialExpenseController::class, 'update']);
        Route::delete('/{id}', [MaterialExpenseController::class, 'delete']);
    });
    Route::group(['prefix' => 'expense'], function () {
        Route::get('', [ExpenseController::class, 'index']);
        Route::get('/repot', [ExpenseController::class, 'repot']);
        Route::get('/repot/show', [ExpenseController::class, 'repotShow']);
        Route::post('', [ExpenseController::class, 'store']);
        Route::post('/excel', [ExpenseController::class, 'storeExcel']);
        Route::get('/{id}', [ExpenseController::class, 'showResource']);
        Route::put('/{id}', [ExpenseController::class, 'update']);
        Route::delete('/{id}', [ExpenseController::class, 'delete']);
    });
    Route::group(['prefix' => 'expense-type'], function () {
        Route::get('', [ExpenseTypeController::class, 'index']);
        Route::post('', [ExpenseTypeController::class, 'store']);
        Route::post('/excel', [ExpenseTypeController::class, 'storeExcel']);
        Route::get('/{id}', [ExpenseTypeController::class, 'showResource']);
        Route::put('/{id}', [ExpenseTypeController::class, 'update']);
        Route::delete('/{id}', [ExpenseTypeController::class, 'delete']);
    });
    Route::group(['prefix' => 'template'], function () {
        Route::get('', [TemplateController::class, 'index']);
        Route::post('', [TemplateController::class, 'store']);
        Route::post('/excel', [TemplateController::class, 'storeExcel']);
        Route::get('/{id}', [TemplateController::class, 'showResource']);
        Route::post('/{id}', [TemplateController::class, 'update']);
        Route::delete('/{id}', [TemplateController::class, 'delete']);
    });
    Route::get('/tg-connect/{id}', [TgBotConnectController::class, 'tgConnect']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/password-change', [AuthController::class, 'passwordChange']);
    Route::post('/profile', [AuthController::class, 'profileUpdate']);
});

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */
