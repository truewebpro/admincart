<?php

namespace App\Http\Controllers;

use App\Models\PageSetting;
use Illuminate\Http\Request;

class PageSettingController extends Controller
{
    public function getPageSetting(Request $request)
    {
        $shopId = session('shop_id');

        $data = $request->validate([
            'page_type' => 'required|in:products,cats,brands,blogs,pages,policies,homepage',
            'slug' => 'required|string|max:255',
        ]);

        $page = PageSetting::firstOrCreate(
            [
                'shop_id' => $shopId,
                'page_type' => $data['page_type'],
            ],
            [
                'slug' => $data['slug'],
                'status' => true,
            ]
        );

        return response()->json([
            'success' => true,
            'page' => $page,
        ]);
    }

    public function updatePageSetting(Request $request)
    {
        $shopId = session('shop_id');

        $data = $request->validate([
            'id' => 'required|integer',
            'slug' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'og_image' => 'nullable|string|max:255',
            'status' => 'required|boolean',
        ]);

        $page = PageSetting::where('shop_id', $shopId)
            ->where('id', $data['id'])
            ->firstOrFail();

        $page->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Page settings updated successfully.',
            'page' => $page,
        ]);
    }

    public function getPageSettingBySlug(Request $request)
    {
        $shopId = $request->shop_id;
        $data = $request->validate([
            'page_type' => 'required|in:products,cats,brands,blogs,pages,policies,homepage',
        ]);
        $pageType = $data['page_type'];

        $page = PageSetting::where('page_type', '=', $pageType)
            ->where('shop_id','=',$shopId)
            ->where('status','=',true)
            ->first();

        return response()->json([
            'success' => true,
            'page' => $page,
        ]);
    }
}
