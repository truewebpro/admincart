<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Business;
use App\Models\BusinessShop;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\Footer;
use App\Models\Homepage;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Policy;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Searchtag;
use App\Models\Setting;
use App\Observers\AnnouncementObserver;
use App\Observers\BlogObserver;
use App\Observers\BrandObserver;
use App\Observers\BusinessObserver;
use App\Observers\BusinessShopObserver;
use App\Observers\CartpageObserver;
use App\Observers\CatObserver;
use App\Observers\FooterObserver;
use App\Observers\HomepageObserver;
use App\Observers\MenuObserver;
use App\Observers\PageObserver;
use App\Observers\PolicyObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductTypeObserver;
use App\Observers\SearchtagObserver;
use App\Observers\SettingObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use App\Models\Shop;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::useCustomerModel(Shop::class);
        Product::observe(ProductObserver::class);
        Cat::observe(CatObserver::class);
        Brand::observe(BrandObserver::class);
        Blog::observe(BlogObserver::class);
        Page::observe(PageObserver::class);
        Setting::observe(SettingObserver::class);
        Menu::observe(MenuObserver::class);
        ProductType::observe(ProductTypeObserver::class);
        Cartpage::observe(CartpageObserver::class);
        Policy::observe(PolicyObserver::class);
        Homepage::observe(HomepageObserver::class);
        Footer::observe(FooterObserver::class);
        Business::observe(BusinessObserver::class);
        BusinessShop::observe(BusinessShopObserver::class);
        Announcement::observe(AnnouncementObserver::class);
        Searchtag::observe(SearchtagObserver::class);

        View::composer('*', function ($view) {
            $view->with('currentShop', session('shop'));
        });
    }
}
