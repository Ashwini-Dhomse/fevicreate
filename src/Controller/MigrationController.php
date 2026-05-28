<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/internal/setup')]
class MigrationController extends AbstractController
{
    #[Route('/create-tables/{table}', methods: ['GET'])]
    public function createTables(string $table, Connection $connection)
    {

        $schemas = [
            'bookmarks' => '
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                object_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                created_at DATETIME NOT NULL,
                UNIQUE KEY uniq_user_object (user_id, object_id, type),
                INDEX idx_user (user_id),
                INDEX idx_object (object_id)
            '
        ];

        if (!isset($schemas[$table])) {
            return new Response('Table schema not found', 400);
        }

        // Check if table already exists
        $schemaManager = $connection->createSchemaManager();
        if ($schemaManager->tablesExist([$table])) {
            return new Response("Table '$table' already exists");
        }

        // Create table
        $sql = "CREATE TABLE $table ({$schemas[$table]}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $connection->executeStatement($sql);

        return new Response("Table '$table' created successfully");
    }
}
