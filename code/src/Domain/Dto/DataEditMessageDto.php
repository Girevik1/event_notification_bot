<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

use Art\Code\Domain\ValueObject\TelegramChatId;

class DataEditMessageDto
{
    public TelegramChatId $chat_id;
    public int $message_id;
    public string $text;
    public string $keyboard;
    public mixed $keyboardData = '';

}