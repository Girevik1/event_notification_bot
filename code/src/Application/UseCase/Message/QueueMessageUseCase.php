<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Message;

use Art\Code\Application\UseCase\Bot\AnniversaryUseCase;
use Art\Code\Application\UseCase\Bot\BirthdayUseCase;
use Art\Code\Application\UseCase\Bot\GroupUseCase;
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

    public function processQueueMessage(array $queue, TelegramUser $telegramUser, int $messageId, string $eventType): void
    {
        $this->queueMessageRepository->deleteAllMessageByUser($telegramUser->id);
        $this->createQueueMessages($queue, $telegramUser->id, $messageId, $eventType);
    }

    private function createQueueMessages(
        array $queue,
        int $telegramUserId,
        int $messageId,
        string $eventType
    ): void
    {
        $prev = null;
        foreach ($queue as $key => $value) {
            $queueMessage = $this->queueMessageRepository->createQueue(
                $telegramUserId,
                $key,
                $messageId,
                $eventType
            );

            if ($prev != null) {
                $prev->next_id = $queueMessage->id; // temp
                $prev->save();

                $queueMessage->previous_id = $prev->id;
                $queueMessage->save();
            }
            $prev = $queueMessage;
        }
    }

    /**
     * @param QueueMessage $message
     * @param QueueMessageRepositoryInterface $queueMessageRepository
     * @param TelegramGroupRepositoryInterface|null $groupRepository
     * @param string $userChatId
     * @return string|null
     * @throws EventNotFoundException
     * @throws QueueTypeException
     */
    public static function getMessageByType(
        QueueMessage                      $message,
        QueueMessageRepositoryInterface   $queueMessageRepository,
        ?TelegramGroupRepositoryInterface $groupRepository = null,
        string                            $userChatId = ''
    ): ?string
    {
        $message_texts = match ($message->event_type) {
            "birthday" => BirthdayUseCase::getMessagesQueueBirthday(),
            "anniversary" => AnniversaryUseCase::getMessagesQueueImportantEvent(),
            "note" => ['NOTE_NAME' => 'in test..'],
            default => throw new EventNotFoundException($message->event_type . ' - такой вид эвента не существует')
        };

        $text = $message_texts[$message->type];

        switch ($message->type) {

            case "GROUP":

                $text .= self::getNamesGroup($groupRepository, $userChatId);
                break;

            case "CONFIRMATION":

                $queueMessagesByUser = $queueMessageRepository->getAllByUserId($message->telegram_user_id);

                if (
                    $message->event_type === 'birthday' ||
                    $message->event_type === 'anniversary'
                ) {
                    foreach ($queueMessagesByUser as $queueMessage) {
                        if($queueMessage->type === 'GROUP' && $queueMessage->answer === '0'){
                            continue;
                        }
                        if($message->event_type === 'birthday'){
                            $text .= self::getTextConfirmationBirthday($queueMessage, $groupRepository, $userChatId);
                        }
                        if($message->event_type === 'anniversary'){
                            $text .= self::getTextConfirmationAnniversary($queueMessage, $groupRepository, $userChatId);
                        }
                    }
                }
                break;

            default:
                break;
        }
        $message->state = "SENT"; // temp
        $message->save();

        return $text;
    }

    /**
     * @throws QueueTypeException
     */
    private static function getTextConfirmationBirthday(QueueMessage $queueMessage, ?TelegramGroupRepositoryInterface $groupRepository, string $userChatId): string
    {
        return match ($queueMessage->type) {
            "NANE_WHOSE_BIRTHDAY" => "\nИмя: <i>" . $queueMessage->answer . "</i>",
            "DATE_OF_BIRTH" => "\nДата рождения: <i>" . $queueMessage->answer . "</i>",
            "NOTIFICATION_TYPE" => self::getNotificationTypeByCondition($queueMessage->answer),
            "GROUP" => GroupUseCase::getNameGroup($queueMessage->answer, $groupRepository, $userChatId),
            "TIME_NOTIFICATION" => "\nВремя оповещения: <i>" . $queueMessage->answer . "</i>",
            "CONFIRMATION" => "",
            default => throw new QueueTypeException($queueMessage->type . ' - такой тип очереди не существует')
        };
    }

    /**
     * @throws QueueTypeException
     */
    private static function getTextConfirmationAnniversary(QueueMessage $queueMessage, ?TelegramGroupRepositoryInterface $groupRepository, string $userChatId): string
    {
        return match ($queueMessage->type) {
            "NANE_EVENT" => "\nСобытие: <i>" . $queueMessage->answer . "</i>",
            "DATE_OF_EVENT" => "\nДата начала события: <i>" . $queueMessage->answer . "</i>",
            "NOTIFICATION_TYPE" => self::getNotificationTypeByCondition($queueMessage->answer),
            "GROUP" => GroupUseCase::getNameGroup($queueMessage->answer, $groupRepository, $userChatId),
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
     * @param string $userChatId
     * @return string
     */
    private static function getNamesGroup(TelegramGroupRepositoryInterface $groupRepository, string $userChatId): string
    {
        $textNameGroup = "";
        $groups = $groupRepository->getListByUser($userChatId);
        foreach ($groups as $group){
            $textNameGroup .= "\n<b>" . $group->id . ".</b> ". $group->name;
        }

        return $textNameGroup;
    }
}