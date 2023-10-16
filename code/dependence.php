<?php

declare(strict_types=1);

return [
    \Art\Code\Domain\Contract\TelegramUserRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\TelegramUserRepository(),
    \Art\Code\Domain\Contract\TelegramMessageRepositoryInterface::class => new \Art\Code\Infrastructure\Repository\TelegramMessageRepository(),
];