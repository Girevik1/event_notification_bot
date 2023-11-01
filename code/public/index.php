<?php

declare(strict_types=1);

require '../vendor/autoload.php';

try {
    (new \Art\Code\Application\Helper\DotEnv(__DIR__ . '/../.env'))->load();
    (new \Art\Code\Infrastructure\Repository\Storage\StorageDefinition())->getStorage($_ENV['DB_DRIVER']);
    (new \Art\Code\Infrastructure\Http\Controllers\AppController())->run();
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}