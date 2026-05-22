<?php

namespace App\Transformer;

use Pimcore\Model\Asset\Image;

class ImageTransformer
{
    public function transform(?Image $image): ?string
    {
        if (!$image) {
            return null;
        }

        return \Pimcore\Tool::getHostUrl() . $image->getFullPath();
    }
}