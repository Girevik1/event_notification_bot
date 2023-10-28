<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

use Art\Code\Application\UseCase\Bot\AddBirthdayUseCase;
use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\QueueMessage;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\EventNotFoundException;
use Art\Code\Domain\Exception\QueueTypeException;

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

    public function processQueueMessage(array $queue, TelegramUser $telegramUser, string $eventType): void
    {
        $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);
        // есть не законченная очередь по др; -> delete -> create new queue

        $this->createQueueMessages($queue, $telegramUser->id, $eventType);


    }

    private function createQueueMessages(array $queue, int $telegramUserId, string $eventType): void
    {
        $prev = null;
        foreach ($queue as $key => $value) {
            $telegram_message = $this->queueMessageRepository->createQueue($telegramUserId, $key, $eventType);

            if ($prev != null) {
                $prev->next_id = $telegram_message->id;
                $prev->save();

                $telegram_message->previous_id = $prev->id;
                $telegram_message->save();
            }

            $prev = $telegram_message;
        }
    }

    /**
     * @throws EventNotFoundException|QueueTypeException
     */
    public static function getMessageByType($message, $telegram = '', $message_id = ''): ?string
    {
        if ($message == null) {
            return null;
        }

//        $telegram->editMessageText([
//            'chat_id' => '500264009',
//            'message_id' => $message_id,
//            'text' => 'test434',
////            'reply_markup' => TelegramSender::getKeyboard('process_set_event'),
//            'parse_mode' => 'HTML',
//        ]);

        $message_texts = match ($message->event_type) {
            "birthday" => AddBirthdayUseCase::getMessagesQueueBirthday(),
            "note" => ['NOTE_NAME'=>'in test..'],
            default => throw new EventNotFoundException($message->event_type . ' - такой вид эвента не существует')
        };

        $text = $message_texts[$message->type];

        switch ($message->type) {

            case "GROUP":
//                $text .= self::getRubrics();
                $text .= "\n   (или /cancel для отмены отзыва)";
                break;

            case "DATE_OF_BIRTH":
                $text .= '';
                break;

            case "CONFIRMATION":
                $queueMessagesByUser = QueueMessage::where('telegram_user_id', $message->telegram_user_id)->get();
                if ($message->event_type === 'birthday') {
                    foreach ($queueMessagesByUser as $queueMessage) {
                        $text .= self::getTextConfirmationBirthday($queueMessage);
                    }
                }
                break;

            default:
                break;
        }

        $message->state = "SENT";
        $message->save();

        return $text;
    }

    /**
     * @throws QueueTypeException
     */
    private static function getTextConfirmationBirthday(QueueMessage $queueMessage): string
    {
        return match ($$queueMessage->type) {
            "NANE_WHOSE_BIRTHDAY" => "\nИмя: " . $queueMessage->answer,
            "DATE_OF_BIRTH" => "\nДата рождения: " . $queueMessage->answer,
            "GROUP" => "\nГруппа: " . $queueMessage->answer,
            "TIME_NOTIFICATION" => "\nВремя оповещения: " . $queueMessage->answer,
            default => throw new QueueTypeException($queueMessage->type . ' - такой тип очереди не существует')
        };
    }

}