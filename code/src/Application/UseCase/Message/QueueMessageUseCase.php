<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

use Art\Code\Application\UseCase\Bot\AddBirthdayUseCase;
use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Contract\TelegramGroupRepositoryInterface;
use Art\Code\Domain\Entity\QueueMessage;
use Art\Code\Domain\Entity\TelegramUser;
use Art\Code\Domain\Exception\EventNotFoundException;
use Art\Code\Domain\Exception\QueueTypeException;

class QueueMessageUseCase
{
    public function __construct(public QueueMessageRepositoryInterface $queueMessageRepository)
    {
    }

    public function processQueueMessage(array $queue, TelegramUser $telegramUser, string $eventType): void
    {
        $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);
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

    public static function getMessageByType(
        QueueMessage                      $message,
        QueueMessageRepositoryInterface   $queueMessageRepository,
        ?TelegramGroupRepositoryInterface $groupRepository = null,
        string                            $chatId = ''
    ): ?string
    {

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
//                $text .= "\n   (или /cancel для отмены отзыва)";
                $text .= self::getNamesGroup($groupRepository,$chatId);
                break;

            case "DATE_OF_BIRTH":
                $text .= '';
                break;

            case "CONFIRMATION":

                $queueMessagesByUser = $queueMessageRepository->getAllByUserId($message->telegram_user_id);

                if ($message->event_type === 'birthday') {
                    foreach ($queueMessagesByUser as $queueMessage) {
                        if($queueMessage->type === 'GROUP' && $queueMessage->answer === '0'){
                            continue;
                        }
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
        return match ($queueMessage->type) {
            "NANE_WHOSE_BIRTHDAY" => "\nИмя: <i>" . $queueMessage->answer . "</i>",
            "DATE_OF_BIRTH" => "\nДата рождения: <i>" . $queueMessage->answer . "</i>",
            "NOTIFICATION_TYPE" => self::getNotificationTypeByCondition($queueMessage->answer),
            "GROUP" => "\nГруппа: <i>" . $queueMessage->answer . "</i>",
            "TIME_NOTIFICATION" => "\nВремя оповещения: <i>" . $queueMessage->answer . "</i>",
            "CONFIRMATION" => "",
            default => throw new QueueTypeException($queueMessage->type . ' - такой тип очереди не существует')
        };
    }

    /**
     * @param string $answer
     * @return string
     */
    private static function getNotificationTypeByCondition(string $answer): string
    {
        if ($answer === 'personal') {
            return "\nКак уведомлять: <i>Лично</i>";
        }
        return "\nКак уведомлять: <i>В группе</i>";

    }

    /**
     * @param TelegramGroupRepositoryInterface $groupRepository
     * @param string $chatId
     * @return string
     */
    private static function getNamesGroup(TelegramGroupRepositoryInterface $groupRepository, string $chatId): string
    {
        $textNameGroup = "\n";
        $groups = $groupRepository->getListByUser($chatId);
        foreach ($groups as $group){
            $textNameGroup .= "\n" . $group->id . ") <b>". $group->name . "</b>";
        }

        return $textNameGroup;
    }

}