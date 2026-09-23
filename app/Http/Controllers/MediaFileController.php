<?php

namespace App\Http\Controllers;

use App\Models\MediaFile;
use App\Models\MediaFileAttachment;
use App\Models\Shop;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

        $query = MediaFile::where('shop_id', $shopId)
            ->withCount('attachments');

        if ($search = $request->input('search')) {
            $query->where('filename', 'like', "%{$search}%");
        }

        if ($type = $request->input('file_type')) {
            $query->where('file_type', $type);
        }

        // NEW — reference filter, applied at the QUERY level (has() /
        // doesntHave() translate to a real SQL EXISTS/NOT EXISTS clause),
        // so pagination is correct rather than filtering an already-paginated page.
        $refFilter = $request->input('reference_filter');
        if ($refFilter === 'used') {
            $query->has('attachments');
        } elseif ($refFilter === 'unused') {
            $query->doesntHave('attachments');
        }

        $perPage = min((int) $request->input('per_page', 30), 100);
        $page = $query->orderByDesc('created_at')->paginate($perPage);

        // Breakdown-by-type query, only for files that actually have
        // attachments — skips the (likely many) zero-count files.
        $mediaFileIds = $page->getCollection()->where('attachments_count', '>', 0)->pluck('id');

        $breakdowns = $mediaFileIds->isEmpty()
            ? collect()
            : MediaFileAttachment::whereIn('media_file_id', $mediaFileIds)
                ->select('media_file_id', 'attachable_type', DB::raw('count(*) as cnt'))
                ->groupBy('media_file_id', 'attachable_type')
                ->get()
                ->groupBy('media_file_id');

        $typeLabels = [
            \App\Models\Product::class => 'product',
            \App\Models\Variant::class => 'variant',
            \App\Models\Blog::class    => 'blog',
            \App\Models\Brand::class   => 'brand',
            \App\Models\Cat::class     => 'collection',
            \App\Models\Page::class    => 'page',
            \App\Models\Section::class => 'section',
        ];

        $page->getCollection()->transform(function ($item) use ($breakdowns, $typeLabels) {
            $rows = $breakdowns->get($item->id, collect());
            $item->reference_breakdown = $rows->map(fn ($row) => [
                'type'  => $typeLabels[$row->attachable_type] ?? class_basename($row->attachable_type),
                'count' => $row->cnt,
            ])->values();
            // attachments_count already provided by withCount() above —
            // no need to sum it manually anymore.
            return $item;
        });

        return response()->json(['success' => true, 'items' => $page]);
    }

    public function uploadFromUrl(Request $request)
    {
        $shopId = session('shop_id');
        $shop = Shop::where('shop_id', $shopId)->firstOrFail();

        $validated = $request->validate([
            'url'      => ['required', 'url', 'max:2048'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        // Dedup check up front — same as the Shopify Files import flow.
        $existing = MediaFile::where('shop_id', $shopId)
            ->where('thirdparty_url', $validated['url'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'This URL has already been added to your library.',
                'media_file' => $existing,
            ], 422);
        }

        // Lightweight HEAD request first, just to check Content-Type —
        // avoids downloading the full file twice (once to check, once to
        // store) by deciding up front which storage method to use.
        try {
            $headResponse = Http::withOptions(['allow_redirects' => true])->head($validated['url']);
            $contentType = $headResponse->header('Content-Type') ?: '';
        } catch (\Throwable $e) {
            $contentType = ''; // some servers reject HEAD requests — fall through and let the actual GET attempt decide
        }

        $isImage = str_starts_with($contentType, 'image/');

        try {
            // Deliberately NOT passing width/height — this is a general
            // library addition, not tied to any specific resource that
            // dictates a target size, so the original dimensions are preserved.
            $result = $isImage
                ? ImageService::storeShopifyFileFromUrl($validated['url'], $shop->shop_slug)
                : ImageService::storeRawFileFromUrl($validated['url'], $shop->shop_slug);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Download failed: ' . $e->getMessage()], 502);
        }

        $mediaFile = \App\Services\MediaLibraryService::recordOnly($shopId, $result['path'], [
            'thirdparty_url' => $validated['url'],
            'alt_text'       => $validated['alt_text'] ?? null,
            'mime_type'      => $result['mime_type'] ?? null,
            'width'          => $result['width'] ?? null,
            'height'         => $result['height'] ?? null,
            'file_size'      => $result['file_size'] ?? null,
            'filename'       => $result['filename'] ?? null,
            'file_type'      => $result['file_type'] ?? ($isImage ? 'image' : 'generic'),
        ]);

        return response()->json(['success' => true, 'media_file' => $mediaFile]);
    }

    public function update(Request $request, int $id)
    {
        $shopId = session('shop_id');

        $mediaFile = MediaFile::where('shop_id', $shopId)->findOrFail($id);

        $validated = $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $mediaFile->update($validated);

        return response()->json(['success' => true, 'media_file' => $mediaFile]);
    }

}
