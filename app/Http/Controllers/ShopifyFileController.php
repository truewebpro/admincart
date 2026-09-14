<?php

namespace App\Http\Controllers;

use App\Exceptions\MissingShopifyScopeException;
use App\Models\MediaFile;
use App\Models\Shop;
use App\Models\ShopifyShop;
use App\Services\ImageService;
use App\Services\MediaLibraryService;
use App\Services\ShopifyFileService;
use Illuminate\Http\Request;

class ShopifyFileController extends Controller
{
    public function live(Request $request, int $shopId)
    {
        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyFileService($shopifyShop);

        try {
            $result = $service->getFiles(
                $request->input('cursor'),
                $request->input('direction', 'next'),
                $request->boolean('only_unused', true),
                (int) $request->input('limit', 20)
            );
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        return response()->json([
            'success'   => true,
            'files'     => $result['files'],
            'page_info' => $result['page_info'],
        ]);
    }

    /**
     * Single, explicit import into the general library. No bulk — same
     * deliberate "pick exactly what you want" philosophy as everything
     * else this session. Does NOT attach to any resource; the file
     * just becomes available for later selection via the picker.
     */
    public function create(Request $request, int $shopId)
    {
        $validated = $request->validate([
            'file_id'   => ['required', 'string'],   // full GID, e.g. gid://shopify/MediaImage/123
            'file_type' => ['required', 'in:MediaImage,GenericFile'],
        ]);

        $shopifyShop = ShopifyShop::where('shop_id', $shopId)->firstOrFail();
        $service = new ShopifyFileService($shopifyShop);

        try {
            $file = $service->getFileById($validated['file_id']);
        } catch (MissingShopifyScopeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'required_scope' => $e->requiredScope,
            ], 403);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }

        $isImage = $validated['file_type'] === 'MediaImage';
        $sourceUrl = $isImage ? ($file['image']['url'] ?? null) : ($file['url'] ?? null);

        if (! $sourceUrl) {
            return response()->json(['success' => false, 'message' => 'No file URL found on this item.'], 422);
        }

        // Dedup check up front — avoid a pointless re-download if this
        // exact file was already imported before.
        $existing = MediaFile::where('shop_id', $shopId)->where('thirdparty_url', $sourceUrl)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This file is already in your library.',
                'media_file' => $existing,
            ], 422);
        }

        $shop = Shop::where('shop_id', $shopId)->firstOrFail();

        try {
            $result = $isImage
                ? ImageService::storeShopifyFileFromUrl($sourceUrl, $shop->shop_slug)
                : ImageService::storeRawFileFromUrl($sourceUrl, $shop->shop_slug);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Download failed: ' . $e->getMessage()], 502);
        }

        $mediaFile = MediaLibraryService::recordOnly($shopId, $result['path'], [
            'thirdparty_url' => $sourceUrl,
            'alt_text'       => $file['alt'] ?? null,
            'mime_type'      => $result['mime_type'] ?? $file['mimeType'] ?? null,
            'width'          => $file['image']['width'] ?? $result['width'] ?? null,
            'height'         => $file['image']['height'] ?? $result['height'] ?? null,
            'file_size'      => $result['file_size'] ?? null,
            'filename'       => $result['filename'] ?? null,
            'file_type'      => $isImage ? 'image' : 'generic',
        ]);

        return response()->json(['success' => true, 'media_file' => $mediaFile]);
    }
}
