<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\SendcloudWebhookController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\VivaWebhookController;
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

//Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//    return $request->user();
//});

Route::post('/shop/vapecraze/sendcloud/webhook',[SendcloudWebhookController::class,'handleSendcloudWebhook']);
Route::post('/shop/vapeportwholesale/sendcloud/webhook',[SendcloudWebhookController::class,'handleVapeportSendcloudWebhook']);

Route::middleware('resolve.shop')->prefix('shop/{shopname}')->group(function () {
    Route::get('/viva/gettoken',[VivaWebhookController::class,'getConfigToken']);
    Route::get('/viva/webhook/verify', [VivaWebhookController::class, 'verifyWebhook']);
    Route::post('/viva/webhook/verify',[VivaWebhookController::class,'handleWebhook']);
    Route::get('/homemetas', [ShopController::class, 'homeMetas']);
    Route::get('/homesections', [ShopController::class, 'homeSections']);
    Route::get('/brands', [ShopController::class, 'allBrands']);
    Route::get('/brand/{brand_slug}', [ShopController::class, 'prosByBrand']);
    Route::get('/cats', [ShopController::class, 'allCats']);
    Route::get('/catpro/{slug}', [ShopController::class, 'getCatorProduct']);
    Route::get('/cat/{slug}', [ShopController::class, 'getCategory']);
    Route::get('/pro/{slug}', [ShopController::class, 'getProduct']);
    Route::get('/products/all', [ShopController::class, 'searhProducts']);
    Route::get('/poptions',[ShopController::class,'shopPoptions']);
    Route::get('/sitemap',[ShopController::class,'siteMap']);
    Route::get('/html/sitemap',[ShopController::class,'htmlSitemap']);
    Route::get('/smethods',[ShopController::class,'shippingOptions']);
    Route::get('/pmethods',[ShopController::class,'paymentOptions']);
    Route::get('/blogs/all',[ShopController::class,'allBlogs']);
    Route::get('/blogs/{blog_slug}',[ShopController::class,'getBlogbySlug']);
    Route::get('/blogs/comments/{blogId}', [CommentController::class, 'getBlogComments']);
    Route::get('/policies/{policy_slug}',[ShopController::class,'getPolicyBySlug']);
    Route::get('/homemenu',[ShopController::class,'getHomeMenu']);
    Route::get('/page/{page_slug}',[ShopController::class,'getPageBySlug']);
    Route::get('/cartsections', [ShopController::class, 'cartSections']);
    Route::get('/announcements', [ShopController::class, 'getAnnouncements']);
    Route::get('/products/search',[ShopController::class,'webSearch']);
    Route::get('/searchtags', [ShopController::class, 'getSearchTags']);
    Route::get('/footer', [ShopController::class, 'getFooter']);
    Route::get('/customer/exists',[ShopController::class,'exitingCustomer']);
    Route::post('/reset-password',[ShopController::class,'resetPassword']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart']);
        Route::post('/event', [CartController::class, 'event']);

        Route::post('/checkout',[CartController::class,'checkout']);
    });

    Route::prefix('customer')->group(function () {
        Route::post('/login',[CustomerController::class,'customerLogin'])->name('customer.login');
        Route::post('/register',[CustomerController::class,'customerRegister'])->name('customer.register');
        Route::post('/cregister',[CustomerController::class,'registerOnCheckout'])->name('customer.cregister');

        Route::middleware('auth:customer')->group(function () {
            Route::get('/me',[CustomerController::class,'me']);
            Route::post('/account/update',[CustomerController::class,'accountUpdate']);
            Route::get('/orders',[CustomerController::class,'recentOrders']);
            Route::get('/address/default',[CustomerController::class,'getDefaultAddress']);
            Route::post('/address/default',[CustomerController::class,'markAsDefaultAddress']);
            Route::post('/address/add',[CustomerController::class,'addNewAddress']);
            Route::post('/address/update',[CustomerController::class,'updateAddress']);
            Route::post('/address/delete',[CustomerController::class,'deleteAddress']);
        });
    });

});
