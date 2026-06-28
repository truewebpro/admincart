<?php

namespace App\Observers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\Homepage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Section;
use App\Services\ShopCacheService;

class SectionObserver
{
    /**
     * Handle the Section "created" event.
     */
    public function created(Section $section): void
    {
        $this->clearSectionCache($section);
    }

    /**
     * Handle the Section "updated" event.
     */
    public function updated(Section $section): void
    {
        $this->clearSectionCache($section);
    }

    /**
     * Handle the Section "deleted" event.
     */
    public function deleted(Section $section): void
    {
        $this->clearSectionCache($section);
    }

    private function clearSectionCache(Section $section): void
    {
        switch ($section->sectionable_type) {

            case Homepage::class:

                $homepage = Homepage::where(
                    'homepage_id',
                    $section->sectionable_id
                )->first();

                if ($homepage) {
                    ShopCacheService::forgetHeroSections(
                        $homepage->shop_id
                    );

                    ShopCacheService::forgetLazySections(
                        $homepage->shop_id
                    );
                }

                break;

            case Page::class:

                $page = Page::where(
                    'page_id',
                    $section->sectionable_id
                )->first();

                if ($page) {
                    ShopCacheService::forgetPage(
                        $page->shop_id,
                        $page->page_slug
                    );
                }

                break;

            case Blog::class:

                $blog = Blog::where(
                    'blog_id',
                    $section->sectionable_id
                )->first();

                if ($blog) {
                    ShopCacheService::forgetBlog(
                        $blog->shop_id,
                        $blog->blog_slug
                    );
                }

                break;

            case Brand::class:

                $brand = Brand::where(
                    'brand_id',
                    $section->sectionable_id
                )->first();

                if ($brand) {
                    ShopCacheService::forgetBrand(
                        $brand->shop_id,
                        $brand->brand_slug
                    );
                }

                break;

            case Cat::class:

                $cat = Cat::where(
                    'cat_id',
                    $section->sectionable_id
                )->first();

                if ($cat) {
                    ShopCacheService::forgetCat(
                        $cat->shop_id,
                        $cat->cat_slug
                    );
                }

                break;

            case Product::class:

                $product = Product::where(
                    'product_id',
                    $section->sectionable_id
                )->first();

                if ($product) {
                    ShopCacheService::forgetProduct(
                        $product->shop_id,
                        $product->handle
                    );
                }

                break;

            case Cartpage::class:

                $cartpage = Cartpage::where(
                    'cartpage_id',
                    $section->sectionable_id
                )->first();

                if ($cartpage) {
                    ShopCacheService::forgetCartPage(
                        $cartpage->shop_id
                    );
                }

                break;
        }
    }
}
