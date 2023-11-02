<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

use Art\Code\Domain\ValueObject\TelegramChatId;

class TelegramMessageDto
{
    public TelegramChatId $chat_id;
    public int $message_id = 0;
    public string $text;
    public int $reply_to = 0;
    public string $command = '';
    public int $is_deleted_from_chat = 0;
}