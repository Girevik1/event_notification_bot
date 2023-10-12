<?php

declare(strict_types=1);

use Art\Code\Application\UseCase\Bot\BotUseCase;

require '../vendor/autoload.php';

try {
    (new \Art\Code\Application\Helper\DotEnv(__DIR__ . '/../.env'))->load();
    (new \Art\Code\Infrastructure\Http\Controllers\BotController)->run(new BotUseCase());
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}