<?php

use App\Http\Controllers\FaqController;
use App\Http\Controllers\HomepageController;
use App\Http\Controllers\MailtrapController;
use App\Http\Controllers\PageSettingController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\SproController;
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

Route::get('/google/autocomplete',function (Request $request){
    $input = $request->input('query');
    if (empty($input)) {
        return response()->json(['error' => 'Missing input'], 400);
    }
    $apiKey = 'AIzaSyD1cGNhJz2BiG4oODjDAkfOH__dxXC_N10';
    $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json";
    $response = Http::get($url, [
        'input' => $input,
        'types' => 'address',
        'components' => 'country:gb',
        'key' => $apiKey,
    ]);
    return $response->json();
});
Route::get('/google/details', function (Request $request) {
    $apiKey = "AIzaSyD1cGNhJz2BiG4oODjDAkfOH__dxXC_N10";
    $placeId = $request->query('place_id');
    $url = "https://maps.googleapis.com/maps/api/place/details/json";
    $response = Http::get($url,[
        'place_id' => $placeId,
        'key' => $apiKey,
    ]);
    return $response->json();
});

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
        Route::post('/shopify/add', [ShopifyController::class, 'addShopDetails']);
        Route::get('/shopify/sync-products', [SproController::class, 'sync']);
        Route::get('/shopify/all-products', [SproController::class, 'index']);
        Route::post('/shopify/create-single-product', [SproController::class, 'createSingleProduct']);
        Route::post('/shopify/import-products', [SproController::class, 'import']);
        Route::post('/shops/assign-user',[SuperadminController::class, 'assignUserToShop']);
        Route::get('/shop-users', [SuperadminController::class, 'shopUsers']);
        Route::put('/shop/update/{shop_id}', [SuperadminController::class, 'updateShop']);
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
        Route::post('/homepage/section/add/new',[HomeController::class,'addNewHomeSection']);
        Route::post('/homepage/section/himage/upload-url',[HomeController::class,'getHimageUploadUrl']);
        Route::post('/homepage/section/video/upload-url',[HomeController::class,'getVideoUploadUrl']);
        Route::post('/homepage/section/update/{section_id}',[HomeController::class,'updateAddedSection']);
        Route::post('/homepage/section/delete/{section_id}',[HomeController::class,'deleteAddedSection']);
        Route::post('/homepage/section/moveup/{section_id}',[HomeController::class,'moveSectionUp']);
        Route::post('/homepage/section/movedown/{section_id}',[HomeController::class,'moveSectionDown']);
        Route::post('/homepage/section/hideorshow/{section_id}',[HomeController::class,'hideOrShowSection']);
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
        Route::get('/orders', [HomeController::class, 'allOrders'])->name('all-orders');
        Route::get('/order-stats', [HomeController::class, 'orderStats'])->name('order-stats');
        Route::get('/order/{order_id}',[HomeController::class,'getOrderById']);
        Route::post('/order/update',[HomeController::class,'updateAdminOrder']);
        Route::post('/order/sendtosendcloud/single',[HomeController::class,'sendToSendCloudSingle']);
        Route::get('/order-invoice/{id}', [HomeController::class, 'invoice']);
        Route::get('/order-label/{id}', [HomeController::class, 'label']);
        Route::get('/pros',[HomeController::class,'allProducts']);
        Route::get('/products/{product_id}',[HomeController::class,'getProductbyId']);
        Route::get('/pros/new',[HomeController::class,'addProductView']);
        Route::post('/product/section/add/new',[HomeController::class,'addNewProductSection']);
        Route::post('/product/update/{product_id}',[HomeController::class,'productUpdate']);
        Route::post('/product/delete/{product_id}',[HomeController::class,'deleteProduct']);
        Route::post('/product/bulk/update',[HomeController::class,'bulkProductUpdate']);
        Route::post('/products/bulk-delete',[HomeController::class,'bulkDeleteProduct']);
        Route::post('/products/bulk-tag-add',[HomeController::class,'bulkAddTag']);
        Route::post('/product/new',[HomeController::class,'addProductNew']);
        Route::post('/product/highlight/update',[HomeController::class,'updateOrCreateHighlights']);
        Route::post('/product/highlight/delete',[HomeController::class,'deleteHighlight']);
        Route::post('/product/review/add',[HomeController::class,'addAdminProductReview']);
        Route::post('/product/update-unit-pack',[HomeController::class,'updateUnitPack']);
        Route::post('/product/save-tier-pricing',[HomeController::class,'saveTierPricing']);
        Route::post('/product/add-faq',[FaqController::class,'addProductFaq']);
        Route::post('/product/edit-faq',[FaqController::class,'editProductFaq']);
        Route::post('/product/delete-faq',[FaqController::class,'deleteProductFaq']);
        Route::get('/categories',[HomeController::class,'allCats']);
        Route::get('/cats/new',[HomeController::class,'addCatView']);
        Route::post('/cat/new',[HomeController::class,'addCatNew']);
        Route::post('/cat/section/add/new',[HomeController::class,'addNewCatSection']);
        Route::post('/products/filter-ids',[HomeController::class,'filterProductIds']);
        Route::get('/categories/{cat_id}',[HomeController::class,'getCat']);
        Route::post('/catpro/update-position',[HomeController::class,'updateCatProPosition']);
        Route::post('/categories/related/add',[HomeController::class,'addRelatedCat']);
        Route::post('/cat/mupdate/{cat_id}',[HomeController::class,'updateManualCatbyId']);
        Route::post('/cat/supdate/{cat_id}',[HomeController::class,'updateSmartCatbyId']);
        Route::get('/cat/delete/{cat_id}',[HomeController::class,'deleteCategorybyId']);
        Route::post('/cat/add-faq',[FaqController::class,'addCatFaq']);
        Route::post('/cat/edit-faq',[FaqController::class,'editCatFaq']);
        Route::post('/cat/delete-faq',[FaqController::class,'deleteCatFaq']);
        Route::get('/inventory',[HomeController::class,'allInventory']);
        Route::post('/inventory/update',[HomeController::class,'updateInventory']);
        Route::get('/customers',[HomeController::class,'allCustomers']);
        Route::post('/mailtrap/sync-customers',[HomeController::class,'syncCustomers']);
        Route::post('/mailtrap/update-customer/{cshopId}', [HomeController::class, 'updateCustomer']);
        Route::get('/customer/details/{customer_id}',[HomeController::class,'getCustomerByID']);
        Route::post('/customer/add-new',[HomeController::class,'addNewCustomer']);
        Route::post('/customer/check-exists',[HomeController::class,'checkCustomerExists']);
        Route::post('/customer/attach',[HomeController::class,'attachCustomer']);
        Route::get('/ptypes',[HomeController::class,'allPtypes']);
        Route::post('/ptype/update',[HomeController::class,'updatePtype']);
        Route::post('/ptype/delete',[HomeController::class,'deletePtypebyId']);
        Route::get('/brands',[HomeController::class,'allBrands']);
        Route::get('/brand/{brand_id}',[HomeController::class,'brandById']);
        Route::post('/brand/update',[HomeController::class,'updateBrand']);
        Route::post('/brand/delete',[HomeController::class,'deleteBrand']);
        Route::post('/brand/section/add/new',[HomeController::class,'addNewBrandSection']);
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
        Route::post('/settings/shipping/update',[HomeController::class,'updateOrAddAdminShipMethod']);
        Route::post('/settings/shipping/delete',[HomeController::class,'deleteAdminShipMethod']);
        Route::get('/settings/payment/methods/list',[HomeController::class,'getAdminShopPaymentMethods']);
        Route::post('/settings/payment/method/update',[HomeController::class,'updateAdminPaymentMethod']);
        Route::get('/shop/preferences',[HomeController::class,'shopPreferences']);
        Route::post('/shop/preference/update',[HomeController::class,'shopPreferenceUpdate']);
        Route::post('/shop/social/update',[HomeController::class,'shopSocialUpdate']);
        Route::get('/blogs/list',[HomeController::class,'blogsList']);
        Route::get('/blogs/edit/{blog_id}',[HomeController::class,'getBlogById']);
        Route::post('/blogs/add/new',[HomeController::class,'addBlog']);
        Route::post('/blogs/update',[HomeController::class,'updateBlog']);
        Route::post('/blog/section/add/new',[HomeController::class,'addNewBlogSection']);
        Route::post('/blog/add-faq',[FaqController::class,'addBlogFaq']);
        Route::post('/blog/edit-faq',[FaqController::class,'editBlogFaq']);
        Route::post('/blog/delete-faq',[FaqController::class,'deleteBlogFaq']);
        Route::get('/pages/list',[HomeController::class,'getAdminPagesList']);
        Route::get('/page/edit/{page_id}',[HomeController::class,'getAdminPageById']);
        Route::post('/page/add/new',[HomeController::class,'addPage']);
        Route::post('/page/update',[HomeController::class,'updateAdminPage']);
        Route::post('/page/delete',[HomeController::class,'deleteAdminPage']);
        Route::post('/page/section/add/new',[HomeController::class,'addNewPageSection']);
        Route::post('/page/add-faq',[FaqController::class,'addPageFaq']);
        Route::post('/page/edit-faq',[FaqController::class,'editPageFaq']);
        Route::post('/page/delete-faq',[FaqController::class,'deletePageFaq']);
        Route::get('/policies/list',[HomeController::class,'getPolicyList']);
        Route::get('/policies/edit/{policy_id}',[HomeController::class,'getPolicyById']);
        Route::post('/policies/update',[HomeController::class,'updatePolicy']);
        Route::get('/search-all',[HomeController::class,'searchAll']);
        Route::get('/menus/list',[HomeController::class,'getAdminMenusList']);
        Route::post('/menu/add',[HomeController::class,'addAdminMenu']);
        Route::get('/menu/edit/{menu_id}',[HomeController::class,'getAdminMenuById']);
        Route::post('/menu/update',[HomeController::class,'updateAdminMenu']);
        Route::post('/menu/delete',[HomeController::class,'deleteAdminMenu']);
        Route::get('/cartpage/default',[HomeController::class,'getCartPage']);
        Route::post('/cartpage/section/add/new',[HomeController::class,'addNewCartSection']);
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
    });
});

Route::middleware(['auth','resolve.admin.shop'])
    ->get('/{any}', function () {
        return view('sadmin');
    })->where('any','.*');


//Route::get('/{any}', function () {
//    return view('sadmin');
//})->where('any', '.*$');
