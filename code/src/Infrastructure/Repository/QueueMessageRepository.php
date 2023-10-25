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
     * @return QueueMessage|null
     */
    public function createQueue(int $telegramUserId, string $key): ?QueueMessage
    {
        $telegram_message = new QueueMessage();
        $telegram_message->telegram_user_id = $telegramUserId;
        $telegram_message->type = $key;
        $telegram_message->state = "NOT_SEND";
        $telegram_message->next_id = 0;
        $telegram_message->previous_id = 0;
        $telegram_message->answer = "";
        $telegram_message->save();

        return $telegram_message;
    }

    /**
     * @param int $telegramUserId
     * @return QueueMessage|null
     */
    public function existUnfinishedQueueByUser(int $telegramUserId): ?QueueMessage
    {
        return QueueMessage::where("telegram_user_id", '=', $telegramUserId)
            ->where("state", "NOT_SEND")
            ->first();
    }

    /**
     * @param int $telegramUserId
     * @return mixed
     */
    public function deleteAllMessageByUser(int $telegramUserId): mixed
    {
        return QueueMessage::where("telegram_user_id", '=', $telegramUserId)
//            ->where("state", "NOT_SEND")
            ->delete();
    }

    public function getFirstOpenMsg(int $telegramUserId): ?QueueMessage
    {
        return QueueMessage::where("telegram_user_id", $telegramUserId)
            ->where("state", "NOT_SEND")
//            ->where("answer", "<>", "")
//            ->orderBy("id", 'desc')
            ->first();
    }

    public function getLastSentMsg(int $telegramUserId): ?QueueMessage
    {
        return QueueMessage::where("telegram_user_id", $telegramUserId)
            ->where("state", "SENT")
            ->where("answer", "=", "")
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getQueueMessageById(int $id): ?QueueMessage
    {
       return QueueMessage::where("id", $id)->first();
    }

    public function makeNotSendState(int $id): void
    {
        QueueMessage::where("id", $id)
//            ->where()
            ->update(['state' => 'NOT_SEND']);
    }

    public function getAllSentByUser(int $telegramUserId): ?QueueMessage
    {
        return QueueMessage::where("telegram_user_id", $telegramUserId)
            ->where('state', 'SENT')
            ->get();
    }
}