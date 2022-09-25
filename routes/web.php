<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\XenditController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::controller(HomeController::class)->prefix('bootcamp')->group(function () {
    Route::get('/', 'index')->name('bootcamps');
    Route::get('/{bootcampID}', 'checkout')->name('checkout');
    Route::post('/{bootcampID}', 'actCheckout')->name('actCheckout');
    Route::get('/transaction/{bootcampTransactionID}', 'detail')->name('detail');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::controller(XenditController::class)->group(function () {
    Route::post('/xendit-callback', 'XenditCallback');
    Route::post('/xendit-callback-ewallets', 'XenditCallbackEwallet');
});
