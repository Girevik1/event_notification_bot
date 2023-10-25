<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\TelegramSender;
use Art\Code\Domain\Entity\TelegramUser;

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

    public function processQueueMessage($telegram, $msg_id, array $queue, TelegramUser $telegramUser): void
    {

//       $a = QueueMessage::where("telegram_user_id", '=', $telegramUser->id)
//            ->where("state", "NOT_SEND")
//            ->first();

//        if ($a != null) {
        if ($this->queueMessageRepository->existUnfinishedQueueByUser($telegramUser->id)) {

            $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);
            // есть не законченная очередь по др; -> delete -> create new queue
        }
        $telegram->editMessageText([
            'chat_id' => '500264009',
            'message_id' => $msg_id,
            'text' => 'rer',
            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
            'parse_mode' => 'HTML',
        ]);

        $this->createQueueMessages($queue, $telegramUser->id);


    }

    private function createQueueMessages(array $queue, int $telegramUserId): void
    {
        $prev = null;
        foreach ($queue as $key => $value) {
            $telegram_message = $this->queueMessageRepository->createQueue($telegramUserId, $key);
                $telegram_message->previous_id = 222;
                $telegram_message->save();
            if ($prev != null) {
                $prev->next_id = $telegram_message->id;
                $prev->save();

//                $telegram_message->previous_id = $prev->id;
//                $telegram_message->save();
            }

            $prev = $telegram_message;
        }
    }
}