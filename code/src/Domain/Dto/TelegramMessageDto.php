<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class TelegramMessageDto
{
    public string $chat_id;
    public int $message_id = 0;
    public string $text;
    public int $reply_to = 0;
    public string $command = '';
    public int $is_deleted_from_chat = 0;
}