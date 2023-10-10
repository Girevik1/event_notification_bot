<?php

declare(strict_types=1);

require '../vendor/autoload.php';

try {
    (new \Art\Code\Application\Helper\DotEnv(__DIR__ . '/../.env'))->load();
    (new \Art\Code\Infrastructure\Http\Controllers\BotController)->run();
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}