<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\MediaFileAttachment;

class MediaLibraryService
{
    /**
     * Shared find-or-create logic, used by both recordAndAttach()
     * (creating a resource that has an image, e.g. a blog) and
     * recordOnly() (importing into the general library with nothing
     * to attach to yet, e.g. from the Shopify Files browser).
     */
    protected static function findOrCreateMediaFile(int $shopId, string $path, array $meta): MediaFile
    {
        $mediaFile = null;

        if (! empty($meta['thirdparty_url'])) {
            $mediaFile = MediaFile::where('shop_id', $shopId)
                ->where('thirdparty_url', $meta['thirdparty_url'])
                ->first();
        }

        if (! $mediaFile) {
            $mediaFile = MediaFile::where('shop_id', $shopId)
                ->where('path', $path)
                ->first();
        }

        if ($mediaFile) {
            $backfill = [];

            foreach (['thirdparty_id', 'thirdparty_url', 'alt_text', 'mime_type', 'width', 'height', 'file_size', 'filename'] as $field) {
                if (is_null($mediaFile->{$field}) && ! empty($meta[$field])) {
                    $backfill[$field] = $meta[$field];
                }
            }

            if ($backfill) {
                $mediaFile->update($backfill);
            }

            return $mediaFile;
        }

        return MediaFile::create([
            'shop_id'        => $shopId,
            'thirdparty_id'  => $meta['thirdparty_id'] ?? null,
            'thirdparty_url' => $meta['thirdparty_url'] ?? null,
            'file_type'      => $meta['file_type'] ?? 'image',
            'filename'       => $meta['filename'] ?? basename(parse_url($path, PHP_URL_PATH) ?? $path),
            'path'           => $path,
            'alt_text'       => $meta['alt_text'] ?? null,
            'mime_type'      => $meta['mime_type'] ?? null,
            'width'          => $meta['width'] ?? null,
            'height'         => $meta['height'] ?? null,
            'file_size'      => $meta['file_size'] ?? null,
        ]);
    }

    /**
     * Used when creating a resource that HAS an image at creation time
     * (blogs, products, collections) — creates/dedupes the MediaFile
     * AND attaches it to that resource in one call.
     */
    public static function recordAndAttach(
        int $shopId,
        string $path,
        $attachable,
        array $meta = [],
        ?string $mediaFor = null
    ): MediaFile {
        $mediaFile = self::findOrCreateMediaFile($shopId, $path, $meta);

        $existingAttachment = MediaFileAttachment::where('media_file_id', $mediaFile->id)
            ->where('attachable_type', get_class($attachable))
            ->where('attachable_id', $attachable->getKey())
            ->where('media_for', $mediaFor)
            ->first();

        if (! $existingAttachment) {
            MediaFileAttachment::create([
                'media_file_id'   => $mediaFile->id,
                'attachable_type' => get_class($attachable),
                'attachable_id'   => $attachable->getKey(),
                'media_for'       => $mediaFor,
            ]);
        }

        return $mediaFile;
    }

    /**
     * Used when importing into the GENERAL library with nothing to
     * attach to yet — e.g. pulling a file in from Shopify's Files
     * browser. No attachment is created; the file just becomes
     * available for later selection via the picker.
     */
    public static function recordOnly(int $shopId, string $path, array $meta = []): MediaFile
    {
        return self::findOrCreateMediaFile($shopId, $path, $meta);
    }

}
