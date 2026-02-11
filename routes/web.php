<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth','resolve.admin.shop'])->group(function(){
    Route::prefix('sadmin')->group(function(){
        Route::get('/dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
        Route::get('/shop/business',[HomeController::class,'getShopBusiness']);
        Route::post('/shop/business/update',[HomeController::class,'updateShopBusiness']);
        Route::post('/shop/setting/update',[HomeController::class,'updateShopSetting']);
        Route::get('/products/export',[HomeController::class,'shopProductExport']);
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
        Route::get('/shop',[HomeController::class,'getShopDetail']);
        Route::get('/users',[HomeController::class,'allUsers']);
        Route::get('/carts',[HomeController::class,'allCarts']);
        Route::get('/cart/{cart_id}',[HomeController::class,'getCartById']);
        Route::get('/orders', [HomeController::class, 'allOrders'])->name('all-orders');
        Route::get('/order/{order_id}',[HomeController::class,'getOrderById']);
        Route::post('/order/update',[HomeController::class,'updateAdminOrder']);
        Route::post('/order/sendtosendcloud/single',[HomeController::class,'sendToSendCloudSingle']);
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
        Route::get('/categories',[HomeController::class,'allCats']);
        Route::get('/cats/new',[HomeController::class,'addCatView']);
        Route::post('/cat/new',[HomeController::class,'addCatNew']);
        Route::post('/cat/section/add/new',[HomeController::class,'addNewCatSection']);
        Route::post('/products/filter-ids',[HomeController::class,'filterProductIds']);
        Route::get('/categories/{cat_id}',[HomeController::class,'getCat']);
        Route::post('/categories/related/add',[HomeController::class,'addRelatedCat']);
        Route::post('/cat/mupdate/{cat_id}',[HomeController::class,'updateManualCatbyId']);
        Route::post('/cat/supdate/{cat_id}',[HomeController::class,'updateSmartCatbyId']);
        Route::get('/cat/delete/{cat_id}',[HomeController::class,'deleteCategorybyId']);
        Route::get('/inventory',[HomeController::class,'allInventory']);
        Route::post('/inventory/update',[HomeController::class,'updateInventory']);
        Route::get('/customers',[HomeController::class,'allCustomers']);
        Route::get('/customer/details/{customer_id}',[HomeController::class,'getCustomerByID']);
        Route::get('/ptypes',[HomeController::class,'allPtypes']);
        Route::post('/ptype/update',[HomeController::class,'updatePtype']);
        Route::post('/ptype/delete',[HomeController::class,'deletePtypebyId']);
        Route::get('/brands',[HomeController::class,'allBrands']);
        Route::get('/brand/{brand_id}',[HomeController::class,'brandById']);
        Route::post('/brand/update',[HomeController::class,'updateBrand']);
        Route::post('/brand/delete',[HomeController::class,'deleteBrand']);
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
        Route::get('/shops/homebanners',[HomeController::class,'homeBanners']);
        Route::get('/settings/shipping',[HomeController::class,'shippingSettings']);
        Route::post('/settings/shipping/update',[HomeController::class,'updateOrAddAdminShipMethod']);
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
        Route::get('/pages/list',[HomeController::class,'getAdminPagesList']);
        Route::get('/page/edit/{page_id}',[HomeController::class,'getAdminPageById']);
        Route::post('/page/add/new',[HomeController::class,'addPage']);
        Route::post('/page/update',[HomeController::class,'updateAdminPage']);
        Route::post('/page/delete',[HomeController::class,'deleteAdminPage']);
        Route::post('/page/section/add/new',[HomeController::class,'addNewPageSection']);
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
        Route::get('/footer',[HomeController::class,'getShopFooter']);
        Route::post('/footer/update',[HomeController::class,'updateFooter']);
        Route::get('/subscribe/section',[HomeController::class,'getShopSubscribeSection']);
        Route::post('/subscribe/section/update',[HomeController::class,'updateShopSubscribeSection']);
        Route::get('/search/alinks',[HomeController::class,'getAllLinks']);
        Route::get('/coupons/list',[HomeController::class,'getAdminCouponsList']);
        Route::get('/reviews/list',[HomeController::class,'getAdminReviewsList']);
        Route::post('/review/update',[HomeController::class,'updateAdminProductReview']);
        Route::post('/generate/ai',[HomeController::class,'allContentFromAi']);
    });
});

Route::middleware(['auth','resolve.admin.shop'])
    ->get('/{any}', function () {
        return view('sadmin');
    })->where('any','.*');


//Route::get('/{any}', function () {
//    return view('sadmin');
//})->where('any', '.*$');
