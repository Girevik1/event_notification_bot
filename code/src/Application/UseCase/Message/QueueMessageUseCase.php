<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

use Art\Code\Domain\Entity\TelegramUser;

class QueueMessageUseCase
{
    private mixed $queueMessageRepository;

    public function __construct()
    {
        $dependence = require '../../../../dependence.php';
        $this->queueMessageRepository = new $dependence[\Art\Code\Domain\Contract\QueueMessageRepositoryInterface::class];
    }

    public function processQueueMessage(array $queue, TelegramUser $telegramUser): void
    {
        if ($this->queueMessageRepository->existUnfinishedQueueByUser($telegramUser->id)) {
            $this->queueMessageRepository->deleteOpenByUser($telegramUser->id);
            // есть не законченная очередь по др; -> delete -> create new queue
        }

        $this->createQueueMessages($queue, $telegramUser->id);


    }

    private function createQueueMessages(array $queue, int $telegramUserId): void
    {
        $prev = null;
        foreach ($queue as $key => $value) {
            $telegram_message = $this->queueMessageRepository->createQueue($telegramUserId, $key);
            if ($prev != null) {
                $prev->next_id = $telegram_message->id;
                $prev->save();
            }
            $prev = $telegram_message;
        }
    }
}