<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;

class BookmarkRepository
{
    private string $table = 'bookmarks';

    public function __construct(
        private Connection $connection
    ) {}

    /**
     * Check if bookmark exists
     */
    public function exists(int $userId, int $objectId, string $type = 'activity'): bool
    {
        $sql = "
            SELECT 1 
            FROM {$this->table}
            WHERE user_id = :user_id 
              AND object_id = :object_id 
              AND type = :type
            LIMIT 1
        ";

        $result = $this->connection->fetchOne($sql, [
            'user_id' => $userId,
            'object_id' => $objectId,
            'type' => $type
        ]);

        return (bool) $result;
    }

    /**
     * Create bookmark
     */
    public function create(int $userId, int $objectId, string $type = 'activity'): void
    {
        $this->connection->insert($this->table, [
            'user_id' => $userId,
            'object_id' => $objectId,
            'type' => $type,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Delete bookmark
     */
    public function delete(int $userId, int $objectId, string $type = 'activity'): void
    {
        $this->connection->delete($this->table, [
            'user_id' => $userId,
            'object_id' => $objectId,
            'type' => $type
        ]);
    }
}