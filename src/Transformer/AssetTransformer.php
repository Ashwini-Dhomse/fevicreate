<?php

namespace App\Transformer;

use Pimcore\Model\Asset;

class AssetTransformer
{

    /**
     * Transform single asset
     */
    public static function transform(?Asset $asset): ?array
    {

        if (!$asset instanceof Asset) {
            return null;
        }

        return [
            'id' => $asset->getId(),
            'filename' => $asset->getFilename(),
            'url' => \Pimcore\Tool::getHostUrl().$asset->getFullPath(),
            'type' => $asset->getType(),
            'mimeType' => $asset->getMimeType(),
            'size' => $asset->getFileSize(),
        ];
    }

    /**
     * Transform multiple assets
     */
    public static function transformCollection(?array $assets): array
    {
        if (empty($assets)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($asset) {
            return self::transform($asset);
        }, $assets)));
    }
}