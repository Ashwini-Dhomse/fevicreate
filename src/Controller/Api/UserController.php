<?php

namespace App\Controller\Api;

use App\DTO\UserDTO;
use App\Service\UserService;
use App\Util\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user')]
class UserController extends AbstractController
{
    private UserService $service;

    public function __construct(
        UserService $service
    ) {
        $this->service = $service;
    }

    /**
     * Register User
     */
    #[Route('', name: 'api_user_add', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {

            $dto = UserDTO::fromRequest($request);

            // =========================
            // Validate Request
            // =========================
            $errors = $dto->validate();

            if (!empty($errors)) {
                return ApiResponse::error(
                    'Validation failed',
                    $errors
                );
            }

            // =========================
            // Save User
            // =========================
            $data = $this->service->create($dto);

            return ApiResponse::success(
                $data,
                'User created successfully',
                201
            );

        } catch (\Exception $e) {

            return ApiResponse::error(
                $e->getMessage()
            );
        }
    }

    /**
     * Login User
     */
    #[Route('/login', name: 'api_user_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {

            $data = json_decode($request->getContent(), true);

            if (
                empty($data['email']) ||
                empty($data['password'])
            ) {

                return ApiResponse::error(
                    'Email and password required'
                );
            }

            $user = $this->service->login(
                $data['email'],
                $data['password']
            );

            // =========================
            // Store Session
            // =========================
            $session = $request->getSession();

            $session->set(
                'user_id',
                $user->getId()
            );

            return ApiResponse::success([
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
            ], 'Login successful');

        } catch (\Exception $e) {

            return ApiResponse::error(
                $e->getMessage()
            );
        }
    }

    /**
     * Logout User
     */
    #[Route('/logout', name: 'api_user_logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        try {

            $session = $request->getSession();

            if (!$session->has('user_id')) {

                return ApiResponse::error(
                    'User not logged in'
                );
            }

            $session->invalidate();

            return ApiResponse::success(
                null,
                'Logged out successfully'
            );

        } catch (\Exception $e) {

            return ApiResponse::error(
                $e->getMessage()
            );
        }
    }
}
