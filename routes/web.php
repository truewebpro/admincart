<?php

use App\Http\Controllers\Admin\LoyaltyActionApprovalController;
use App\Http\Controllers\Admin\LoyaltyEarnActionController;
use App\Http\Controllers\Admin\LoyaltyProductPointController;
use App\Http\Controllers\Admin\LoyaltySettingController;
use App\Http\Controllers\Admin\StoreCreditController as AdminStoreCreditController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CatController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DraftOrderController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\MailtrapController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PageSettingController;
use App\Http\Controllers\PolicyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ScustController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SendcloudController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\ShopifyPageController;
use App\Http\Controllers\SorderController;
use App\Http\Controllers\SproController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SuperadminController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\FrontController;
use App\Http\Controllers\CartController;

//Route::get('/', function () {
//    return view('auth.login');
//});

Route::get('/',[FrontController::class,'homePage'])->name('homePage');
Route::get('/plans',[FrontController::class,'plansPage'])->name('plansPage');

//Route::get('/google/autocomplete',function (Request $request){
//    $input = $request->input('query');
//    if (empty($input)) {
//        return response()->json(['error' => 'Missing input'], 400);
//    }
//    $apiKey = 'AIzaSyD1cGNhJz2BiG4oODjDAkfOH__dxXC_N10';
//    $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json";
//    $response = Http::get($url, [
//        'input' => $input,
//        'types' => 'address',
//        'components' => 'country:gb',
//        'key' => $apiKey,
//    ]);
//    return $response->json();
//});
//Route::get('/google/details', function (Request $request) {
//    $apiKey = "AIzaSyD1cGNhJz2BiG4oODjDAkfOH__dxXC_N10";
//    $placeId = $request->query('place_id');
//    $url = "https://maps.googleapis.com/maps/api/place/details/json";
//    $response = Http::get($url,[
//        'place_id' => $placeId,
//        'key' => $apiKey,
//    ]);
//    return $response->json();
//});

Auth::routes();

Route::post('/stripe/webhook',[StripeWebhookController::class,'handleWebhook']);
Route::get('/stripe/sync-existing',[StripeWebhookController::class,'syncExisting'])->middleware('auth');

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::post('/subscribe', [SubscriptionController::class, 'subscribe']);
    Route::get('/subscription', [SubscriptionController::class, 'current']);
    Route::get('/billing', [SubscriptionController::class, 'billing']);
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel']);
});

