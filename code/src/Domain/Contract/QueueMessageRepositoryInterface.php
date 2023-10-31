<?php
declare(strict_types=1);

namespace Art\Code\Domain\Contract;

use Art\Code\Domain\Entity\QueueMessage;
use Illuminate\Support\Collection;

interface QueueMessageRepositoryInterface
{
    public function createQueue(int $telegramUserId, string $key, int $messageId, string $eventType): ?QueueMessage;

//    public function existUnfinishedQueueByUser(int $telegramUserId): ?QueueMessage;

    public function deleteAllMessageByUser(int $telegramUserId): mixed;

    public function getFirstOpenMsg(int $telegramUserId): ?QueueMessage;

    public function getLastSentMsg(int $telegramUserId): ?QueueMessage;

    public function getQueueMessageById(int $id): ?QueueMessage;

    public function makeNotSendState(int $id): void;

    public function updateFieldById(mixed $field, mixed $value, int $id): void;

    public function getAllByUserId(int $telegramUserId): Collection;
}