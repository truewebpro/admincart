<?php

namespace App\Services;

use App\Models\MediaFile;
use App\Models\MediaFileAttachment;

class MediaLibraryService
{
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

    public static function recordAndAttach(int $shopId, string $path, $attachable, array $meta = [], ?string $mediaFor = null): MediaFile
    {
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

    public static function recordOnly(int $shopId, string $path, array $meta = []): MediaFile
    {
        return self::findOrCreateMediaFile($shopId, $path, $meta);
    }

    public static function recordAndAttachFromResult(int $shopId, array $imageResult, $attachable, ?string $mediaFor = null, array $extra = []): MediaFile
    {
        if (empty($imageResult['path'])) {
            throw new \InvalidArgumentException('recordAndAttachFromResult(): $imageResult has no path.');
        }

        $meta = array_merge([
            'mime_type' => $imageResult['mime_type'] ?? null,
            'width'     => $imageResult['width'] ?? null,
            'height'    => $imageResult['height'] ?? null,
            'file_size' => $imageResult['file_size'] ?? null,
            'filename'  => $imageResult['filename'] ?? null,
            'file_type' => $imageResult['file_type'] ?? 'image',
        ], $extra);

        return self::recordAndAttach($shopId, $imageResult['path'], $attachable, $meta, $mediaFor);
    }

    public static function recordOnlyFromResult(int $shopId, array $imageResult, array $extra = []): MediaFile
    {
        if (empty($imageResult['path'])) {
            throw new \InvalidArgumentException('recordOnlyFromResult(): $imageResult has no path.');
        }

        $meta = array_merge([
            'mime_type' => $imageResult['mime_type'] ?? null,
            'width'     => $imageResult['width'] ?? null,
            'height'    => $imageResult['height'] ?? null,
            'file_size' => $imageResult['file_size'] ?? null,
            'filename'  => $imageResult['filename'] ?? null,
            'file_type' => $imageResult['file_type'] ?? 'image',
        ], $extra);

        return self::recordOnly($shopId, $imageResult['path'], $meta);
    }

    public static function replaceFeaturedImage(int $shopId, $attachable, string $flatColumn, string $path,
        array $meta = [],
        string $mediaFor = 'featured'
    ): MediaFile
    {
        $mediaFile = self::findOrCreateMediaFile($shopId, $path, $meta);

        MediaFileAttachment::where('attachable_type', get_class($attachable))
            ->where('attachable_id', $attachable->getKey())
            ->where('media_for', $mediaFor)
            ->delete();

        MediaFileAttachment::create([
            'media_file_id'   => $mediaFile->id,
            'attachable_type' => get_class($attachable),
            'attachable_id'   => $attachable->getKey(),
            'media_for'       => $mediaFor,
        ]);

        $attachable->update([$flatColumn => $mediaFile->path]);

        return $mediaFile;
    }

    public static function replaceFeaturedImageFromResult(int $shopId, $attachable, string $flatColumn, array $imageResult,
        array $extra = [],
        string $mediaFor = 'featured'
    ): MediaFile
    {
        if (empty($imageResult['path'])) {
            throw new \InvalidArgumentException('replaceFeaturedImageFromResult(): $imageResult has no path.');
        }

        $meta = array_merge([
            'mime_type' => $imageResult['mime_type'] ?? null,
            'width'     => $imageResult['width'] ?? null,
            'height'    => $imageResult['height'] ?? null,
            'file_size' => $imageResult['file_size'] ?? null,
            'filename'  => $imageResult['filename'] ?? null,
            'file_type' => $imageResult['file_type'] ?? 'image',
        ], $extra);

        return self::replaceFeaturedImage($shopId, $attachable, $flatColumn, $imageResult['path'], $meta, $mediaFor);
    }

    public static function resolveImageAlts(array $sectionJson, int $shopId): array
    {
        // First pass: collect every *_url value anywhere in the structure.
        $paths = [];
        self::collectUrlValues($sectionJson, $paths);

        if (empty($paths)) {
            return $sectionJson;
        }

        // One batch query, regardless of how many images this section has.
        $altsByPath = MediaFile::where('shop_id', $shopId)
            ->whereIn('path', array_unique($paths))
            ->pluck('alt_text', 'path');

        // Second pass: inject the matching *_alt sibling key wherever a
        // *_url key's value had a real alt text found.
        self::injectAltValues($sectionJson, $altsByPath);

        return $sectionJson;
    }

    protected static function collectUrlValues($data, array &$paths): void
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                self::collectUrlValues($value, $paths);
            } elseif (is_string($key) && str_ends_with($key, '_url') && ! empty($value)) {
                $paths[] = $value;
            }
        }
    }

    protected static function injectAltValues(array &$data, $altsByPath): void
    {
        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                self::injectAltValues($value, $altsByPath);
            } elseif (is_string($key) && str_ends_with($key, '_url') && isset($altsByPath[$value])) {
                $altKey = str_replace('_url', '_alt', $key); // image_url -> image_alt, mimage_url -> mimage_alt
                $data[$altKey] = $altsByPath[$value];
            }
        }
    }


}
