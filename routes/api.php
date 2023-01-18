<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\OrderController;
use Illuminate\Http\Request;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => 'api', 'prefix' => 'user'], function ($router) {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('createOrder', [AuthController::class, 'createOrder']);
    Route::get('userOrders', [AuthController::class, 'userOrders']);
    Route::post('updateOrder', [AuthController::class, 'updateOrder']);
    Route::post('updateUserInfo', [AuthController::class, 'updateUserInfo']);
    Route::post('addRate', [AuthController::class, 'addRate']);
});

Route::group(['middleware' => 'api', 'prefix' => 'driver'], function ($router) {
    Route::post('login', [DriverController::class, 'login']);
    Route::post('register', [DriverController::class, 'register']);
    Route::post('me', [DriverController::class, 'me']);
    Route::post('logout', [DriverController::class, 'logout']);
    Route::get('selectOrder', [DriverController::class, 'selectOrder']);
    Route::post('acceptOrder', [DriverController::class, 'acceptOrder']);
    Route::get('driverOrders', [DriverController::class, 'driverOrders']);
    Route::post('updateDriverInfo', [DriverController::class, 'updateDriverInfo']);
    Route::post('addRate', [DriverController::class, 'addRate']);
});

Route::group(['middleware' => 'api'], function ($router) {
    Route::get('showAllOrder', [OrderController::class, 'showAllOrder']);
    Route::post('deleteOrder', [OrderController::class, 'deleteOrder']);
    Route::post('ratesForDriver', [OrderController::class, 'ratesForDriver']);
    Route::post('ratesForUser', [OrderController::class, 'ratesForUser']);
});











// Route::group(['namespace'=>'Api'],function(){

//     Route::controller(AuthController::class)->group(['prefix' => 'user'],function () {
//         Route::post('login', 'login');
//         Route::post('register', 'register');
//     });

//     // Route::group(['prefix' => 'user'],function(){
//     //     Route::post('register', 'AuthController@register');
//     //     Route::post('login', 'AuthController@login');
//     // });

// });
