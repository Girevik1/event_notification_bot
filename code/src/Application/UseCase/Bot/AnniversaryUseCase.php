<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Application\UseCase\Message\QueueMessageUseCase;
use Art\Code\Domain\Dto\BotRequestDto;
use Art\Code\Domain\Dto\DataEditMessageDto;

class AnniversaryUseCase
{
    private QueueMessageUseCase $queueMessageUseCase;

    public function __construct(public BotRequestDto $botRequestDto)
    {
        $this->queueMessageUseCase = new QueueMessageUseCase($this->botRequestDto->queueMessageRepository);
    }

    public function addAnniversary(): void
    {
        $queueAnniversary = self::getMessagesQueueImportantEvent();

        $this->queueMessageUseCase->processQueueMessage(
            $queueAnniversary,
            $this->botRequestDto->telegramUser,
            $this->botRequestDto->messageId,
            'anniversary'
        );

        $firstQueueMessage = $this->botRequestDto->queueMessageRepository->getFirstOpenMsg($this->botRequestDto->telegramUser->id);

        $text = QueueMessageUseCase::getMessageByType($firstQueueMessage, $this->botRequestDto->queueMessageRepository);

        $dataEditMessageDto = new DataEditMessageDto();
        $dataEditMessageDto->message_id = $this->botRequestDto->messageId;
        $dataEditMessageDto->chat_id = $this->botRequestDto->telegramUser->telegram_chat_id;
        $dataEditMessageDto->text = $text;
        $dataEditMessageDto->keyboard = 'process_set_event';
        $this->botRequestDto->telegram::editMessageTextSend($dataEditMessageDto);
    }

    public static function getMessagesQueueImportantEvent(): array
    {
        return [
            "NANE_EVENT" => "💭 <b>Укажите название события</b>",
            "DATE_OF_EVENT" => "📆 <b>Дата начала события</b> (формат: 01.01.1970)",
            "NOTIFICATION_TYPE" => "🔊 <b>Как уведомлять?</b>",
            "GROUP" => "👥<b> Укажите номер группы для оповещения</b> (например: 1) \n",
            "TIME_NOTIFICATION" => "⏰  <b>Укажите время оповещения в день события</b> (формат: 12:00)",
            "CONFIRMATION" => "<b>‼️ Подтвердите данные:</b>",
        ];
    }
}