<?php

namespace App\Controller\Api;

use App\Attribute\RequireAuth;
use App\DTO\ContestSubmissionDTO;
use App\Service\ContestSubmissionService;
use App\Util\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/contest')]

class ContestController extends AbstractController
{
    private $service;

    public function __construct(
        ContestSubmissionService $service
    ) {
        $this->service = $service;
    }

    // #[RequireAuth]
    #[Route('', name: 'api_contest_post', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {

        try {

            $user = $request->attributes->get('auth_user');

            $dto = ContestSubmissionDTO::fromRequest($request);

            // Validate required fields
            $errors = $dto->validate();

            if (!empty($errors)) {
                return ApiResponse::error(
                    'Validation failed',
                    $errors
                );
            }

            $data = $this->service->save($dto);

            return ApiResponse::success(
                $data,
                'Submission successful',
            );

        } catch (\Throwable $e) {
    return $this->json([
        'success' => false,
        'message' =>"Something went wrong"
    ], 500);
}
    }

    #[Route('', name: 'api_contest_list', methods: ['GET'])]
    public function list(Request $request)
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 10);

        $data = $this->service->getList($page, $limit);

        return ApiResponse::success($data, 'Contest list');
    }

    #[Route('/{id}', name: 'api_contest_detail', methods: ['GET'])]
    public function detail(int $id)
    {

        try {

            $data = $this->service->getDetail($id);

            return ApiResponse::success($data, 'Contest detail');

        } catch (\Exception $e) {
            return ApiResponse::error($e->getMessage());
        }
    }
}
