<?php

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

Route::middleware('auth:sanctum')->group(function () {

    // ADMIN WhereHouse owner routes (users with admin ability on their sanctum token)
    Route::middleware('admin')->group(function(){


    });

    // NORMAL USERS / PHARMACISTS routes (users with user ability on their sanctum token)
    Route::middleware('user')->group(function (){

    });


    
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});


