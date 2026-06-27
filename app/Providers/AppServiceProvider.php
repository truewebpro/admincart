<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Setting;
use App\Observers\BlogObserver;
use App\Observers\BrandObserver;
use App\Observers\CartpageObserver;
use App\Observers\CatObserver;
use App\Observers\MenuObserver;
use App\Observers\PageObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductTypeObserver;
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

        View::composer('*', function ($view) {
            $view->with('currentShop', session('shop'));
        });
    }
}
