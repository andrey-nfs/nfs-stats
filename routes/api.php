<?php

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

Route::namespace('Admin')->group(function () {
    Route::middleware([])->group(function () {
        Route::get('/generate-stats', 'LogFilesController@processAll');
        Route::get('/reset-stats', 'LogFilesController@removeAll');
    });
});
