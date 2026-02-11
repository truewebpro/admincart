<?php

use App\Http\Controllers\ShopController;
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

Route::middleware('resolve.shop')->prefix('shop/{shopname}')->group(function () {
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
    Route::get('/policies/{policy_slug}',[ShopController::class,'getPolicyBySlug']);
    Route::get('/homemenu',[ShopController::class,'getHomeMenu']);
    Route::get('/page/{page_slug}',[ShopController::class,'getPageBySlug']);
    Route::get('/cartsections', [ShopController::class, 'cartSections']);
    Route::get('/announcements', [ShopController::class, 'getAnnouncements']);
    Route::get('/searchtags', [ShopController::class, 'getSearchTags']);
    Route::get('/footer', [ShopController::class, 'getFooter']);
    Route::get('/customer/exists',[ShopController::class,'exitingCustomer']);
    Route::post('/reset-password',[ShopController::class,'resetPassword']);
});
