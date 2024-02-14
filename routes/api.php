<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\ClassificationController;
use App\Http\Controllers\AnswerController;
use App\Http\Controllers\ManagementController;
use App\Http\Controllers\ReportController;

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
Route::middleware(['force.json','throttle:300,1','auth:sanctum','admincando'])->group(function () {
    Route::post('/company/csv/upload/{my_user_id}', [CompanyController::class, 'store']);
    Route::post('/company/csv/download/{my_user_id}', [CompanyController::class, 'show']);
    Route::get('/company/{my_user_id}', [CompanyController::class, 'getCompanies']);
    Route::post('/marksheet/csv/upload/{my_user_id}', [AnswerController::class, 'store']);
});

Route::middleware(['force.json','throttle:300,1','auth:sanctum','groupOneDo'])->group(function () {
});

Route::middleware(['force.json','throttle:300,1','auth:sanctum','groupThreeDo'])->group(function () {
    Route::get('/user/{my_user_id}', [UserController::class, 'getUserDetails']);
    Route::get('/department/{my_user_id}', [DepartmentController::class, 'getDepartments']);
    Route::get('/director/{my_user_id}', [DirectorController::class, 'getDirectors']);
    Route::get('/company/{my_user_id}/{fixed_company_id}', [CompanyController::class, 'getCompanyDetail']);
    Route::get('/department/{my_user_id}/{department_id}', [DepartmentController::class, 'getDepartmentById']);
    Route::get('/classification/{my_user_id}', [ClassificationController::class, 'getClassifications']);
    Route::get('/company/analysis/all/{my_user_id}', [CompanyController::class, 'getCompanyAnalysis']);
    Route::get('/company/analysis/department/{my_user_id}', [CompanyController::class, 'getDepartmentAnalysis']);
    Route::get('/company/analysis/director/{my_user_id}', [CompanyController::class, 'getDirectorAnalysis']);
    Route::get('/company/analysis/classification/{my_user_id}', [CompanyController::class, 'getCompanyAnalysisClassification']);
    Route::get('/company/analysis/classification_title/{my_user_id}', [ClassificationController::class, 'getAllClassificationsTitle']);
    Route::get('/company/details/{my_user_id}/{company_id}', [CompanyController::class, 'getCompanyDetailById']);
    Route::get('/company/history/{my_user_id}/{fixed_company_id}', [CompanyController::class, 'getCompanyHistoryByFid']);
    Route::get('/user/history/{my_user_id}/{user_id}', [UserController::class, 'getUserHistory']);
    Route::post('/answer/csv/download/{my_user_id}', [AnswerController::class, 'show']);
    Route::post('/answer/csv/upload/{my_user_id}', [AnswerController::class, 'update']);
    Route::post('/users/save/{my_user_id}', [UserController::class, 'store']);
    Route::post('/user/csv/upload/{my_user_id}', [UserController::class, 'create']);
    Route::post('/user/csv/download/{my_user_id}', [UserController::class, 'show']);
    Route::post('/department/csv/upload/{my_user_id}', [DepartmentController::class, 'store']);
    Route::post('/department/csv/download/{my_user_id}', [DepartmentController::class, 'show']);
    Route::post('/director/csv/upload/{my_user_id}', [DirectorController::class, 'store']);
    Route::post('/director/csv/download/{my_user_id}', [DirectorController::class, 'show']);
    Route::post('/question/csv/upload/{my_user_id}', [QuestionController::class, 'store']);
    Route::post('/question/csv/download/{my_user_id}', [QuestionController::class, 'show']);
    Route::post('/classification/csv/upload/{my_user_id}', [ClassificationController::class, 'store']);
    Route::post('/classification/csv/download/{my_user_id}', [ClassificationController::class, 'show']);
    Route::get('/admin/{my_user_id}', [UserController::class, 'getAlladmins']);
    Route::get('/admin/{my_user_id}/{admin_id}', [UserController::class, 'getSingleAdmin']);
    Route::post('/admin/csv/upload/{my_user_id}', [UserController::class, 'uploadAdminByCSV']);
    Route::post('/admin/csv/download/{my_user_id}', [UserController::class, 'downloadAdminByCSV']);
    Route::post('/user/missmatch/{my_user_id}', [UserController::class, 'setMismatchData']);
    Route::get('/user/missmatch/{my_user_id}', [UserController::class, 'getMismatchData']);
    Route::delete('/user/missmatch/{my_user_id}/{examinee_id}', [UserController::class, 'deleteMismatchData']);
    Route::post('/report/company/login_info/{my_user_id}', [ReportController::class, 'createLogInfo']);
    Route::post('/report/company/excel/{my_user_id}', [ReportController::class, 'index']);
    Route::post('/report/company/marksheet/{my_user_id}', [ReportController::class, 'reportCompanyMarksheetExcel']);

    //Ticket ID: ASAKA-134- Excel company raw data.

    Route::post('/report/company/raw_data/{my_user_id}', 'API\Reports\CompanyReportController@index');

    //Ticket ID: ASAKA-136- Excel company upload.
    Route::post('/report/company/upload/{my_user_id}', 'API\Reports\CompanyReportController@upload');
    Route::post('/report/company/pdf/{my_user_id}', [ReportController::class, 'createCompanyPDF']);
    Route::post('/report/upload/schedule/{my_user_id}', [ReportController::class, 'uploadScheduleReport']);
});
Route::middleware(['force.json','throttle:300,1','auth:sanctum','rolefivedo'])->group(function () {
    Route::post('/answers/{my_user_id}', [AnswerController::class, 'storeMarksData']);
    Route::get('/questions/{my_user_id}', [QuestionController::class, 'getQuestions']);
    Route::post('/user/result_view/{my_user_id}', [UserController::class, 'usersResultView']);
    Route::post('/user/interview_request/{my_user_id}/{examinee_id}', [UserController::class, 'setInterviewFlg']);
    Route::get('/user/result/history/{my_user_id}', [CompanyController::class, 'getRoleFiveUserHistory']);
});
Route::middleware(['force.json','throttle:300,1','auth:sanctum'])->group(function () {
    Route::get('/user/{my_user_id}/{user_id}', [UserController::class, 'index']);
    Route::post('/calculation/personal/{my_user_id}', [AnswerController::class, 'calculateResultAPI']);
    Route::post('/management/send_email/{my_user_id}', [ManagementController::class, 'SendEmail']);
});
Route::middleware(['force.json', 'throttle:300,1'])->group(function () {
    Route::post('/user/login', [UserController::class, 'login']);
    Route::post('/user/password/reset', [UserController::class, 'resetPassword']);
    Route::post('/admin/password/reset', [UserController::class, 'resetAdminPassword']);
    Route::post('/cronjob/{type}', [ManagementController::class, 'ReslutDayCronJob']);
    Route::post('/report/personal/{examinee_id}', [ReportController::class, 'docurainApi']);
});
