<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

class TelegramMessageDto
{
    public string $chat_id;
    public int $message_id;
    public string $text;
    public int $reply_to;
    public string $command;
    public int $is_deleted_from_chat;
}