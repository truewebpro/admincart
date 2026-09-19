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

}
