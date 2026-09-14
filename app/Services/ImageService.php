<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public static function storeUploadedFile(UploadedFile $file, string $shopSlug, ?int $width = null, ?int $height = null): array
    {
        $extension = $file->getClientOriginalExtension() ?: 'png';
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = self::safeSlug($originalName);
        $filename = $safeName . '-' . uniqid() . '.' . $extension;

        // {shop_slug}/cdn/shop/files/{filename} — matches Shopify's own
        // path structure exactly, scoped by shop_slug in storage to
        // prevent collisions across your 21+ tenants sharing one bucket.
        // CloudFront (configured separately, outside this codebase) is
        // what makes the PUBLIC-facing URL show just
        // {shop-domain}/cdn/shop/files/{filename} with no visible slug.
        $fpath = $shopSlug . '/cdn/shop/files/' . $filename;

        $mimeType = $file->getMimeType();
        $isImage = str_starts_with($mimeType, 'image/');

        $width_out = null;
        $height_out = null;

        if ($isImage) {
            $img = Image::make($file->getRealPath());

            if ($width && $height) {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $width_out = $img->width();
            $height_out = $img->height();

            Storage::disk('s3')->put($fpath, (string) $img->encode());
        } else {
            Storage::disk('s3')->put($fpath, file_get_contents($file->getRealPath()));
        }

        return [
            'path'      => $fpath,
            'filename'  => $filename,
            'mime_type' => $mimeType,
            'width'     => $width_out,
            'height'    => $height_out,
            'file_size' => $file->getSize(),
            'file_type' => $isImage ? 'image' : 'generic',
        ];
    }

    protected static function safeSlug(string $name, int $maxLength = 80): string
    {
        return Str::limit(Str::slug($name), $maxLength, '');
    }

    public static function storeShopifyFileFromUrl(string $url, string $shopSlug, ?int $width = null, ?int $height = null): array
    {
        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to download image: {$url}");
        }

        $urlPath = parse_url($url, PHP_URL_PATH); // strips ?v=... automatically
        $rawFilename = basename($urlPath);
        $extension = pathinfo($rawFilename, PATHINFO_EXTENSION) ?: 'jpg';
        $nameWithoutExt = pathinfo($rawFilename, PATHINFO_FILENAME);

        $safeName = self::safeSlug($nameWithoutExt, 150);
        $filename = $safeName . '.' . $extension;

        $fpath = $shopSlug . '/cdn/shop/files/' . $filename;

        $mimeType = $response->header('Content-Type') ?: 'image/jpeg';
        $isImage = str_starts_with($mimeType, 'image/');

        $widthOut = null;
        $heightOut = null;

        if ($isImage) {
            $img = Image::make($response->body());

            if ($width && $height) {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $widthOut = $img->width();
            $heightOut = $img->height();

            Storage::disk('s3')->put($fpath, (string) $img->encode());
        } else {
            Storage::disk('s3')->put($fpath, $response->body());
        }

        return [
            'path'      => $fpath,
            'filename'  => $filename,
            'mime_type' => $mimeType,
            'width'     => $widthOut,
            'height'    => $heightOut,
            'file_size' => strlen($response->body()),
            'file_type' => $isImage ? 'image' : 'generic',
        ];
    }

    public static function storeRawFileFromUrl(string $url, string $shopSlug): array
    {
        $response = Http::timeout(30)->get($url);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to download file: {$url}");
        }

        $urlPath = parse_url($url, PHP_URL_PATH);
        $rawFilename = basename($urlPath);
        $extension = pathinfo($rawFilename, PATHINFO_EXTENSION) ?: 'bin';
        $nameWithoutExt = pathinfo($rawFilename, PATHINFO_FILENAME);

        $safeName = self::safeSlug($nameWithoutExt, 150);
        $filename = $safeName . '.' . $extension;
        $fpath = $shopSlug . '/cdn/shop/files/' . $filename;

        Storage::disk('s3')->put($fpath, $response->body());

        return [
            'path'      => $fpath,
            'filename'  => $filename,
            'mime_type' => $response->header('Content-Type') ?: 'application/octet-stream',
            'width'     => null,
            'height'    => null,
            'file_size' => strlen($response->body()),
            'file_type' => 'generic',
        ];
    }
}
