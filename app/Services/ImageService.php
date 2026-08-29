<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageService
{
    /**
     * Download a remote image (e.g. Shopify CDN url), resize/encode it,
     * and store it on S3. Used across every part of the Shopify import
     * pipeline that handles images — products, blog articles, and
     * anything added later — so this download/resize/upload sequence
     * only ever exists in ONE place.
     *
     * Defaults ($folder='products', 1000x1000) match the original
     * product-image behavior exactly, so existing calls that don't pass
     * these params keep working identically. Pass different values for
     * other resources — e.g. storeFromUrl($url, 'blogs', 1200, 800).
     *
     * @return string  the S3 path (e.g. products/xxxx.png or blogs/xxxx.png)
     *
     * @throws \RuntimeException  if the download fails — callers should
     *                            catch this if a failed image shouldn't
     *                            block the rest of the operation (e.g.
     *                            article creation proceeding without an image).
     */
    public static function storeFromUrl(
        string $url,
        string $folder = 'products',
        int $width = 1000,
        int $height = 1000
    ): string {
        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to download image: {$url}");
        }

        $filename = rtrim($folder, '/') . '_image-' . time() . uniqid() . '.png';

        $img = Image::make($response->body())->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        $fpath = rtrim($folder, '/') . '/' . $filename;

        Storage::disk('s3')->put($fpath, (string) $img->encode());

        return $fpath;
    }
}
