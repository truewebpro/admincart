<?php

use App\Http\Controllers\Api\LoyaltyActionController;
use App\Http\Controllers\Api\LoyaltyController;
use App\Http\Controllers\Api\LoyaltyPointsPreviewController;
use App\Http\Controllers\Api\StoreCreditController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MigrateController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageSettingController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\SendcloudWebhookController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SumupController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\VivaWebhookController;
use App\Http\Controllers\WorldpayWebhookController;
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

//Route::post('/shop/vapecraze/sendcloud/webhook',[SendcloudWebhookController::class,'handleSendcloudWebhook']);
//Route::post('/shop/vapeportwholesale/sendcloud/webhook',[SendcloudWebhookController::class,'handleVapeportSendcloudWebhook']);

Route::middleware('resolve.shop')->prefix('shop/{shopname}')->group(function () {
    Route::get('/shop',[ShopController::class,'shopSetting']);
    Route::post('/sendcloud/webhook',[SendcloudWebhookController::class,'handleSendCloudWebHookEvents']);
    Route::post('/worldpay/webhook',[WorldpayWebhookController::class,'handleWorldPayWebhook']);
    Route::get('/viva/gettoken',[VivaWebhookController::class,'getConfigToken']);
    Route::get('/viva/webhook/verify', [VivaWebhookController::class, 'verifyWebhook']);
    Route::post('/viva/webhook/verify',[VivaWebhookController::class,'handleWebhook']);
    Route::get('/homemetas', [ShopController::class, 'homeMetas']);
    Route::get('/shop/review/summary', [ShopController::class, 'shopReviewSummary']);
    Route::get('/shop/setting', [ShopController::class, 'getShopSetting']);
    Route::get('/herosections', [HomepageController::class, 'homeHeroSections']);
    Route::get('/home-promos', [HomepageController::class, 'homePromos']);
    Route::get('/lazysections', [HomepageController::class, 'homeLazySections']);
    Route::get('/homesections', [HomepageController::class, 'homeSections']);
    Route::get('/brands', [BrandController::class, 'allBrands']);
    Route::get('/brand/{brand_slug}', [BrandController::class, 'prosByBrand']);
    Route::get('/brandbyslug/{brand_slug}', [BrandController::class, 'getBrandBySlug']);
    Route::get('/brandsections/{brand_slug}', [BrandController::class, 'getBrandSections']);
    Route::get('/cats', [CatController::class, 'allCats']);
    Route::get('/all-cats', [CatController::class, 'getAllCats']);
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
    Route::get('/policies/{policy_slug}',[PolicyController::class,'getPolicyBySlug']);
    Route::get('/homemenu',[MenuController::class,'getHomeMenu']);
    Route::get('/page/{page_slug}',[PageController::class,'getPageBySlug']);
    Route::get('/cartsections', [ShopController::class, 'cartSections']);
    Route::get('/announcements', [ShopController::class, 'getAnnouncements']);
    Route::get('/products/search',[ShopController::class,'webSearch']);
    Route::get('/products/qsearch',[ShopController::class,'resultSearchPage']);
    Route::get('/searchtags', [ShopController::class, 'getSearchTags']);
    Route::get('/footer', [ShopController::class, 'getFooter']);
    Route::get('/customer/exists',[CustomerController::class,'exitingCustomer']);
    Route::post('/reset-password',[CustomerController::class,'resetPassword']);
    Route::post('/track/pageview', [TrackingController::class, 'pageview']);
    Route::post('/track/heartbeat', [TrackingController::class, 'heartbeat']);

    // Create SumUp checkout
    Route::post('/payment/sumup/create', [SumupController::class, 'createCheckout']);

    // Verify payment after redirect (called from frontend)
    Route::post('/payment/sumup/verify', [SumupController::class, 'verify']);

    Route::prefix('loyalty/points-preview')->group(function () {
        Route::get('/{variantId}', [LoyaltyPointsPreviewController::class, 'show']);
        Route::post('/', [LoyaltyPointsPreviewController::class, 'bulk']);
    });


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

            //Loyalty  and Reward Points Routes
            Route::middleware('resolve.customer-shop')->group(function () {
                Route::prefix('store-credit')->group(function () {
                    Route::get('/balance', [StoreCreditController::class, 'balance']);
                    Route::get('/history', [StoreCreditController::class, 'history']);
                });

                Route::prefix('checkout/store-credit')->group(function () {
                    Route::post('/preview', [StoreCreditController::class, 'previewApplication']);
                });


                Route::prefix('loyalty')->group(function () {
                    Route::get('/balance', [LoyaltyController::class, 'balance']);
                    Route::get('/history', [LoyaltyController::class, 'history']);
                    Route::get('/rewards', [LoyaltyController::class, 'rewards']);
                    Route::post('/redeem', [LoyaltyController::class, 'redeem']);


                    // "Ways to earn": reviews, social follows/shares, custom actions
                    Route::get('/earn-actions', [LoyaltyActionController::class, 'index']);
                    Route::post('/earn-actions/{actionId}/claim', [LoyaltyActionController::class, 'claim']);
                });
            });
        });
    });

});


Route::prefix('migrate')->group(function () {
    Route::get('/shops', [MigrateController::class, 'migrateShops']);
    Route::get('/users', [MigrateController::class, 'migrateUsers']);
    Route::get('/brands', [MigrateController::class, 'migrateBrands']);
    Route::get('/tags', [MigrateController::class, 'migrateTags']);
    Route::get('/product-types', [MigrateController::class, 'migrateProductTypes']);
    Route::get('/poptions', [MigrateController::class, 'migratePoptions']);
    Route::get('/blogs', [MigrateController::class, 'migrateBlogs']);
    Route::get('/pages', [MigrateController::class, 'migratePages']);
    Route::get('/policies', [MigrateController::class, 'migratePolicies']);
    Route::get('/cats', [MigrateController::class, 'migrateCats']);
    Route::get('/products', [MigrateController::class, 'migrateProducts']);
});
