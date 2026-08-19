<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Blog;
use App\Models\BlogFaq;
use App\Models\Brand;
use App\Models\BrandFaq;
use App\Models\Business;
use App\Models\BusinessShop;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\CatFaq;
use App\Models\CustomerShop;
use App\Models\Footer;
use App\Models\Highlight;
use App\Models\Homepage;
use App\Models\HomepageFaq;
use App\Models\Menu;
use App\Models\Page;
use App\Models\PageFaq;
use App\Models\Policy;
use App\Models\Preference;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductType;
use App\Models\Proreview;
use App\Models\RelatedCat;
use App\Models\Searchbrand;
use App\Models\Searchcat;
use App\Models\Searchtag;
use App\Models\Section;
use App\Models\Setting;
use App\Models\ShipMethod;
use App\Models\ShopPaymentMethod;
use App\Models\Specific;
use App\Models\Variant;
use App\Observers\AnnouncementObserver;
use App\Observers\BlogFaqObserver;
use App\Observers\BlogObserver;
use App\Observers\BrandFaqObserver;
use App\Observers\BrandObserver;
use App\Observers\BusinessObserver;
use App\Observers\BusinessShopObserver;
use App\Observers\CartpageObserver;
use App\Observers\CatFaqObserver;
use App\Observers\CatObserver;
use App\Observers\CustomerShopObserver;
use App\Observers\FooterObserver;
use App\Observers\HighlightObserver;
use App\Observers\HomepageFaqObserver;
use App\Observers\HomepageObserver;
use App\Observers\MenuObserver;
use App\Observers\PageFaqObserver;
use App\Observers\PageObserver;
use App\Observers\PolicyObserver;
use App\Observers\PreferenceObserver;
use App\Observers\ProductFaqObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductTypeObserver;
use App\Observers\ProreviewObserver;
use App\Observers\RelatedCatObserver;
use App\Observers\SearchbrandObserver;
use App\Observers\SearchcatObserver;
use App\Observers\SearchtagObserver;
use App\Observers\SectionObserver;
use App\Observers\SettingObserver;
use App\Observers\ShipMethodObserver;
use App\Observers\ShopPaymentMethodObserver;
use App\Observers\SpecificObserver;
use App\Observers\VariantObserver;
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
        Variant::observe(VariantObserver::class);
        Highlight::observe(HighlightObserver::class);
        Specific::observe(SpecificObserver::class);
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
        Searchbrand::observe(SearchbrandObserver::class);
        Searchcat::observe(SearchcatObserver::class);
        ShipMethod::observe(ShipMethodObserver::class);
        ShopPaymentMethod::observe(ShopPaymentMethodObserver::class);
        Preference::observe(PreferenceObserver::class);
        Section::observe(SectionObserver::class);
        ProductFaq::observe(ProductFaqObserver::class);
        BlogFaq::observe(BlogFaqObserver::class);
        PageFaq::observe(PageFaqObserver::class);
        Proreview::observe(ProreviewObserver::class);
        CatFaq::observe(CatFaqObserver::class);
        BrandFaq::observe(BrandFaqObserver::class);
        HomepageFaq::observe(HomepageFaqObserver::class);
        RelatedCat::observe(RelatedCatObserver::class);
        CustomerShop::observe(CustomerShopObserver::class);

        View::composer('*', function ($view) {
            $view->with('currentShop', session('shop'));
        });
    }
}
