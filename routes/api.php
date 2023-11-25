<?php

use App\Http\Controllers\api\V1\AuthController;
use App\Http\Controllers\api\V1\ProductController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


//AUTH SANCTUM GROUP HERE ARE ROUTES AFTER LOGIN
Route::middleware('api')->group(function () {
    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/v1/products/getbycat', [ProductController::class, 'getByCategory']);
        Route::get('/v1/products/{product}', [ProductController::class, 'show']);
        // ADMIN WhereHouse owner routes (users with admin ability on their sanctum token)
        Route::middleware('admin')->group(function () {
            Route::apiResource('/v1/products', ProductController::class)->except('show');
        });

        // NORMAL USERS / PHARMACISTS routes (users with user ability on their sanctum token)
        Route::middleware('user')->group(function () {
        });


        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::post('v1/logout', [AuthController::class, 'logout']);
    });

    //Public Route To Create A user
    Route::post('/v1/createuser', [AuthController::class, 'createUser']);
    //Public Route To Login
    Route::post('/v1/login', [AuthController::class, 'loginUser']);
});
