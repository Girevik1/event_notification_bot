<?php

declare(strict_types=1);

namespace Art\Code\Application\UseCase\Bot;

use Art\Code\Domain\Entity\TelegramUser;
use Telegram\Bot\Api;

class AddBirthdayUseCase
{
    private Api $telegram;
    private TextUseCase $textUseCase;
    private TelegramUser $telegramUser;
    public int $message_id;

    public function __constructor(
        Api $telegram,
        TextUseCase $textUseCase,
        TelegramUser $telegramUser,
        int $message_id
    ): void
    {
        $this->telegram = $telegram;
        $this->textUseCase = $textUseCase;
        $this->telegramUser = $telegramUser;
        $this->message_id = $message_id;
    }

    public function addBirthday(): void
    {
        $text = $this->textUseCase->getAddBirthdayText();
        $this->telegram->editMessageText([
            'chat_id' => $this->telegramUser->telegram_chat_id,
            'message_id' => $this->message_id,
            'text' => $text,
            //                        'reply_markup' => TelegramSender::getKeyboard('settings_menu'),
            'parse_mode' => 'HTML',
        ]);
    }
}