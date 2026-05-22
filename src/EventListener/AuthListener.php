<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\TokenAuthService;
use App\Attribute\RequireAuth;
use ReflectionMethod;

class AuthListener
{
    private $tokenAuthService;

    public function __construct(TokenAuthService $tokenAuthService)
    {
        $this->tokenAuthService = $tokenAuthService;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $controller = $request->attributes->get('_controller');

        if (!$controller) {
            return;
        }

        if (is_string($controller)) {
            if (!str_contains($controller, '::')) {
                return;
            }
            $controller = explode('::', $controller);
        }
    
        if (!is_array($controller) || count($controller) !== 2) {
            return;
        }
    
        try {
            $reflection = new ReflectionMethod($controller[0], $controller[1]);
        } catch (\ReflectionException $e) {
            return; // skip if invalid controller
        }

        // Check if #[RequireAuth] is present
        $attributes = $reflection->getAttributes(RequireAuth::class);

        if (empty($attributes)) {
            return;
        }

        // Run auth
        $user = $this->tokenAuthService->getUserFromRequest($request);

        if (!$user) {
            $event->setResponse(new JsonResponse([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401));
            return;
        }

        // Attach user to request
        $request->attributes->set('auth_user', $user);
    }
}