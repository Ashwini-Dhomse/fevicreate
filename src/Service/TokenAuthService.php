<?php

namespace App\Service;

use Pimcore\Model\DataObject\Users;
use Symfony\Component\HttpFoundation\Request;

class TokenAuthService
{
    public function getUserFromRequest(Request $request): ?Users
    {
        $header = $request->headers->get('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = str_replace('Bearer ', '', $header);

        $list = new Users\Listing();
        $list->setCondition('apiToken = ?', [$token]);
        $list->setLimit(1);

        $users = $list->load();

        return $users[0] ?? null;
    }
}
