<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository\Storage;

use Art\Code\Infrastructure\Repository\Storage\Postgres\DatabaseConnection;
use InvalidArgumentException;

class StorageDefinition
{
    public function getStorage(string $storage): \Illuminate\Database\Capsule\Manager
    {
        return match ($storage) {
            'pgsql' => DatabaseConnection::getInstance(),
            default => throw new InvalidArgumentException("Storage type not defined!"),
        };
    }
}