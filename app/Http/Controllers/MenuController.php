<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Cat;
use App\Models\Menu;
use App\Services\CacheKeys;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function getHomeMenu(Request $request,$shopname)
    {
        $shopId = $request->shop_id;

        $menu = Cache::remember(
            CacheKeys::mainMenu($shopId),
            now()->addHours(12),
            function () use ($shopId) {
                return Menu::where('shop_id', $shopId)
                    ->where('menu_slug', 'main_menu')
                    ->first();
            }
        );

        return response()->json([
            'status' => !is_null($menu),
            'mitems' => $menu?->mitems,
            'shop_id' => $shopId,
        ]);
    }

    //Admin Routes
    public function getAdminMenusList()
    {
        $shopId = session('shop_id');
        $menus = Menu::where('shop_id', $shopId)
            ->get();
        return response()->json([
            'success' => !$menus->isEmpty(),
            'menus' => $menus->isEmpty() ? null : $menus,
        ]);
    }

    public function addAdminMenu(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'menu_name' => 'required|string',
            'menu_slug' => 'required|string',
            'menu_status' => 'required|in:active,inactive',
            'menu_items' => 'required|array',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::create([
                'menu_name' => $validatedData['menu_name'],
                'menu_slug' => $validatedData['menu_slug'],
                'menu_status' => $validatedData['menu_status'],
                'mitems'=> $validatedData['menu_items'],
                'shop_id' => $shopId,
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Successfully created menu.',
                'menu' => $menu,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAdminMenuById($menu_id)
    {
        $shopId = session('shop_id');
        $menu = Menu::where('shop_id', $shopId)
            ->where('menus.menu_id', $menu_id)
            ->first();
        $brands = Brand::where('shop_id','=',$shopId)->get();
        $cats = Cat::where('shop_id','=',$shopId)->get();
        if($menu){
            return response()->json([
                'success' => true,
                'message' => 'Menu with its Items',
                'menu' => $menu,
                'brands' => $brands,
                'cats' => $cats,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found.',
                'menu' => null,
            ]);
        }

    }

    public function updateAdminMenu(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'menu_id' => 'required|integer',
            'menu_name' => 'required|string',
            'menu_slug' => 'required|string',
            'menu_status' => 'required|in:active,inactive',
            'menu_items' => 'required|array',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::where('shop_id', $shopId)
                ->findOrFail($validatedData['menu_id']);

            $menu->update([
                'menu_name' => $validatedData['menu_name'],
                'menu_slug' => $validatedData['menu_slug'],
                'menu_status' => $validatedData['menu_status'],
                'mitems'=> $validatedData['menu_items'],
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Menu updated successfully.',
                'menu' => $menu,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteAdminMenu(Request $request)
    {
        $shopId = session('shop_id');
        $validatedData = $request->validate([
            'menu_id' => 'required|integer',
        ]);
        DB::beginTransaction();
        try {
            $menu = Menu::where('shop_id', $shopId)
                ->findOrFail($validatedData['menu_id']);
            $menu->delete();
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Menu deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
