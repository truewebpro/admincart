<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Brand;
use App\Models\Cartpage;
use App\Models\Cat;
use App\Models\Homepage;
use App\Models\Page;
use App\Models\Product;
use App\Models\Section;
use App\Models\Shop;
use App\Observers\SectionObserver;
use App\Services\ImageService;
use App\Services\MediaLibraryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class SectionController extends Controller
{
    public function addNewHomeSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'homepage_id' => 'required|exists:homepages,homepage_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Homepage::class,
            'sectionable_id' => $validatedData['homepage_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Homepage::class)
                    ->where('sectionable_id', $request->homepage_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function addNewProductSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'product_id' => 'required|exists:products,product_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Product::class,
            'sectionable_id' => $validatedData['product_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Product::class)
                    ->where('sectionable_id', $request->product_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')
        ]);
    }

    public function addNewBrandSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'brand_id' => 'required|exists:brands,brand_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Brand::class,
            'sectionable_id' => $validatedData['brand_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Brand::class)
                    ->where('sectionable_id', $request->brand_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function addNewCatSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'cat_id' => 'required|exists:cats,cat_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Cat::class,
            'sectionable_id' => $validatedData['cat_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Cat::class)
                    ->where('sectionable_id', $request->cat_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function addNewPageSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'page_id' => 'required|exists:pages,page_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Page::class,
            'sectionable_id' => $validatedData['page_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Page::class)
                    ->where('sectionable_id', $request->page_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function addNewCartSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'cartpage_id' => 'required|exists:cartpages,cartpage_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Cartpage::class,
            'sectionable_id' => $validatedData['cartpage_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Cartpage::class)
                    ->where('sectionable_id', $request->cartpage_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);

        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function addNewBlogSection(Request $request)
    {
        $validatedData = $request->validate([
            'stype_id' => 'required|exists:stypes,stype_id',
            'blog_id' => 'required|exists:blogs,blog_id',
        ]);
        $section = Section::create([
            'stype_id' => $validatedData['stype_id'],
            'sectionable_type' => Blog::class,
            'sectionable_id' => $validatedData['blog_id'],
            'section_json' => $request->section_json,
            'sort_order' => Section::where('sectionable_type', Blog::class)
                    ->where('sectionable_id', $request->blog_id)
                    ->max('sort_order') + 1,
            'section_status' => 'show',
        ]);
        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        return response()->json([
            'success' => true,
            'message' => 'Section added successfully',
            'section' => $section->load('stype')

        ]);
    }

    public function updateAddedSection(Request $request,$section_id)
    {
        $validated = $request->validate([
            'section_json'   => 'required|array',
            'sort_order'     => 'nullable|integer',
            'section_status' => 'nullable|in:show,hide',
        ]);
        $section = Section::findOrFail($section_id);
        $section->section_json   = $validated['section_json'];
        $section->sort_order     = $validated['sort_order'] ?? $section->sort_order;
        $section->section_status = $validated['section_status'] ?? $section->section_status;
        $section->save();

        // images are actually in this section now.
        MediaLibraryService::syncSectionImageAttachments(session('shop_id'), $section);

        // Clear related cache
        app(SectionObserver::class)
            ->updated($section);

        return response()->json([
            'success' => true,
            'message' => "Updated Successfully",
            'section' => $section->fresh()
        ]);
    }

    public function moveSectionUp($section_id)
    {
        $section = Section::findOrFail($section_id);
        $above = Section::where('sectionable_id',$section->sectionable_id)
            ->where('sectionable_type',$section->sectionable_type)
            ->where('sort_order', $section->sort_order - 1)
            ->first();
        if($above){
            $above->sort_order = $section->sort_order;
            $above->save();
            $section->sort_order = $section->sort_order - 1;
            $section->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Section moved up successfully',
            'section' => $section->fresh()
        ]);
    }

    public function moveSectionDown($section_id)
    {
        $section = Section::findOrFail($section_id);
        $below = Section::where('sectionable_id',$section->sectionable_id)
            ->where('sectionable_type',$section->sectionable_type)
            ->where('sort_order', $section->sort_order + 1)
            ->first();
        if($below){
            $below->sort_order = $section->sort_order;
            $below->save();
            $section->sort_order = $section->sort_order + 1;
            $section->save();
        }
        return response()->json([
            'success' => true,
            'message' => 'Section moved down successfully',
            'section' => $section->fresh()
        ]);
    }

    public function hideOrShowSection($section_id)
    {
        $section = Section::findOrFail($section_id);
        $existingStatus = Section::where('section_status','=',$section->section_status)->first();
        if($existingStatus->section_status == "show"){
            $section->section_status = "hide";
            $section->save();
            // Clear related cache
            app(\App\Observers\SectionObserver::class)
                ->updated($section);
            return response()->json([
                'success' => true,
                'message' => 'Section hide successfully',
                'section' => $section->fresh()
            ]);
        } elseif ($existingStatus->section_status == "hide"){
            $section->section_status = "show";
            $section->save();
            // Clear related cache
            app(\App\Observers\SectionObserver::class)
                ->updated($section);
            return response()->json([
                'success' => true,
                'message' => 'Section shown successfully',
                'section' => $section->fresh()
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Section notupdate',
                'section' => $section->fresh()
            ]);
        }

    }

    public function deleteAddedSection($section_id)
    {
        $section = Section::findOrFail($section_id);
        $sectionable_id = $section->sectionable_id;
        $sectionable_type = $section->sectionable_type;
        $deletedSortOrder = $section->sort_order;
        $section->delete();

        Section::where('sectionable_id', $sectionable_id)
            ->where('sectionable_type', $sectionable_type)
            ->where('sort_order', '>', $deletedSortOrder)
            ->decrement('sort_order');

        return response()->json([
            'success' => true,
            'message' => "Deleted Successfully",
        ]);
    }

    public function getVideoUploadUrl(Request $request)
    {
        $file = $request->file('video');
        $extension = $file->getClientOriginalExtension();
        $filename = 'section_video_'.uniqid().'.'.$extension;
        $vpath = 'sections/'.$filename;
        Storage::disk('s3')->put($vpath,file_get_contents($file->getRealPath()));
        return response()->json([
            'success' => true,
            'url' => $vpath,
        ]);
    }

    public function getHimageUploadUrl(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->firstOrFail();

        $dimensions = [
            'browse_collection'    => [750, 300],
            'slideshow'            => [1800, 600],
            'slideshow_mobile'     => [600, 600], // NEW distinct key — see fix below
            'featured_links'       => [800, 1000],
            'featured_collections' => [800, 1000],
            'popular_range'        => [100, 100],
            'image_with_text'      => [700, 500],
        ];

        $isMobile = $request->hasFile('mimage');
        $file = $isMobile ? $request->file('mimage') : $request->file('image');

        if (!$file) {
            return response()->json(['success' => false, 'message' => 'No file provided.'], 422);
        }

        $stype = $request->stype ?? 'section';
        $dimensionKey = ($stype === 'slideshow' && $isMobile) ? 'slideshow_mobile' : $stype;
        [$width, $height] = $dimensions[$dimensionKey] ?? [1800, 1800];

        $imageMeta = ImageService::storeUploadedFile($file, $shop->shop_slug, $width, $height);

        MediaLibraryService::recordOnlyFromResult($shopId, $imageMeta);

        return response()->json([
            'success' => true,
            'url' => $imageMeta['path'],
        ]);
    }
}
