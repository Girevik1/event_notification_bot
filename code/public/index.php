<?php

declare(strict_types=1);

use Art\Code\Application\UseCase\Bot\BotUseCase;

require '../vendor/autoload.php';
$dependence = require'../dependence.php';

try {
    (new \Art\Code\Application\Helper\DotEnv(__DIR__ . '/../.env'))->load();
    (new \Art\Code\Infrastructure\Repository\Storage\StorageDefinition())->getStorage($_ENV['DB_DRIVER']);
    (new \Art\Code\Infrastructure\Http\Controllers\BotController)->run(
            new BotUseCase(
                $dependence[\Art\Code\Domain\Contract\TelegramUserRepositoryInterface::class],
                $dependence[\Art\Code\Domain\Contract\TelegramMessageRepositoryInterface::class],
                $dependence[\Art\Code\Domain\Contract\TelegramGroupRepositoryInterface::class],
                $dependence[\Art\Code\Domain\Contract\QueueMessageRepositoryInterface::class],
            )
        );
} catch (\Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}