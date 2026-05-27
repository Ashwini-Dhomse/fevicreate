<?php

namespace App\Repository;

use Pimcore\Model\DataObject\Activities;

class ActivitiesRepository
{

    public function getList($page = 1, $limit = 10) {

        $list = new Activities\Listing;

        $list->setUnpublished(true);

        $list->setOrderKey('oo_id');
        $list->setOrder('DESC');

        $list->setOffset(($page - 1) * $limit);
        $list->setLimit($limit);

        return $list;
    }

    public function getById(int $id)
    {
        return Activities::getById($id, ['force' => true]);
    }
}