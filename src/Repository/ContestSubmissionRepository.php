<?php

namespace App\Repository;

use Pimcore\Model\DataObject\ContestSubmission;
use Pimcore\Model\DataObject;

class ContestSubmissionRepository
{


    public function getFolder()
    {
        return DataObject::getByPath('/contest-submissions');
    }

    public function getList($page = 1, $limit = 10)
    {
        $list = new ContestSubmission\Listing();

        $list->setUnpublished(true);

        $list->setOrderKey('oo_id');
        $list->setOrder('DESC');

        $list->setOffset(($page - 1) * $limit);
        $list->setLimit($limit);

        return $list;
    }

    public function getById($id)
    {
        return ContestSubmission::getById($id, ['force' => true]);
    }
}