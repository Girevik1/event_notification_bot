<?php

declare(strict_types=1);

namespace Art\Code\Domain\Dto;

use Art\Code\Domain\ValueObject\TelegramChatId;

class TelegramGroupDto
{
    public string $name;
    public TelegramChatId $group_chat_id;
    public TelegramChatId $user_chat_id;

    public function __construct($message)
    {
        $groupName = $message["chat"]["title"] ?? '';
        $this->name = strtolower($groupName);
        $this->group_chat_id = $message["chat"]["id"];
        $this->user_chat_id = $message["from"]["id"];
    }
}