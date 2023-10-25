<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Entity\QueueMessage;
use Illuminate\Database\Eloquent\Collection;

interface QueueMessageRepositoryInterface
{
    public function createQueue(int $telegramUserId, string $key): ?QueueMessage;

    public function existUnfinishedQueueByUser(int $telegramUserId): ?QueueMessage;

    public function deleteAllMessageByUser(int $telegramUserId): mixed;

    public function getFirstOpenMsg(int $telegramUserId): ?QueueMessage;

    public function getLastSentMsg(int $telegramUserId): ?QueueMessage;

//    public function getAllByUser(int $telegramUserId): ?Collection;
}