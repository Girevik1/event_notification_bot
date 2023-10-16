<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository\Storage;

use Art\Code\Domain\Exception\DatabaseConnectionException;
use Art\Code\Infrastructure\Repository\Storage\Postgres\DatabasePostgresConnection;

class StorageDefinition
{
    /**
     * @throws DatabaseConnectionException
     */
    public function getStorage(string $storage): \Illuminate\Database\Capsule\Manager
    {
        return match ($storage) {
            'pgsql' => DatabasePostgresConnection::getInstance(),
            default => throw new DatabaseConnectionException("Storage type not defined!"),
        };
    }
}