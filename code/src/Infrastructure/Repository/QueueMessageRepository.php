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
        $telegram_message->answer = "";
        $telegram_message->save();

        return $telegram_message;
    }

    /**
     * @param int $telegramUserId
     * @return bool
     */
    public function existUnfinishedQueueByUser(int $telegramUserId)
    {
        return QueueMessage::where("telegram_user_id", '=', $telegramUserId)
            ->where("state", "NOT_SEND")
            ->first();
    }

    /**
     * @param int $telegramUserId
     * @return mixed
     */
    public function deleteOpenByUser(int $telegramUserId): mixed
    {
        return QueueMessage::where("telegram_user_id", '=', $telegramUserId)
//            ->where("state", "NOT_SEND")
            ->delete();
    }

    public function getFirstOpenMsg(int $telegramUserId): QueueMessage
    {
        return QueueMessage::where("telegram_user_id", $telegramUserId)
            ->where("state", "NOT_SEND")
//            ->where("answer", "<>", "")
//            ->orderBy("id", 'desc')
            ->first();
    }
}