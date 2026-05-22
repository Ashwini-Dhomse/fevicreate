<?php

namespace App\Service;

use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service;

class FileUploadService
{

    public function upload($file, $folderPath, $fileNamePrefix): Asset
    {

        // Create folder if not exists
        $folder = Service::createFolderByPath($folderPath);

        $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);

        $fileName = $fileNamePrefix . time() . '_' . rand(1000,9999) . '.' . $extension;

        $asset = new Asset();
        $asset->setFilename($fileName);
        $asset->setData(file_get_contents($file->getPathname()));
        $asset->setParent($folder);
        $asset->save();

        return $asset;
    }
}