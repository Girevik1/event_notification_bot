<?php

declare(strict_types=1);

return [
    \Art\Code\Domain\Contract\TelegramUserRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\TelegramUserRepository(),
    \Art\Code\Domain\Contract\TelegramMessageRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\TelegramMessageRepository(),
    \Art\Code\Domain\Contract\TelegramGroupRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\TelegramGroupRepository(),
    \Art\Code\Domain\Contract\QueueMessageRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\QueueMessageRepository(),
    \Art\Code\Domain\Contract\ListEventRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\ListEventRepository(),
    \Art\Code\Domain\Contract\TelegramHandlerInterface::class => new \Art\Code\Infrastructure\Telegram\TelegramHandler(),
];