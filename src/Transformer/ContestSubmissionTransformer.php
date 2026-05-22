<?php

namespace App\Transformer;

use Pimcore\Model\DataObject\ContestSubmission;
use App\Transformer\AssetTransformer;

class ContestSubmissionTransformer
{
    /**
     * For Listing API
     */
    public static function list(ContestSubmission $item): array
    {
        return [
            'id' => $item->getId(),
            'childName' => $item->getChildName(),
            'parentPhone' => $item->getparentPhone(),
            'artworkTitle' => $item->getArtworkTitle(),
        ];
    }

    /**
     * For Detail API
     */
    public static function detail(ContestSubmission $item): array
    {
        return [
            'id' => $item->getId(),
            'childName' => $item->getChildName(),
            'parentEmail' => $item->getParentEmail(),
            'parentPhone' => $item->getParentPhone(),
            'artworkTitle' => $item->getArtworkTitle(),

            'documents' => AssetTransformer::transformCollection($item->getDocuments())
        ];
    }

    /**
     * For API response after save
     */
    public static function afterSave(ContestSubmission $item): array
    {
        return [
            'id' => $item->getId(),
            'message' => 'Submission successful'
        ];
    }
}