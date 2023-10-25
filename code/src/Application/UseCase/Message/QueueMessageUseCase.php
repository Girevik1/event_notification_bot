<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\QueueMessage;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Infrastructure\Repository\QueueMessageRepository;

class QueueMessageUseCase
{
//    private mixed $queueMessageRepository;

//    private QueueMessageRepositoryInterface $queueMessageRepository;

    public function __construct(public QueueMessageRepositoryInterface $queueMessageRepository)
    {
//       $this->queueMessageRepository = new QueueMessageRepository();
//        $dependence = require '../../../../dependence.php';
//        $this->queueMessageRepository = new $dependence[\Art\Code\Domain\Contract\QueueMessageRepositoryInterface::class];
    }

    public function processQueueMessage(array $queue, TelegramUser $telegramUser): void
    {

//       $a = QueueMessage::where("telegram_user_id", '=', $telegramUser->id)
//            ->where("state", "NOT_SEND")
//            ->first();
//        $telegram->editMessageText([
//            'chat_id' => '500264009',
//            'message_id' => $msg_id,
//            'text' => 'rer',
//            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
//            'parse_mode' => 'HTML',
//        ]);
//        if ($a != null) {
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