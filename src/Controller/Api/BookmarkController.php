<?php

namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\BookmarkService;
use App\Util\ApiResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/bookmarks')]

class BookmarkController extends AbstractController
{
    public function __construct(
        private BookmarkService $service
    ) {}

    #[Route('/{objectId}/{type}', methods: ['GET'])]
    public function add(int $objectId, string $type = 'activity')
    {

        try {

            $userId = 1; // from auth later

            $this->service->add($userId, $objectId, $type);

            return ApiResponse::success([], 'Bookmarked');

        } catch (\Throwable $th) {
        }
    }

    #[Route('/{objectId}', methods: ['DELETE'])]
    public function remove(int $objectId, string $type = 'activity')
    {

        try {

            $userId = 1;

            $this->service->remove($userId, $objectId, $type);

            return ApiResponse::success([], 'Removed');

        } catch (\Throwable $th) {
        }
    }
}