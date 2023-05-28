<?php

use Illuminate\Support\Facades\Route;

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

Route::controller(App\Http\Controllers\System\ApplicantController::class)->prefix('applicants')->group(function () {

    Route::post('/index', 'index');
    Route::post('', 'store');
    Route::put('{applicant}', 'update');
    Route::delete('{applicant}', 'destroy');
    Route::get('{applicant}', 'get');

});
