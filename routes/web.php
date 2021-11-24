<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Ecommerce\FrontController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Auth;
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

// Route::get('/',[FrontController::class, 'index'])->name('front.index');

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();
Route::group(['prefix' => 'administrator', 'middleware' => 'auth'], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('category', CategoryController::class)->except(['create', 'show']);
    Route::resource('product', ProductController::class)->except(['show']);
    Route::get('/product/bulk',[ProductController::class, 'massUploadForm'])->name('product.bulk');
    Route::post('/product/bulk',[ProductController::class, 'massUpload'])->name('product.saveBulk');
});
