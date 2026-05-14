<?php

namespace App\Controller\Api;

use Pimcore\Controller\FrontendController;
use Pimcore\Model\DataObject\User;
use Pimcore\Model\DataObject\Folder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class UserController extends FrontendController
{
    /**
     * @Route("/api/user/add", name="api_user_add", methods={"POST"})
     */
    public function addUser(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data) {
                return $this->jsonError('Invalid JSON payload', 400);
            }

            // ===============================
            // Get or Create Parent Folder
            // ===============================
            $parent = Folder::getByPath('/Users');

            if (!$parent) {
                $parent = new Folder();
                $parent->setKey('Users');
                $parent->setParentId(1); // root
                $parent->save();
            }

            // ===============================
            // Create User Object
            // ===============================
            $user = new User();
            $user->setParentId($parent->getId());
            $user->setKey($this->generateUserKey($data));
            $user->setPublished(true);

            // ===============================
            // Personal Data
            // ===============================
            $user->setActive($data['active'] ?? true);
            $user->setFirstname($data['firstname'] ?? null);
            $user->setLastname($data['lastname'] ?? null);
            $user->setGender($data['gender'] ?? null);
            $user->setUser_type($data['user_type'] ?? null);

            // ===============================
            // Password (hashed)
            // ===============================
            if (!empty($data['password'])) {
                $user->setPassword(
                    password_hash($data['password'], PASSWORD_BCRYPT)
                );
            }

            // ===============================
            // Address
            // ===============================
            $user->setZip($data['zip'] ?? null);
            $user->setCity($data['city'] ?? null);
            $user->setSTATE($data['state'] ?? null);
            $user->setCOUNTRY($data['country'] ?? null);
            $user->setStreet($data['street'] ?? null);

            // ===============================
            // Contact
            // ===============================
            $user->setEmail($data['email'] ?? null);
            $user->setPhone($data['phone'] ?? null);

            // ===============================
            // Save
            // ===============================
            $user->save();

            return new JsonResponse([
                'success' => true,
                'id' => $user->getId(),
                'message' => 'User created successfully'
            ], 201);

        } catch (\Exception $e) {
            return $this->jsonError($e->getMessage(), 500);
        }
    }

    /**
     * @Route("/api/user/login", name="api_user_login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || empty($data['email']) || empty($data['password'])) {
            return $this->jsonError('Email and password required', 400);
        }

        $userList = new User\Listing();
        $userList->setCondition('email = ?', [$data['email']]);
        $userList->setLimit(1);

        $user = $userList->current();

        if (!$user || !password_verify($data['password'], $user->getPassword())) {
            return $this->jsonError('Invalid email or password', 401);
        }

        if (!$user->getActive()) {
            return $this->jsonError('User inactive', 403);
        }

        // ✅ STORE USER IN SESSION
        $session = $request->getSession();
        $session->set('user_id', $user->getId());

        return new JsonResponse([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname()
            ]
        ]);
    }

   /**
     * @Route("/api/user/logout", name="api_user_logout", methods={"POST"})
     */
    public function logout(Request $request): JsonResponse
    {
        $session = $request->getSession();

        if (!$session->has('user_id')) {
            return $this->jsonError('User not logged in', 401);
        }

        // ✅ Clear session
        $session->invalidate();

        return new JsonResponse([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    // ==================================================
    // Helpers
    // ==================================================

    private function generateUserKey(array $data): string
    {
        $base = trim(
            ($data['firstname'] ?? 'user') . '-' . ($data['lastname'] ?? '')
        );

        return strtolower(preg_replace('/[^a-z0-9\-]/i', '', $base))
            . '-' . time();
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message
        ], $status);
    }
}
