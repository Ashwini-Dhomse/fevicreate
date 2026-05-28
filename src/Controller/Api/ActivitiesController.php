<?php

namespace App\Controller\Api;

use App\Service\ActivitiesService;
use App\Util\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/activities')]

class ActivitiesController extends AbstractController
{
    public function __construct(
        private ActivitiesService $service
    ) {
    }

    #[Route('', name: 'api_activities_list', methods: ['GET'])]
    public function list(Request $request)
    {

        try {

            $page = $request->get('page', 1);
            $limit = $request->get('limit', 10);

            $data = $this->service->getActivitiesList($page, $limit);

            return ApiResponse::success($data, 'Activities list');

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }

    #[Route('/{id}', name: 'api_activities_detail', methods: ['GET'])]
    public function detail(int $id, Request $request)
    {

        try {

            $data = $this->service->getDetail($id);

            return ApiResponse::success($data, 'Activity Detail');

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
