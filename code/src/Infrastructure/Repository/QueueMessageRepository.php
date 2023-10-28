<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\QueueMessage;

class QueueMessageRepository implements QueueMessageRepositoryInterface
{
    /**
     * @param int $telegramUserId
     * @param string $key
     * @param string $eventType
     * @return QueueMessage|null
     */
    public function createQueue(int $telegramUserId, string $key, string $eventType): ?QueueMessage
    {
        $telegram_message = new QueueMessage();
        $telegram_message->telegram_user_id = $telegramUserId;
        $telegram_message->type = $key;
        $telegram_message->state = "NOT_SEND";
        $telegram_message->next_id = 0;
        $telegram_message->previous_id = 0;
        $telegram_message->answer = "";
        $telegram_message->event_type = $eventType;
        $telegram_message->save();

        return $telegram_message;
    }

    /**
     * @param int $telegramUserId
     * @return mixed
     */
    public function deleteAllMessageByUser(int $telegramUserId): mixed
    {
        return QueueMessage::where("telegram_user_id", '=', $telegramUserId)
            ->delete();
    }

    /**
     * @param int $telegramUserId
     * @return QueueMessage|null
     */
    public function getFirstOpenMsg(int $telegramUserId): ?QueueMessage
    {
        return QueueMessage::where("telegram_user_id", $telegramUserId)
            ->where("state", "NOT_SEND")
            ->orederBy('id','asc')
            ->first();
    }

    /**
     * @param int $telegramUserId
     * @return QueueMessage|null
     */
    public function getLastSentMsg(int $telegramUserId): ?QueueMessage
    {
        return QueueMessage::where("telegram_user_id", $telegramUserId)
            ->where("state", "SENT")
            ->where("answer", "=", "")
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * @param int $id
     * @return QueueMessage|null
     */
    public function getQueueMessageById(int $id): ?QueueMessage
    {
       return QueueMessage::where("id", $id)->first();
    }

    /**
     * @param int $id
     * @return void
     */
    public function makeNotSendState(int $id): void
    {
        QueueMessage::where("id", $id)
            ->update(['state' => 'NOT_SEND']);
    }

    /**
     * @param mixed $field
     * @param mixed $value
     * @param int $id
     * @return void
     */
    public function updateFieldById(mixed $field, mixed $value, int $id): void
    {
        QueueMessage::where("id", $id)
            ->update([$field => $value]);
    }
}