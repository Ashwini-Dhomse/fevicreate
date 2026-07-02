<?php

namespace App\Repository;

use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\ContestSubmission;
use Pimcore\Model\DataObject\ContestSubmission\Listing;

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

    /**
     * Get contest submissions by user.
     *
     * @param int $userId User Object ID
     * @param int $page   Current page number
     * @param int $limit  Number of records per page
     *
     * @return Listing
     */
    public function getByUser(int $userId, int $page = 1, int $limit = 10): Listing
        {
            $list = new Listing();

            // Filter submissions by user relation
            $list->setCondition("user__id = ?", [$userId]);

            // Show latest submissions first
            $list->setOrderKey("creationDate");
            $list->setOrder("DESC");

            // Apply pagination
            $list->setLimit($limit);
            $list->setOffset(($page - 1) * $limit);

            return $list;
        }
}