Route::middleware(['auth','resolve.admin.shop'])->group(function(){
    Route::prefix('superadmin')->group(function(){
        Route::get('/dashboard', [SuperadminController::class, 'superadminDashboard']);
        Route::get('/shops', [SuperadminController::class, 'allShops']);
        Route::get('/shopify/detail', [ShopifyController::class, 'getShopifyShop']);
        Route::get('/shopify/fetch-token', [ShopifyController::class, 'fetchToken']);
        Route::get('/shopify/fetch-products', [ShopifyController::class, 'fetchProducts']);
        Route::get('/shopify/import-and-save-articles', [ShopifyController::class, 'importArticles']);
        Route::get('/shopify/import-and-save-ccats', [ShopifyController::class, 'importCustomCollections']);
        Route::get('/shopify/import-and-save-scats', [ShopifyController::class, 'importSmartCollections']);
        Route::post('/shopify/add', [ShopifyController::class, 'addShopDetails']);
        Route::get('/shopify/sync-products', [SproController::class, 'sync']);
        Route::get('/shopify/sync-products-seo/{id}', [SproController::class, 'syncProductSeo']);
        Route::get('/shopify/all-products', [SproController::class, 'index']);
        Route::post('/shopify/create-single-product', [SproController::class, 'createSingleProduct']);
        Route::post('/shopify/import-products', [SproController::class, 'import']);
        // Customer Import Routes
        Route::post('/shopify/{shopId}/sync-customers', [ScustController::class, 'sync']);
        Route::get('/shopify/{shopId}/synced-customers', [ScustController::class, 'index']);
        Route::post('/shopify/{shopId}/import-customers', [ScustController::class, 'import']);
        // Customer Import Bulk Operations
        Route::post('/shopify/{shopId}/bulk-sync-customers/start', [ScustController::class, 'startBulkSync']);
        Route::get('/shopify/{shopId}/bulk-sync-customers/status', [ScustController::class, 'checkBulkSyncStatus']);
        Route::post('/shopify/{shopId}/bulk-sync-customers/process', [ScustController::class, 'processBulkSync']);
        // Shopify orders Routes
        Route::get('/shopify/{shopId}/live-orders', [SorderController::class, 'live']);
        Route::get('/shopify/{shopId}/orders-list', [SorderController::class, 'index']);
        Route::post('/shopify/{shopId}/orders/{orderId}/create', [SorderController::class, 'createOrder']);
        Route::post('/shopify/{shopId}/orders/bulk-create', [SorderController::class, 'bulkCreateOrders']);
        //Shopify pages routes
        Route::get('/shopify/{shopId}/pages/live', [ShopifyPageController::class, 'live']);
        Route::post('/shopify/{shopId}/pages/{pageId}/create', [ShopifyPageController::class, 'create']);
        Route::get('/shopify/{shopId}/pages/sync-seo', [ShopifyPageController::class, 'syncSeo']);

        Route::post('/shops/assign-user',[SuperadminController::class, 'assignUserToShop']);
        Route::get('/shop-users', [SuperadminController::class, 'shopUsers']);
        Route::put('/shop/update/{shop_id}', [SuperadminController::class, 'updateShop']);
        Route::put('/shop/update-subdomain/{shop_id}', [SuperadminController::class, 'updateShopSubdomain']);
        Route::put('/shop/update-order-prefix/{shop_id}', [SuperadminController::class, 'updateOrderPrefix']);
        Route::post('/shop/add', [SuperadminController::class, 'storeShop']);
        Route::post('/switch-shop', [SuperadminController::class, 'switchShop']);
        Route::get('/shops/check-slug', [SuperadminController::class, 'checkSlug']);
        Route::post('/shops/{shop_id}/status', [SuperadminController::class, 'toggleShopStatus']);
        Route::get('/plans', [SuperadminController::class, 'plans']);
        Route::get('/mailtrap/contacts/list', [MailtrapController::class, 'getMailtrapLists']);
        Route::post('/mailtrap/contacts/list', [MailtrapController::class, 'createMailtrapList']);
        Route::get('/sync-mailtrap-customers', [MailtrapController::class, 'syncAllCustomers']);
    });
    Route::prefix('sadmin')->group(function(){
        Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
        Route::get('/shop/business',[HomeController::class,'getShopBusiness']);
        Route::post('/shop/business/update',[HomeController::class,'updateShopBusiness']);
        Route::post('/shop/setting/update',[HomeController::class,'updateShopSetting']);
        Route::get('/products/export',[HomeController::class,'shopProductExport']);
        Route::get('/products/selected/export',[HomeController::class,'shopSelectedProductsExport']);
        Route::post('/products/import',[HomeController::class,'shopProductImport']);
        Route::get('/homepage/default',[HomeController::class,'getHomePage']);
        Route::post('/homepage/section/add/new',[SectionController::class,'addNewHomeSection']);
        Route::post('/homepage/section/himage/upload-url',[SectionController::class,'getHimageUploadUrl']);
        Route::post('/homepage/section/video/upload-url',[SectionController::class,'getVideoUploadUrl']);
        Route::post('/homepage/section/update/{section_id}',[SectionController::class,'updateAddedSection']);
        Route::post('/homepage/section/delete/{section_id}',[SectionController::class,'deleteAddedSection']);
        Route::post('/homepage/section/moveup/{section_id}',[SectionController::class,'moveSectionUp']);
        Route::post('/homepage/section/movedown/{section_id}',[SectionController::class,'moveSectionDown']);
        Route::post('/homepage/section/hideorshow/{section_id}',[SectionController::class,'hideOrShowSection']);
        Route::post('/homepage/add-faq',[FaqController::class,'addHomepageFaq']);
        Route::post('/homepage/edit-faq',[FaqController::class,'editHomepageFaq']);
        Route::post('/homepage/delete-faq',[FaqController::class,'deleteHomepageFaq']);
        Route::get('/homepage/promo',[HomepageController::class,'getHomePagePromo']);
        Route::get('/promo',[PromoController::class,'getPromo']);
        Route::post('/update-promo',[PromoController::class,'updatePromo']);
        Route::post('/add-promo-item',[PromoController::class,'addPromoItem']);
        Route::post('/update-promo-item',[PromoController::class,'updatePromoItem']);
        Route::post('/delete-promo-item',[PromoController::class,'deletePromoItem']);
        Route::post('/homepage/update-promo',[HomepageController::class,'updatePromo']);
        Route::post('/homepage/add-promo-item',[HomepageController::class,'addPromoItem']);
        Route::post('/homepage/update-promo-item',[HomepageController::class,'updatePromoItem']);
        Route::post('/homepage/delete-promo-item',[HomepageController::class,'deletePromoItem']);
        Route::get('/page-setting',[PageSettingController::class,'getPageSetting']);
        Route::post('/page-setting/update',[PageSettingController::class,'updatePageSetting']);
        Route::get('/shop',[HomeController::class,'getShopDetail']);
        Route::get('/users',[HomeController::class,'allUsers']);
        Route::get('/carts',[HomeController::class,'allCarts']);
        Route::get('/cart/{cart_id}',[HomeController::class,'getCartById']);
        Route::post('/create-viva-missing-order',[CartController::class,'createMissingOrder']);
        Route::get('/orders', [OrderController::class, 'allOrders'])->name('all-orders');
        Route::get('/order-stats', [OrderController::class, 'orderStats'])->name('order-stats');
        Route::get('/order/{order_id}',[OrderController::class,'getOrderById']);
        Route::post('/order/update',[OrderController::class,'updateAdminOrder']);
        Route::post('/order/sendtosendcloud/single',[OrderController::class,'sendToSendCloudSingle']);
        Route::get('/order/sendcloud/shipping-options',[SendcloudController::class,'getShippingOptions']);
        Route::get('/order/sendcloud/shipping-carriers',[SendcloudController::class,'getSendcloudCarriers']);
        Route::post('/order/sendcloud/send-single-order',[SendcloudController::class,'sendToSendCloudSingle']);
        Route::get('/order/{orderId}/sendcloud/label', [SendcloudController::class, 'printLabel']);
        Route::get('/order-invoice/{id}', [OrderController::class, 'invoice']);
        Route::get('/order-label/{id}', [OrderController::class, 'label']);
        Route::get('/all-products',[ProductController::class,'allProducts']);
        Route::get('/products/{product_id}',[ProductController::class,'getProductbyId']);
        Route::get('/pros/new',[ProductController::class,'addProductView']);
        Route::post('/product/section/add/new',[SectionController::class,'addNewProductSection']);
        Route::post('/product/update/{product_id}',[ProductController::class,'productUpdate']);
        Route::post('/product/delete/{product_id}',[ProductController::class,'deleteProduct']);
        Route::post('/product/bulk/update',[ProductController::class,'bulkProductUpdate']);
        Route::post('/products/bulk-delete',[ProductController::class,'bulkDeleteProduct']);
        Route::post('/products/bulk-tag-add',[ProductController::class,'bulkAddTag']);
        Route::post('/product/new',[ProductController::class,'addProductNew']);
        Route::post('/product/highlight/update',[ProductController::class,'updateOrCreateHighlights']);
        Route::post('/product/highlight/delete',[ProductController::class,'deleteHighlight']);
        Route::post('/product/review/add',[ProductController::class,'addAdminProductReview']);
        Route::post('/product/update-unit-pack',[ProductController::class,'updateUnitPack']);
        Route::post('/product/save-tier-pricing',[ProductController::class,'saveTierPricing']);
        Route::post('/product/add-faq',[FaqController::class,'addProductFaq']);
        Route::post('/product/edit-faq',[FaqController::class,'editProductFaq']);
        Route::post('/product/delete-faq',[FaqController::class,'deleteProductFaq']);
        Route::get('/categories',[CatController::class,'allAdminCats']);
        Route::get('/cats/new',[CatController::class,'addCatView']);
        Route::post('/cat/new',[CatController::class,'addCatNew']);
        Route::post('/cat/section/add/new',[SectionController::class,'addNewCatSection']);
        Route::post('/products/filter-ids',[CatController::class,'filterProductIds']);
        Route::get('/categories/{cat_id}',[CatController::class,'getCat']);
        Route::post('/catpro/update-position',[CatController::class,'updateCatProPosition']);
        Route::post('/categories/related/add',[CatController::class,'addRelatedCat']);
        Route::post('/cat/mupdate/{cat_id}',[CatController::class,'updateManualCatbyId']);
        Route::post('/cat/supdate/{cat_id}',[CatController::class,'updateSmartCatbyId']);
        Route::get('/cat/delete/{cat_id}',[CatController::class,'deleteCategorybyId']);
        Route::post('/cat/add-faq',[FaqController::class,'addCatFaq']);
        Route::post('/cat/edit-faq',[FaqController::class,'editCatFaq']);
        Route::post('/cat/delete-faq',[FaqController::class,'deleteCatFaq']);
        Route::get('/inventory',[StockController::class,'allInventory']);
        Route::post('/inventory/update',[StockController::class,'updateInventory']);
        Route::get('/customers',[CustomerController::class,'allCustomers']);
        Route::post('/mailtrap/sync-customers',[CustomerController::class,'syncCustomers']);
        Route::post('/mailtrap/update-customer/{cshopId}', [CustomerController::class, 'updateCustomer']);
        Route::get('/customer/details/{customer_id}',[CustomerController::class,'getCustomerByID']);
        Route::post('/customer/add-new',[CustomerController::class,'addNewCustomer']);
        Route::post('/customer/check-exists',[CustomerController::class,'checkCustomerExists']);
        Route::post('/customer/attach',[CustomerController::class,'attachCustomer']);
        Route::get('/ptypes',[HomeController::class,'allPtypes']);
        Route::post('/ptype/update',[HomeController::class,'updatePtype']);
        Route::post('/ptype/delete',[HomeController::class,'deletePtypebyId']);
        Route::get('/brands',[BrandController::class,'allAdminBrands']);
        Route::get('/brand/{brand_id}',[BrandController::class,'brandById']);
        Route::post('/brand/update',[BrandController::class,'updateBrand']);
        Route::post('/brand/delete',[BrandController::class,'deleteBrand']);
        Route::post('/brand/section/add/new',[SectionController::class,'addNewBrandSection']);
        Route::post('/brand/add-faq',[FaqController::class,'addBrandFaq']);
        Route::post('/brand/edit-faq',[FaqController::class,'editBrandFaq']);
        Route::post('/brand/delete-faq',[FaqController::class,'deleteBrandFaq']);
        Route::get('/features',[HomeController::class,'featuresList']);
        Route::post('/feature/update',[HomeController::class,'updateFeature']);
        Route::post('/feature/delete',[HomeController::class,'deleteFeature']);
        Route::post('/specific/update',[HomeController::class,'updateSpecific']);
        Route::post('/specific/delete',[HomeController::class,'deleteSpecific']);
        Route::get('/tags',[HomeController::class,'allTags']);
        Route::post('/tag/update',[HomeController::class,'updateTag']);
        Route::post('/tag/delete',[HomeController::class,'deleteTag']);
        Route::get('/poptions',[HomeController::class,'allPoptions']);
        Route::post('/poption/update',[HomeController::class,'updatePoption']);
        Route::get('/shops/theme',[HomeController::class,'themeSettings']);
        Route::get('/settings/shipping',[HomeController::class,'shippingSettings']);
        Route::post('/settings/update/sendcloud',[HomeController::class,'updateSendCloud']);
        Route::get('/settings/sendcloud/shipping-options', [SendcloudController::class, 'getSendcloudOptionSettings']);
        Route::post('/settings/sendcloud/shipping-options', [SendcloudController::class, 'updateSendcloudOptionSettings']);
        Route::post('/settings/shipping/update',[HomeController::class,'updateOrAddAdminShipMethod']);
        Route::post('/settings/shipping/delete',[HomeController::class,'deleteAdminShipMethod']);
        Route::get('/settings/payment/methods/list',[HomeController::class,'getAdminShopPaymentMethods']);
        Route::post('/settings/payment/method/update',[HomeController::class,'updateAdminPaymentMethod']);
        Route::get('/shop/preferences',[HomeController::class,'shopPreferences']);
        Route::post('/shop/preference/update',[HomeController::class,'shopPreferenceUpdate']);
        Route::post('/shop/social/update',[HomeController::class,'shopSocialUpdate']);
        Route::get('/blogs/list',[BlogController::class,'blogsList']);
        Route::get('/blogs/edit/{blog_id}',[BlogController::class,'getBlogById']);
        Route::post('/blogs/add/new',[BlogController::class,'addBlog']);
        Route::post('/blogs/update',[BlogController::class,'updateBlog']);
        Route::post('/blog/section/add/new',[SectionController::class,'addNewBlogSection']);
        Route::post('/blog/add-faq',[FaqController::class,'addBlogFaq']);
        Route::post('/blog/edit-faq',[FaqController::class,'editBlogFaq']);
        Route::post('/blog/delete-faq',[FaqController::class,'deleteBlogFaq']);
        Route::get('/pages/list',[PageController::class,'getAdminPagesList']);
        Route::get('/page/edit/{page_id}',[PageController::class,'getAdminPageById']);
        Route::post('/page/add/new',[PageController::class,'addPage']);
        Route::post('/page/update',[PageController::class,'updateAdminPage']);
        Route::post('/page/delete',[PageController::class,'deleteAdminPage']);
        Route::post('/page/section/add/new',[SectionController::class,'addNewPageSection']);
        Route::post('/page/add-faq',[FaqController::class,'addPageFaq']);
        Route::post('/page/edit-faq',[FaqController::class,'editPageFaq']);
        Route::post('/page/delete-faq',[FaqController::class,'deletePageFaq']);
        Route::get('/policies/list',[PolicyController::class,'getPolicyList']);
        Route::get('/policies/edit/{policy_id}',[PolicyController::class,'getPolicyById']);
        Route::post('/policies/update',[PolicyController::class,'updatePolicy']);
        Route::get('/search-all',[HomeController::class,'searchAll']);
        Route::get('/menus/list',[MenuController::class,'getAdminMenusList']);
        Route::post('/menu/add',[MenuController::class,'addAdminMenu']);
        Route::get('/menu/edit/{menu_id}',[MenuController::class,'getAdminMenuById']);
        Route::post('/menu/update',[MenuController::class,'updateAdminMenu']);
        Route::post('/menu/delete',[MenuController::class,'deleteAdminMenu']);
        Route::get('/cartpage/default',[HomeController::class,'getCartPage']);
        Route::post('/cartpage/section/add/new',[SectionController::class,'addNewCartSection']);
        Route::get('/announcements',[HomeController::class,'getShopAnnouncements']);
        Route::post('/announcement/update',[HomeController::class,'updateAnnouncement']);
        Route::get('/searchtags',[HomeController::class,'getShopSearchTags']);
        Route::post('/searchtag/update',[HomeController::class,'updateSearchTag']);
        Route::get('/searchbrands',[HomeController::class,'getShopSearchBrands']);
        Route::post('/searchbrand/update',[HomeController::class,'updateSearchBrand']);
        Route::get('/searchcats',[HomeController::class,'getShopSearchCats']);
        Route::post('/searchcat/update',[HomeController::class,'updateSearchCat']);
        Route::get('/footer',[HomeController::class,'getShopFooter']);
        Route::post('/footer/update',[HomeController::class,'updateFooter']);
        Route::get('/subscribe/section',[HomeController::class,'getShopSubscribeSection']);
        Route::post('/subscribe/section/update',[HomeController::class,'updateShopSubscribeSection']);
        Route::get('/search/alinks',[HomeController::class,'getAllLinks']);
        Route::get('/coupons/list',[HomeController::class,'getAdminCouponsList']);
        Route::post('/coupon/save',[HomeController::class,'saveAdminCoupon']);
        Route::post('/coupon/delete',[HomeController::class,'deleteAdminCoupon']);
        Route::get('/pricing-rules/list',[HomeController::class,'getAdminPricingRules']);
        Route::post('/pricing-rule/save',[HomeController::class,'saveAdminPricingRule']);
        Route::post('/pricing-rule/delete',[HomeController::class,'deleteAdminPricingRule']);
        Route::get('/reviews/list',[HomeController::class,'getAdminReviewsList']);
        Route::post('/review/update',[HomeController::class,'updateAdminProductReview']);
        Route::post('/generate/ai',[HomeController::class,'allContentFromAi']);
        Route::get('/plans',[SubscriptionController::class,'plans']);

        // Inside Route::prefix('sadmin')->group(function(){ ... }), alongside /order-stats etc.
        Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);
        Route::get('/analytics/sales-trend', [AnalyticsController::class, 'salesTrend']);
        Route::get('/analytics/top-products', [AnalyticsController::class, 'topProducts']);
        Route::get('/analytics/orders', [AnalyticsController::class, 'orders']);
        Route::get('/analytics/breakdown', [AnalyticsController::class, 'breakdown']);
        Route::get('/analytics/customer-split', [AnalyticsController::class, 'customerSplit']);
        Route::get('/analytics/cart-conversion', [AnalyticsController::class, 'cartConversion']);
        Route::get('/analytics/cart-funnel', [AnalyticsController::class, 'cartFunnel']);
        Route::get('/analytics/live-now', [AnalyticsController::class, 'liveNow']);
        Route::get('/analytics/pageview-trend', [AnalyticsController::class, 'pageviewTrend']);
        Route::get('/analytics/top-pages', [AnalyticsController::class, 'topPages']);
        Route::get('/analytics/traffic-breakdown', [AnalyticsController::class, 'trafficBreakdown']);
        Route::get('/analytics/sessions-over-time', [AnalyticsController::class, 'sessionsOverTime']);
        Route::get('/analytics/daily-stats', [AnalyticsController::class, 'dailyStats']);
        Route::get('/analytics/live-locations', [AnalyticsController::class, 'liveLocations']);
        Route::get('/analytics/sessions-by-location', [AnalyticsController::class, 'sessionsByLocation']);
        Route::get('/analytics/customer-behavior', [AnalyticsController::class, 'customerBehavior']);
        Route::get('/analytics/sales-stat-cards', [AnalyticsController::class, 'salesStatCards']);
        Route::get('/analytics/total-sales-over-time', [AnalyticsController::class, 'totalSalesOverTime']);
        Route::get('/analytics/sales-breakdown', [AnalyticsController::class, 'salesBreakdown']);
        Route::get('/analytics/sales-by-channel', [AnalyticsController::class, 'salesByChannel']);
        Route::get('/analytics/aov-over-time', [AnalyticsController::class, 'aovOverTime']);
        Route::get('/analytics/sales-by-product', [AnalyticsController::class, 'salesByProduct']);
        Route::get('/analytics/sessions-over-time-range', [AnalyticsController::class, 'sessionsOverTimeRange']);
        Route::get('/analytics/conversion-rate-over-time', [AnalyticsController::class, 'conversionRateOverTime']);
        Route::get('/analytics/conversion-breakdown', [AnalyticsController::class, 'conversionBreakdown']);
        Route::get('/analytics/sessions-by-location-comparison', [AnalyticsController::class, 'sessionsByLocationComparison']);
        Route::get('/analytics/sales-by-social-referrer', [AnalyticsController::class, 'salesBySocialReferrer']);
        Route::get('/analytics/sessions-by-referrer', [AnalyticsController::class, 'sessionsByReferrer']);
        Route::get('/analytics/sales-by-pos-location', [AnalyticsController::class, 'salesByPosLocation']);
        Route::get('/analytics/products-sell-through', [AnalyticsController::class, 'productsBySellThroughRate']);

        //Draft Order Routes
        Route::get('draft-orders', [DraftOrderController::class, 'index']);
        Route::post('draft-orders', [DraftOrderController::class, 'store']);
        Route::get('draft-orders/{id}', [DraftOrderController::class, 'show']);
        Route::delete('draft-orders/{id}', [DraftOrderController::class, 'destroy']);
        Route::post('draft-orders/{id}/duplicate', [DraftOrderController::class, 'duplicate']);
        Route::post('draft-orders/{id}/convert', [DraftOrderController::class, 'convert']);

        Route::post('draft-orders/{id}/items', [DraftOrderController::class, 'addItem']);
        Route::put('draft-orders/{id}/items/{itemId}', [DraftOrderController::class, 'updateItem']);
        Route::delete('draft-orders/{id}/items/{itemId}', [DraftOrderController::class, 'removeItem']);

        Route::put('draft-orders/{id}/customer', [DraftOrderController::class, 'setCustomer']);
        Route::delete('draft-orders/{id}/customer', [DraftOrderController::class, 'removeCustomer']);

        Route::post('draft-orders/{id}/discount', [DraftOrderController::class, 'applyDiscount']);
        Route::delete('draft-orders/{id}/discount', [DraftOrderController::class, 'removeDiscount']);

        Route::post('draft-orders/{id}/shipping', [DraftOrderController::class, 'updateShipping']);
        Route::post('draft-orders/{id}/notes', [DraftOrderController::class, 'updateNotes']);
        Route::post('draft-orders/{id}/tags', [DraftOrderController::class, 'syncTags']);

        Route::post('draft-orders/{id}/payments', [DraftOrderController::class, 'recordPayment']);
        Route::get('draft-orders/{id}/payments', [DraftOrderController::class, 'listPayments']);

        // Admin Product Search
        Route::get('/products-search',[DraftOrderController::class,'searchProducts']);

        Route::get('/customers-search', [DraftOrderController::class, 'searchCustomers']);
        Route::post('/shop-customers', [DraftOrderController::class, 'createCustomer']);
        Route::get('/shop-customers/{customerId}/addresses', [DraftOrderController::class, 'listCustomerAddresses']);
        Route::post('/shop-customers/{customerId}/addresses', [DraftOrderController::class, 'createCustomerAddress']);

        Route::post('draft-orders/{id}/shipping-address', [DraftOrderController::class, 'setShippingAddress']);

        Route::get('/shop-ctags', [DraftOrderController::class, 'listTags']);
        Route::get('/ship-methods', [DraftOrderController::class, 'listShipMethods']);

        // Loyalty sadmin routes
        Route::get('/customer-shops/{customerShop}/store-credit', [AdminStoreCreditController::class, 'show']);
        Route::post('/customer-shops/{customerShop}/store-credit/adjust', [AdminStoreCreditController::class, 'adjust']);
        Route::post('/orders/{order}/refund-as-credit', [AdminStoreCreditController::class, 'refundAsCredit']);

        Route::get('/loyalty/settings', [LoyaltySettingController::class, 'show']);
        Route::put('/loyalty/settings', [LoyaltySettingController::class, 'update']);
        Route::post('/loyalty/redeem-rules', [LoyaltySettingController::class, 'storeRule']);
        Route::put('/loyalty/redeem-rules/{rule}', [LoyaltySettingController::class, 'updateRule']);
        Route::delete('/loyalty/redeem-rules/{rule}', [LoyaltySettingController::class, 'destroyRule']);

        // Per-product-variant points overrides — editable anytime, no redeploy needed
        Route::get('/loyalty/product-points', [LoyaltyProductPointController::class, 'index']);
        Route::post('/loyalty/product-points', [LoyaltyProductPointController::class, 'store']);
        Route::put('/loyalty/product-points/{override}', [LoyaltyProductPointController::class, 'update']);
        Route::delete('/loyalty/product-points/{override}', [LoyaltyProductPointController::class, 'destroy']);

        // Ways-to-earn actions (reviews, social, share, custom)
        Route::get('/loyalty/earn-actions', [LoyaltyEarnActionController::class, 'index']);
        Route::post('/loyalty/earn-actions', [LoyaltyEarnActionController::class, 'store']);
        Route::put('/loyalty/earn-actions/{action}', [LoyaltyEarnActionController::class, 'update']);
        Route::delete('/loyalty/earn-actions/{action}', [LoyaltyEarnActionController::class, 'destroy']);

        // Manual review queue for actions that can't be auto-verified
        Route::get('/loyalty/action-completions', [LoyaltyActionApprovalController::class, 'index']);
        Route::post('/loyalty/action-completions/{completion}/approve', [LoyaltyActionApprovalController::class, 'approve']);
        Route::post('/loyalty/action-completions/{completion}/reject', [LoyaltyActionApprovalController::class, 'reject']);


    });
});

Route::middleware(['auth','resolve.admin.shop'])
    ->get('/{any}', function () {
        return view('sadmin');
    })->where('any','.*');


//Route::get('/{any}', function () {
//    return view('sadmin');
//})->where('any', '.*$');
