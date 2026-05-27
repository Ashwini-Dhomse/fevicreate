<?php

namespace App\Transformer;

use Pimcore\Model\DataObject\Activities;
use App\Transformer\AssetTransformer;

class ActivitiesTransformer
{

    /**
     * For Listing API
     */
    public static function list(Activities $item): array
    {
        return [
            'id' => $item->getId(),
            'activityTitle' => $item->getActivityTitle(),
            'timeRange' => $item->getTimeRange(),
            'image' => AssetTransformer::transform($item->getImage())
        ];
    }

    /**
     * For Detail API
     */
    public static function detail(Activities $item): array
    {
        return [
            'id' => $item->getId(),
            'activityTitle' => $item->getActivityTitle(),
            'timeRange' => $item->getTimeRange(),
            'activityDescription' => $item->getactivityDescription(),
            'ageGroup' => $item->getageGroup(),
            'technique' => $item->gettechnique(),
            'subject' => $item->getsubject(),
            'theme' => $item->gettheme(),
            'activityClass' => $item->getactivityClass(),
            'materialsNeeded' => self::transformMaterials($item->getmaterialsNeeded()),
            'steps' => self::transformSteps($item->getactivitySteps()),
            'image' => AssetTransformer::transform($item->getImage())
        ];
    }

    private static function transformSteps($steps): array
    {
        $data = [];

        if (!$steps) {
            return [];
        }

        foreach ($steps as $step) {

            $data[] = [
                'title' => isset($step['stepTitle']) ? $step['stepTitle']->getData() : null,
                'description' => isset($step['stepDescription']) ? $step['stepDescription']->getData() : null,
                'image' => isset($step['image']) ? AssetTransformer::transform($step['image']->getData()) : null,
            ];
        }

        return $data;
    }

    private static function transformMaterials($materials): array
    {
        $data = [];

        if (!$materials) {
            return [];
        }

        foreach ($materials as $step) {

            $data[] = [
                'material' => isset($step['material']) ? $step['material']->getData() : null,
                'linkToBuy' => isset($step['linkToBuy']) ? $step['linkToBuy']->getData() : null,
            ];
        }

        return $data;
    }
}