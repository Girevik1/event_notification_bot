<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Entity\QueueMessage;

interface QueueMessageRepositoryInterface
{
    public function createQueue(int $telegramUserId, string $key): ?QueueMessage;

    public function existUnfinishedQueueByUser(int $telegramUserId): bool;

    public function deleteOpenByUser(int $telegramUserId): mixed;

    public function getFirstOpenMsg(int $telegramUserId): QueueMessage;
}