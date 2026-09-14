<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\Shop;
use App\Services\ImageService;
use Illuminate\Http\Request;

class MediaFileController extends Controller
{
    /**
     * Manual upload — a real file from the user's device, nothing to
     * do with Shopify. Creates a MediaFile row directly, no attachment
     * yet (attaching to a specific resource is a separate step).
     */
    public function upload(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->firstOrFail();

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $result = ImageService::storeUploadedFile($validated['file'], $shop->shop_slug);

        $mediaFile = MediaFile::create([
            'shop_id'    => $shopId,
            'file_type'  => $result['file_type'],
            'filename'   => $result['filename'],
            'path'       => $result['path'],
            'alt_text'   => $validated['alt_text'] ?? null,
            'mime_type'  => $result['mime_type'],
            'width'      => $result['width'],
            'height'     => $result['height'],
            'file_size'  => $result['file_size'],
        ]);

        return response()->json(['success' => true, 'media_file' => $mediaFile]);
    }


    /**
     * Browse the local library — powers a picker UI. Purely local,
     * never hits Shopify (that's the separate live-browsing feature
     * we'll build next).
     */
    public function index(Request $request)
    {
        $shopId = session('shop_id');

        $query = MediaFile::where('shop_id', $shopId);

        if ($search = $request->input('search')) {
            $query->where('filename', 'like', "%{$search}%");
        }

        if ($type = $request->input('file_type')) {
            $query->where('file_type', $type);
        }

        $perPage = min((int) $request->input('per_page', 30), 100);
        $page = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json(['success' => true, 'items' => $page]);
    }
}
