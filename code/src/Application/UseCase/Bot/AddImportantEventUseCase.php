<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Contract\QueueMessageRepositoryInterface;
use Art\Code\Domain\Entity\TelegramUser;
use Telegram\Bot\Api;

class AddImportantEventUseCase
{
    private Api $telegram;
    private TelegramUser $telegramUser;
    public int $message_id;
    private QueueMessageUseCase $queueMessageUseCase;
    private QueueMessageRepositoryInterface $queueMessageRepository;

    public function __construct(
        Api          $telegram,
        TelegramUser $telegramUser,
        int          $message_id,
        QueueMessageRepositoryInterface  $queueMessageRepository
    )
    {
        $this->message_id = $message_id;
        $this->telegram = $telegram;
        $this->telegramUser = $telegramUser;
        $this->queueMessageRepository = $queueMessageRepository;
        $this->queueMessageUseCase = new QueueMessageUseCase($this->queueMessageRepository);
    }

    public function addImportantEvent(): void
    {
        $queueBirthday = $this->getMessagesQueueImportantEvent();

        $this->queueMessageUseCase->processQueueMessage($queueBirthday, $this->telegramUser, $this->message_id, 'important_event');

        $firstQueueMessage = $this->queueMessageRepository->getFirstOpenMsg($this->telegramUser->id);
    }

    public static function getMessagesQueueImportantEvent(): array
    {
        return [
            "NANE_EVENT" => "👶 <b>Укажите имя события</b>",
            "DATE_OF_BIRTH" => "📆 <b>Дату годовщины</b> (формат: 01.01.1970)",
            "NOTIFICATION_TYPE" => "🔊 <b>Как уведомлять?</b>",
            "GROUP" => "👥<b> Укажите номер группы для оповещения</b> (например: 1) \n",
            "TIME_NOTIFICATION" => "⏰  <b>Укажите время оповещения в день рождения</b> (формат: 12:00)",
            "CONFIRMATION" => "<b>‼️ Подтвердите даннные:</b>",
        ];
    }
}