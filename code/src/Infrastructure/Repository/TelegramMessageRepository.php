<?php

declare(strict_types=1);

namespace Art\Code\Infrastructure\Repository;

use Art\Code\Domain\Entity\TelegramMessage;
use Art\Code\Domain\ValueObject\TelegramUser\TelegramUserId;

class TelegramMessageRepository
{
    public function create(array $message): void
    {
       TelegramMessage::create([
            'telegram_user_id' => new TelegramUserId(1),
            'message_id' => 0,
            'text' => 'Developer text',
            'reply_to' => 0,
            'command' => 'Developer',
            'model' => 0,
            'model_id' => 0,
            'is_deleted_from_chat' => 0,
            'data_test' => $message
        ]);
    }
}