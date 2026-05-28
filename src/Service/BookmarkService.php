<?php

namespace App\Service;

use App\Repository\BookmarkRepository;

class BookmarkService
{
    public function __construct(
        private BookmarkRepository $repo
    ) {}

    public function add(int $userId, int $objectId, string $type): void
    {
        if (!$this->repo->exists($userId, $objectId, $type)) {
            $this->repo->create($userId, $objectId, $type);
        }
    }

    public function remove(int $userId, int $objectId, string $type): void
    {
        $this->repo->delete($userId, $objectId, $type);
    }
}