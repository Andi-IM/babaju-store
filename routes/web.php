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

/**
 * Route yang berkaitan dengan halaman muka (tidak terikat dengan customer).
 */
// Route::get('/',[FrontController::class, 'index'])->name('front.index');
Route::get('/product', [FrontController::class, 'product'])->name('front.product');
Route::get('/category/{slug}', [FrontController::class, 'categoryProduct'])->name('front.category');
Route::get('/product/{slug}', [FrontController::class, 'show'])->name('front.show_product');

Route::post('cart', [\App\Http\Controllers\Ecommerce\CartController::class, 'addToCart'])->name('front.cart');
Route::get('/cart', [\App\Http\Controllers\Ecommerce\CartController::class, 'listCart'])->name('front.list_cart');
Route::post('/cart/update', [\App\Http\Controllers\Ecommerce\CartController::class, 'updateCart'])->name('front.update_cart');

Route::get('/checkout', [\App\Http\Controllers\Ecommerce\CartController::class, 'checkout'])->name('front.checkout');
Route::post('/checkout', [\App\Http\Controllers\Ecommerce\CartController::class, 'prosesCheckout'])->name('front.store_checkout');
Route::get('/checkout/{invoice}', [\App\Http\Controllers\Ecommerce\CartController::class, 'checkoutFinish'])->name('front.finish_checkout');
Route::get('/product/ref/{user}/{product}', [FrontController::class, 'referralProduct'])->name('front.afiliasi');

Route::get('/', function () {
    return view('welcome');
});

/**
 * Route yang berkaitan dengan member
 * semua route akan diawali oleh endpoint /member
 * contoh : member/login
 */
Route::group(['prefix' => 'member', 'namespace' => 'Ecommerce'], function () {
    Route::get('login', [\App\Http\Controllers\Ecommerce\LoginController::class, 'loginForm'])->name('customer.login');
    Route::post('login', [\App\Http\Controllers\Ecommerce\LoginController::class, 'login'])->name('customer.post_login');
    Route::get('verify/{token}', [FrontController::class, 'verifyCustomerRegistration'])->name('customer.verify');

    // member/customer/...
    Route::group(['middleware' => 'customer'], function() {
        Route::get('dashboard', [\App\Http\Controllers\Ecommerce\LoginController::class, 'dashboard'])->name('customer.dashboard');
        Route::get('logout', [\App\Http\Controllers\Ecommerce\LoginController::class, 'logout'])->name('customer.logout');

        Route::get('orders', [\App\Http\Controllers\Ecommerce\OrderController::class, 'index'])->name('customer.orders');
        Route::get('orders/{invoice}', [\App\Http\Controllers\Ecommerce\OrderController::class, 'view'])->name('customer.view_order');
        Route::get('orders/pdf/{invoice}', [\App\Http\Controllers\Ecommerce\OrderController::class, 'pdf'])->name('customer.order_pdf');
        Route::post('orders/accept', [\App\Http\Controllers\Ecommerce\OrderController::class, 'acceptOrder'])->name('customer.order_accept');
        Route::get('orders/return/{invoice}', [\App\Http\Controllers\Ecommerce\OrderController::class, 'returnForm'])->name('customer.order_return');
        Route::put('orders/return/{invoice}', [\App\Http\Controllers\Ecommerce\OrderController::class, 'processReturn'])->name('customer.return');

        Route::get('payment', [\App\Http\Controllers\Ecommerce\OrderController::class, 'paymentForm'])->name('customer.paymentForm');
        Route::post('payment', [\App\Http\Controllers\Ecommerce\OrderController::class, 'storePayment'])->name('customer.savePayment');

        Route::get('setting', [FrontController::class, 'customerSettingForm'])->name('customer.settingForm');
        Route::post('setting', [FrontController::class, 'customerUpdateProfile'])->name('customer.setting');

        Route::get('/afiliasi', [FrontController::class,'listCommission'])->name('customer.affiliate');
    });
});

Auth::routes(); // Routing yang mencakup semua routing yang berkaitan dengan authentication

/**
 * Route yang berkaitan dengan admin
 * route akan diawali dengan endpoint administrator
 * contoh : administrator/home
 */
Route::group(['prefix' => 'administrator', 'middleware' => 'auth'], function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::resource('category', CategoryController::class)->except(['create', 'show']);
    Route::resource('product', ProductController::class)->except(['show']);
    Route::get('/product/bulk', [ProductController::class, 'massUploadForm'])->name('product.bulk');
    Route::post('/product/bulk', [ProductController::class, 'massUpload'])->name('product.saveBulk');

    // administrator/orders/...
    Route::group(['prefix' => 'orders'], function (){
        Route::get('/', [\App\Http\Controllers\OrderController::class,'index'])->name('orders.index');
        Route::get('/{invoice}', [\App\Http\Controllers\OrderController::class, 'view'])->name('orders.view');
        Route::get('/payment/{invoice}', [\App\Http\Controllers\OrderController::class, 'acceptPayment'])->name('orders.approve_payment');
        Route::post('/shipping', [\App\Http\Controllers\OrderController::class, 'shippingOrder'])->name('orders.shipping');
        Route::delete('/{id}', [\App\Http\Controllers\OrderController::class, 'destroy'])->name('orders.destroy');
        Route::get('/return/{invoice}', [\App\Http\Controllers\OrderController::class, 'return'])->name('orders.return');
        Route::post('/return', [\App\Http\Controllers\OrderController::class, 'approveReturn'])->name('orders.approve_return');
    });

    // administrator/reports/...
    Route::group(['prefix' => 'reports'], function (){
        Route::get('/order', [HomeController::class, 'orderReport'])->name('report.order');
        Route::get('/order/pdf/{daterange}', [HomeController::class, 'orderReportPdf'])->name('report.order_pdf');
        Route::get('/return', [HomeController::class, 'returnReport'])->name('report.return');
        Route::get('/return/pdf/{daterange}', [HomeController::class, 'returnReportPdf'])->name('report.return_pdf');
    });
});
