<?php

use App\Http\Controllers\api\V1\AuthController;
use App\Http\Controllers\api\V1\BillController;
use App\Http\Controllers\api\V1\CategoryController;
use App\Http\Controllers\api\V1\FavoriteController;
use App\Http\Controllers\api\V1\OrderController;
use App\Http\Controllers\api\V1\ProductController;
use App\Http\Controllers\api\V1\ReportsController;
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
        //Searching
        Route::get('v1/products/search', [ProductController::class, 'search']);

        Route::get('/v1/product/mostCommon',[ProductController::class,'common']);
        //all prods
        Route::get('/v1/products', [ProductController::class, 'index']);
        //get all cats or one
        Route::apiResource('/v1/categories', CategoryController::class)->except('store', 'destroy', 'update');

        Route::get('/v1/bills/{bill}', [BillController::class, 'show']);
        
        Route::get('/v1/products/report', [ReportsController::class, 'salesByMonth']);
        // ADMIN WhereHouse owner routes (users with admin ability on their sanctum token)
        Route::middleware('admin')->group(function () {
            //add/delete/update prod
            Route::apiResource('/v1/products', ProductController::class)->except('show', 'index');
            // pay bill (change from unpaid to paid)
            Route::patch('/v1/orders/{order}/bill/pay', [BillController::class, 'pay']);
            // change order status to sent
            Route::patch('/v1/orders/{order}/send', [OrderController::class, 'send']);
            // change order status to received
            Route::patch('/v1/orders/{order}/receive', [OrderController::class, 'receive']);
            //add new cat
            Route::post('/v1/categories', [CategoryController::class, 'store']);
            //delete a cat
            Route::delete('/v1/categories/{category}', [CategoryController::class, 'destroy']);
        });
        Route::get('/v1/orders/filterstatus', [OrderController::class, 'filterbystatus']);
        // NORMAL USERS / PHARMACISTS routes (users with user ability on their sanctum token)
        Route::get('/v1/products/{product}', [ProductController::class, 'show']);
        Route::middleware('user')->group(function () {
            Route::get('/v1/orders/report', [ReportsController::class, 'userByMonth']);
            // add/delete an order
            Route::apiResource('/v1/orders', OrderController::class)->except('index', 'show', 'update');
            Route::post('/v1/favorites/{product}', [FavoriteController::class, 'toggleFavorite']);
            Route::get('/v1/favorites', [FavoriteController::class, 'index']);
            Route::get('/v1/products/{product}/isfav', [FavoriteController::class, 'isFavorite']);
        });
        //show an order
        Route::get('/v1/orders/{order}', [OrderController::class, 'show']);
        //get all orders
        Route::get('/v1/orders', [OrderController::class, 'index']);
        //get the current user info
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        //logout
        Route::post('v1/logout', [AuthController::class, 'logout']);
    });

    //Public Route To Create A user
    Route::post('/v1/createuser', [AuthController::class, 'createUser']);
    //Public Route To Login
    Route::post('/v1/login', [AuthController::class, 'loginUser']);
});
