<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PageSettingController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\SendcloudWebhookController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SumupController;
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
    Route::get('/shop',[ShopController::class,'shopSetting']);
    Route::post('/sendcloud/webhook',[SendcloudWebhookController::class,'handleSendCloudWebHookEvents']);
    Route::get('/viva/gettoken',[VivaWebhookController::class,'getConfigToken']);
    Route::get('/viva/webhook/verify', [VivaWebhookController::class, 'verifyWebhook']);
    Route::post('/viva/webhook/verify',[VivaWebhookController::class,'handleWebhook']);
    Route::get('/homemetas', [ShopController::class, 'homeMetas']);
    Route::get('/shop/review/summary', [ShopController::class, 'shopReviewSummary']);
    Route::get('/shop/setting', [ShopController::class, 'getShopSetting']);
    Route::get('/herosections', [ShopController::class, 'homeHeroSections']);
    Route::get('/home-promos', [ShopController::class, 'homePromos']);
    Route::get('/lazysections', [ShopController::class, 'homeLazySections']);
    Route::get('/homesections', [ShopController::class, 'homeSections']);
    Route::get('/brands', [BrandController::class, 'allBrands']);
    Route::get('/brand/{brand_slug}', [BrandController::class, 'prosByBrand']);
    Route::get('/brandbyslug/{brand_slug}', [BrandController::class, 'getBrandBySlug']);
    Route::get('/brandsections/{brand_slug}', [BrandController::class, 'getBrandSections']);
    Route::get('/cats', [CatController::class, 'allCats']);
    Route::get('/all-cats', [CatController::class, 'getAllCats']);
    Route::get('/catpro/{slug}', [CatController::class, 'getCatorProduct']);
    Route::get('/cat/{slug}', [CatController::class, 'getCategory']);
    Route::get('/catbyslug/{slug}', [CatController::class, 'getCatBySlug']);
    Route::get('/catsections/{slug}', [CatController::class, 'getCatSections']);
    Route::get('/pro/{slug}', [ProductController::class, 'getProduct']);
    Route::get('/product/{slug}', [ProductController::class, 'getProductData']);
    Route::get('/productsections/{slug}', [ProductController::class, 'getProductLazyData']);
    Route::get('/products/all', [ProductController::class, 'searhProducts']);
    Route::get('/all-products', [ProductController::class, 'getAllProducts']);
    Route::get('/promo-by-type', [PromoController::class, 'getPromoByType']);
    Route::get('/page-settings', [PageSettingController::class, 'getPageSettingBySlug']);
    Route::get('/poptions',[ShopController::class,'shopPoptions']);
    Route::get('/sitemap',[ShopController::class,'siteMap']);
    Route::get('/html/sitemap',[ShopController::class,'htmlSitemap']);
    Route::get('/smethods',[ShopController::class,'shippingOptions']);
    Route::get('/pmethods',[ShopController::class,'paymentOptions']);
    Route::get('/blogs/all',[BlogController::class,'allBlogs']);
    Route::get('/all-blogs',[BlogController::class,'getAllBlogs']);
    Route::get('/blogs/{blog_slug}',[BlogController::class,'getBlogBySlug']);
    Route::get('/blogs/comments/{blogId}', [CommentController::class, 'getBlogComments']);
    Route::get('/policies/{policy_slug}',[ShopController::class,'getPolicyBySlug']);
    Route::get('/homemenu',[ShopController::class,'getHomeMenu']);
    Route::get('/page/{page_slug}',[ShopController::class,'getPageBySlug']);
    Route::get('/cartsections', [ShopController::class, 'cartSections']);
    Route::get('/announcements', [ShopController::class, 'getAnnouncements']);
    Route::get('/products/search',[ShopController::class,'webSearch']);
    Route::get('/products/qsearch',[ShopController::class,'resultSearchPage']);
    Route::get('/searchtags', [ShopController::class, 'getSearchTags']);
    Route::get('/footer', [ShopController::class, 'getFooter']);
    Route::get('/customer/exists',[ShopController::class,'exitingCustomer']);
    Route::post('/reset-password',[ShopController::class,'resetPassword']);

    // Create SumUp checkout
    Route::post('/payment/sumup/create', [SumupController::class, 'createCheckout']);

    // Verify payment after redirect (called from frontend)
    Route::post('/payment/sumup/verify', [SumupController::class, 'verify']);

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'getCart']);
        Route::post('/event', [CartController::class, 'event']);
        Route::get('/coupons', [CartController::class, 'getAvailableCoupons']);
        Route::post('/apply-coupon', [CartController::class, 'applyCouponToCart']);
        Route::post('/checkout',[CartController::class,'checkout']);
    });

    Route::prefix('customer')->group(function () {
        Route::post('/login',[CustomerController::class,'customerLogin'])->name('customer.login');
        Route::post('/register',[CustomerController::class,'customerRegister'])->name('customer.register');
        Route::post('/cregister',[CustomerController::class,'registerOnCheckout'])->name('customer.cregister');
        Route::get('/order/placed/detail',[CartController::class,'orderPlacedDetail']);

        Route::middleware('auth:customer')->group(function () {
            Route::get('/me',[CustomerController::class,'me']);
            Route::post('/account/update',[CustomerController::class,'accountUpdate']);
            Route::get('/orders',[CustomerController::class,'recentOrders']);
            Route::get('/order/detail',[CartController::class,'getOrderDetail']);
            Route::get('/addresses',[CustomerController::class,'allAddresses']);
            Route::get('/address/default',[CustomerController::class,'getDefaultAddress']);
            Route::post('/address/default',[CustomerController::class,'markAsDefaultAddress']);
            Route::post('/address/add',[CustomerController::class,'addNewAddress']);
            Route::post('/address/update',[CustomerController::class,'updateAddress']);
            Route::post('/address/delete',[CustomerController::class,'deleteAddress']);
        });
    });

});
